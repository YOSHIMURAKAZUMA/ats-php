<?php

namespace App\Enums;

enum NotificationSendStatus: int
{
    case Pending = 0; // 送信待ち(通知レコード作成~送信結果が出るまで)
    case Sent = 1;    // 送信済み
    case Failed = 2;  // 送信失敗(再送の対象)
    case Skipped = 3; // 送信しない(採用担当者が確認ポップアップで選択)

    public function label(): string
    {
        return match ($this) {
            self::Pending => '送信待ち',
            self::Sent => '送信済み',
            self::Failed => '送信失敗',
            self::Skipped => '送信なし',
        };
    }
}
