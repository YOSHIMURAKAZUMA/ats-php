<?php

namespace App\Repositories;

use App\Enums\NotificationSendStatus;
use App\Enums\NotificationType;
use App\Models\Candidacy;
use App\Models\CandidacyStatusHistory;
use App\Models\Notification;

class NotificationRepository
{
    /**
     * ステータス変更に対応する通知レコードを1件作成する(REQ-011)。
     * 送信する場合は pending、送信しない場合は skipped で作成する。
     * created_at(=ステータス変更操作の日時)は呼び出し側(Service)が渡す。
     */
    public function createForStatusChange(
        Candidacy $candidacy,
        CandidacyStatusHistory $statusHistory,
        NotificationSendStatus $sendStatus,
        ?string $ccEmail,
        \DateTimeInterface $createdAt,
    ): Notification {
        return Notification::create([
            'candidacy_id' => $candidacy->id,
            'candidacy_status_history_id' => $statusHistory->id,
            'type' => NotificationType::StatusChanged,
            'cc_email' => $ccEmail,
            'send_status' => $sendStatus,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * 通知の送信ステータスを更新する。
     * 送信成功時は送信日時もあわせて記録する(失敗時は null のまま)。
     */
    public function updateSendStatus(
        Notification $notification,
        NotificationSendStatus $sendStatus,
        ?\DateTimeInterface $sentAt = null,
    ): Notification {
        $notification->send_status = $sendStatus;
        $notification->sent_at = $sentAt;
        $notification->save();

        return $notification;
    }
}
