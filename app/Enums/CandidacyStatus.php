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

    /**
     * カンバン(SCR-04)に列として表示する選考ステータスを、表示順に返す。
     *
     * 内定承諾(12)・内定辞退(13)はドラッグ&ドロップの対象にせず、
     * SCR-05のプルダウンで変更する方針のため、ここには含めない。
     */
    public static function boardColumns(): array
    {
        return [
            self::Screening,
            self::FirstInterview,
            self::SecondInterview,
            self::Offer,
            self::Rejected,
        ];
    }

    /**
     * このステータスが対応する面接ラウンド番号を返す。
     * 一次面接=1 / 二次面接=2 / それ以外=null。
     *
     * ステータスコードは「選考方法(0番台)」の帯で管理され、面接ステータスの
     * case値=ラウンド番号が一致するよう設計されている(設計書「状態遷移定義」参照)。
     * そのため面接ステータスは $this->value をそのままラウンド番号として返せる。
     */
    public function round(): ?int
    {
        return match ($this) {
            self::FirstInterview,
            self::SecondInterview => $this->value,
            default => null,
        };
    }

    /**
     * 面接官アサインの対象となる面接ラウンドの値を配列で返す(例:[1,2])。
     * round() を唯一の情報源として面接ステータスのラウンド番号だけを集約する。
     * 将来ラウンドを追加(例: 三次面接=3)しても round() 側の対応だけで自動追従する。
     *
     * @return array<int>
     */
    public static function interviewRounds(): array
    {
        return array_values(array_filter(
            array_map(fn ($case) => $case->round(), self::cases())
        ));
    }

    /**
     * 終端ステータス(そこから先の遷移が発生しない)かどうかを返す。
     * 内定承諾(12)・内定辞退(13)・不合格(14)が終端。
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::OfferAccepted,
            self::OfferDeclined,
            self::Rejected => true,
            default => false,
        };
    }
}
