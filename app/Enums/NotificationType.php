<?php

namespace App\Enums;

enum NotificationType: int
{
    case StatusChanged = 0; // 選考ステータス変更通知

    public function label(): string
    {
        return match ($this) {
            self::StatusChanged => '選考ステータス変更',
        };
    }
}
