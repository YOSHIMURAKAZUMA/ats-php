<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

class AuthRedirectService
{
    /**
     * REQ-019: 複数ロール保持時は admin > recruiter > interviewer の優先順で
     * ログイン後の初期画面を決定する。
     *
     * NOTE: 各画面はタスク9以降で実装するため、現時点ではパス直置き。
     *       画面実装後に名前付きルート(route()ヘルパー)へ置き換える。
     */
    public function initialPathFor(User $user): string
    {
        return match ($user->primaryRole()) {
            UserRole::Admin => '/users',
            UserRole::Recruiter => '/job-postings',
            UserRole::Interviewer => '/candidacies',
            // ロール未設定のユーザーはLoginRequestで弾いているため、通常ここには到達しない。
            // 万が一到達した場合に備え、ログアウト用のパスへ逃がす。
            default => '/',
        };
    }
}
