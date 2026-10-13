<?php

namespace App\Services;

use App\Enums\NotificationSendStatus;
use App\Models\Notification;
use App\Notifications\CandidacyStatusChangedNotification;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use LogicException;
use Throwable;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $repository,
    ) {}

    /**
     * 選考ステータス変更の通知メールを応募者へ送信し、結果を記録する(REQ-011)。
     * 送信に失敗しても例外は外に出さず failed として記録する
     * (ステータス変更自体は呼び出し前に確定済みのため、業務処理を止めない)。
     * 送信対象は pending(初回送信)と failed(再送)のみ。skipped・sent は送信しない。
     *
     * @return bool 送信に成功したか
     */
    public function send(Notification $notification): bool
    {
        if (! in_array($notification->send_status, [NotificationSendStatus::Pending, NotificationSendStatus::Failed], true)) {
            throw new LogicException("送信対象外の通知です(id={$notification->id}, send_status={$notification->send_status->name})");
        }

        $notification->loadMissing(['candidacy.candidate', 'candidacy.jobPosting', 'statusHistory']);

        $candidate = $notification->candidacy->candidate;

        try {
            NotificationFacade::route('mail', [$candidate->email => $candidate->name])
                ->notify(new CandidacyStatusChangedNotification(
                    $notification->candidacy,
                    $notification->statusHistory->to_status,
                    $notification->cc_email,
                ));
        } catch (Throwable $e) {
            Log::error('選考ステータス変更通知メールの送信に失敗しました。', [
                'notification_id' => $notification->id,
                'exception' => $e,
            ]);

            $this->repository->updateSendStatus($notification, NotificationSendStatus::Failed);

            return false;
        }

        $this->repository->updateSendStatus($notification, NotificationSendStatus::Sent, now());

        return true;
    }
}
