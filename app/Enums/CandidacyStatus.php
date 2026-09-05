<?php

namespace App\Enums;

enum CandidacyStatus: int
{
    // 選考方法(0番台)
    case Screening = 0;       // 書類選考中
    case FirstInterview = 1;  // 一次面接
    case SecondInterview = 2; // 二次面接

    // 選考結果(1x番台)
    case Offer = 11;          // 内定
    case OfferAccepted = 12;  // 内定承諾
    case OfferDeclined = 13;  // 内定辞退
    case Rejected = 14;       // 不合格

    public function label(): string
    {
        return match ($this) {
            self::Screening => '書類選考中',
            self::FirstInterview => '一次面接',
            self::SecondInterview => '二次面接',
            self::Offer => '内定',
            self::OfferAccepted => '内定承諾',
            self::OfferDeclined => '内定辞退',
            self::Rejected => '不合格',
        };
    }
}
