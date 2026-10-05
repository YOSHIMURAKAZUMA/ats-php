<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Candidacy;
use App\Models\User;

class CandidacyPolicy
{
    /**
     * 選考を管理(ステータス変更・面接官アサイン)できるか。
     * 権限マトリクス:採用担当者　または　管理者。
     */
    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([UserRole::Recruiter, UserRole::Admin]);
    }

    /**
     * 応募者一覧(SCR-04)を開けるか。
     * 採用担当者・管理者は全体、面接官は担当分のみ(絞り込みはController/Repositoryで実施)。
     * ここでは「一覧画面へのアクセス可否」のみを判定する。
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Recruiter,
            UserRole::Admin,
            UserRole::Interviewer,
        ]);
    }

    /**
     * 特定の選考(SCR-05)を閲覧できるか。
     * 採用担当者・管理者は全件可。面接官は自分がアサインされた選考のみ可(担当分のみ参照)。
     */
    public function view(User $user, Candidacy $candidacy): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        // 面接官は、その選考にアサインされている場合のみ閲覧可
        if ($user->hasRole(UserRole::Interviewer)) {
            return $candidacy->interviewers
                ->contains('id', $user->id);
        }

        return false;
    }

    /**
     * 選考ステータスを変更できるか(REQ-006)。
     */
    public function updateStatus(User $user, Candidacy $candidacy): bool
    {
        return $this->canManage($user);
    }

    /**
     * 面接官の割り当てを管理できるか(REQ-008)。
     * SCR-05でアサインリストをまとめて更新する洗い替え方式のため、
     * 追加・削除の両方をこの1つの権限でカバーする。
     */
    public function manageInterviewers(User $user, Candidacy $candidacy): bool
    {
        return $this->canManage($user);
    }

    /**
     * 面接評価の入力(SCR-06の表示・保存)
     * 面接官ロールを持ち、現在の選考ラウンドにアサインされている場合のみ可
     */
    public function evaluate(User $user, Candidacy $candidacy): bool
    {
        $round = $candidacy->status->round();

        return $round !== null
            && $user->hasRole(UserRole::Interviewer)
            && $candidacy->hasInterviewer($user->id, $round);
    }
}
