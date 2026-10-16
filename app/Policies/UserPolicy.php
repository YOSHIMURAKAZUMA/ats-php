<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * ユーザーアカウントを管理(発行・編集・無効化)できるか。
     * 権限マトリクス:管理者のみ。
     */
    private function isAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    /**
     * ユーザー管理画面(SCR-07)の一覧を開けるか。
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * ユーザーを新規登録できるか(REQ-013)。
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * ユーザーを編集できるか(REQ-013, REQ-019)。
     * 自分自身の編集も可(自分から管理者の役割を外す操作はUpdateUserRequestで防ぐ)。
     */
    public function update(User $user, User $target): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * ユーザーを無効化できるか(REQ-013)。
     * 自分自身は無効化できない(操作者は有効な管理者なので、管理者が0人になることを防げる)。
     */
    public function deactivate(User $user, User $target): bool
    {
        return $this->isAdmin($user)
            && $user->id !== $target->id;
    }

    /**
     * 無効化したユーザーを再有効化できるか。
     */
    public function activate(User $user, User $target): bool
    {
        return $this->isAdmin($user);
    }
}
