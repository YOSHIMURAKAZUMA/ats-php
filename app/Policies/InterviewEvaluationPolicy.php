<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InterviewEvaluation;
use App\Models\User;

class InterviewEvaluationPolicy
{
    /**
     * 所感コメントを閲覧できるか(REQ-010 ブラインド評価)。
     * 面接官は、担当ラウンドについては自分の評価提出後のみ他者の所感を閲覧可。
     */
    public function viewComment(User $user, InterviewEvaluation $evaluation): bool
    {
        $candidacy = $evaluation->candidacy;

        // 前提:その選考自体を閲覧できること(面接官は担当分のみ)
        if (! $user->can('view', $candidacy)) {
            return false;
        }

        // 採用担当者・管理者は常に全件閲覧可
        if ($user->hasAnyRole([UserRole::Recruiter, UserRole::Admin])) {
            return true;
        }

        // 自分の評価は常に閲覧可
        if ($evaluation->interviewer_id === $user->id) {
            return true;
        }

        // 自分が担当していないラウンドの評価は常時閲覧可
        if (! $candidacy->hasInterviewer($user->id, $evaluation->round)) {
            return true;
        }

        // 担当ラウンド:自分がそのラウンドの評価を提出済みなら閲覧可
        return $candidacy->evaluations->contains(
            fn (InterviewEvaluation $e) => $e->interviewer_id === $user->id && $e->round === $evaluation->round
        );
    }
}
