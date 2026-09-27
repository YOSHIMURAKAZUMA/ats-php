<?php

namespace App\Services;

use App\Enums\CandidacyStatus;
use App\Models\Candidacy;
use App\Repositories\CandidacyRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidacyService
{
    public function __construct(
        private readonly CandidacyRepository $repository,
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
     * 選考ステータスを変更する(REQ-006/007)。
     * 定義外の遷移は拒否し、変更成功時は履歴を記録する。
     * ステータス更新と履歴記録は1トランザクションで整合する。
     */
    public function changeStatus(Candidacy $candidacy, CandidacyStatus $to, int $changedBy): Candidacy
    {
        $from = $candidacy->status;

        if (! $this->canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "「{$from->label()}」から「{$to->label()}」への変更はできません。",
            ]);
        }

        return DB::transaction(function () use ($candidacy, $from, $to, $changedBy) {
            $changedAt = now();

            $this->repository->updateStatus($candidacy, $to);
            $this->repository->createStatusHistory($candidacy, $from, $to, $changedBy, $changedAt);

            return $candidacy;
        });
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
