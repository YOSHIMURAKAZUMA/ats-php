<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    /**
     * 求人票を操作(作成・編集・公開切替)できるか。
     * 権限マトリクス:採用担当者　または　管理者。
     */
    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Recruiter, UserRole::Admin]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $this->canManage($user);
    }

    public function updateStatus(User $user, JobPosting $jobPosting): bool
    {
        return $this->canManage($user);
    }
}
