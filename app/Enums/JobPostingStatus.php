<?php

namespace App\Enums;

enum JobPostingStatus: int
{
    case Draft = 0;      // 下書き
    case Published = 1;  // 公開
    case Closed = 2;     // 募集終了

    public function label(): string
    {
        return match ($this) {
            self::Draft => '下書き',
            self::Published => '公開',
            self::Closed => '募集終了',
        };
    }
}
