<?php

namespace App\Enums;

enum UserRole: int
{
    case Admin = 0;
    case Recruiter = 1;
    case Interviewer = 2;

    public function label()
    {
        return match ($this) {
            self::Admin => '管理者',
            self::Recruiter => '採用担当者',
            self::Interviewer => '面接官',
        };
    }

    public static function fromName(string $name): self
    {
        return match (strtolower($name)) {
            'admin' => self::Admin,
            'recruiter' => self::Recruiter,
            'interviewer' => self::Interviewer,
            default => throw new \InvalidArgumentException("Unknown role name: {$name}"),
        };
    }

    /**
     * ログイン後の初期画面振り分けの優先順(REQ-019)
     *
     * @return array<self>
     */
    public static function priorityOrder(): array
    {
        return [self::Admin, self::Recruiter, self::Interviewer];
    }
}
