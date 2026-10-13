<?php

namespace App\Services;

use App\Enums\CandidacyStatus;
use App\Enums\NotificationSendStatus;
use App\Models\Candidacy;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\CandidacyRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidacyService
{
    public function __construct(
        private readonly CandidacyRepository $repository,
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * 許可された選考ステータス遷移の定義。
     * キー=現在ステータス / 値=遷移先として許可するステータスの配列。
     * (設計書「状態遷移定義」の選考ステータス遷移に対応)
     */
    private const ALLOWED_TRANSITIONS = [
        CandidacyStatus::Screening->value => [
            CandidacyStatus::FirstInterview->value,
            CandidacyStatus::Rejected->value,
        ],
        CandidacyStatus::FirstInterview->value => [
            CandidacyStatus::SecondInterview->value,
            CandidacyStatus::Rejected->value,
        ],
        CandidacyStatus::SecondInterview->value => [
            CandidacyStatus::Offer->value,
            CandidacyStatus::Rejected->value,
        ],
        CandidacyStatus::Offer->value => [
            CandidacyStatus::OfferAccepted->value,
            CandidacyStatus::OfferDeclined->value,
        ],
        CandidacyStatus::OfferAccepted->value => [], // 終端
        CandidacyStatus::OfferDeclined->value => [], // 終端
        CandidacyStatus::Rejected->value => [],      // 終端
    ];

    /**
     * 選考ステータスを変更する(REQ-006/007/011)。
     * 定義外の遷移は拒否し、変更成功時は履歴と通知レコードを記録する。
     * ステータス更新・履歴記録・通知レコード作成は1トランザクションで整合させ、
     * メール送信は確定後に行う(送信に失敗してもステータス変更を取り消さないため)。
     *
     * @return Notification 送信結果(send_status)を反映した通知レコード
     */
    public function changeStatus(
        Candidacy $candidacy,
        CandidacyStatus $to,
        User $changedBy,
        bool $sendNotification,
    ): Notification {
        $from = $candidacy->status;

        if (! $this->canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "「{$from->label()}」から「{$to->label()}」への変更はできません。",
            ]);
        }

        $notification = DB::transaction(function () use ($candidacy, $from, $to, $changedBy, $sendNotification) {
            $changedAt = now();

            $this->repository->updateStatus($candidacy, $to);
            $statusHistory = $this->repository->createStatusHistory($candidacy, $from, $to, $changedBy->id, $changedAt);

            // 送信する場合は pending(CC=変更者)、送信しない場合は skipped(CCなし)で記録する
            return $this->notificationRepository->createForStatusChange(
                $candidacy,
                $statusHistory,
                $sendNotification ? NotificationSendStatus::Pending : NotificationSendStatus::Skipped,
                $sendNotification ? $changedBy->email : null,
                $changedAt,
            );
        });

        // トランザクション確定後に送信する(失敗しても例外は出ず、failed として記録される)
        if ($sendNotification) {
            $this->notificationService->send($notification);
        }

        return $notification;
    }

    /**
     * 指定ラウンドの面接官アサインを、渡されたリストで更新する(REQ-008)。
     * 対象ラウンドの既存アサインを全部消して、送られたリストで入れ直す(洗い替え)。
     */
    public function manageInterviewers(Candidacy $candidacy, int $round, array $interviewerIds): void
    {
        DB::transaction(function () use ($candidacy, $round, $interviewerIds) {
            $this->repository->syncInterviewers($candidacy, $round, $interviewerIds, now());
        });
    }

    /**
     * from -> to の遷移が許可されているのか。
     */
    private function canTransition(CandidacyStatus $from, CandidacyStatus $to): bool
    {
        return in_array($to->value, self::ALLOWED_TRANSITIONS[$from->value], true);
    }

    /**
     * 指定ステータスから遷移可能な次ステータスの「値」の配列を返す。
     * FormRequest の動的 in 判定などで、遷移表を唯一の情報源として共有するために公開する。
     *
     * @return array<int>
     */
    public static function allowedNextValues(CandidacyStatus $from): array
    {
        return self::ALLOWED_TRANSITIONS[$from->value] ?? [];
    }
}
