<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

class AuthRedirectService
{
    /**
     * REQ-019: 複数ロール保持時は admin > recruiter > interviewer の優先順で
     * ログイン後の初期画面を決定する。
     */
    public function initialPathFor(User $user): string
    {
        return match ($user->primaryRole()) {
            UserRole::Admin => route('users.index'),
            UserRole::Recruiter => route('job-postings.index'),
            UserRole::Interviewer => route('candidacies.index'),
            // ロール未設定のユーザーはLoginRequestで弾いているため、通常ここには到達しない。
            // 万が一到達した場合に備え、公開トップへ逃がす。
            default => '/',
        };
    }
}
