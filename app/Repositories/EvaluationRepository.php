<?php

namespace App\Repositories;

use App\Models\Candidacy;
use App\Models\InterviewEvaluation;
use Illuminate\Database\Eloquent\Collection;

class EvaluationRepository
{
    /**
     * 選考に紐づく面接評価の一覧(評価者を含む。ラウンド順→登録順)
     *
     * @return Collection<int, InterviewEvaluation>
     */
    public function getByCandidacy(Candidacy $candidacy): Collection
    {
        return $candidacy->evaluations()->with('interviewer')->get();
    }

    /**
     * 指定の面接官・ラウンドの評価を1件取得(未入力ならnull)
     */
    public function findByInterviewerAndRound(Candidacy $candidacy, int $interviewerId, int $round): ?InterviewEvaluation
    {
        return $candidacy->evaluations()
            ->where('interviewer_id', $interviewerId)
            ->where('round', $round)
            ->first();
    }

    /**
     * 評価を保存する
     * 同じ選考×面接官×ラウンドの評価があれば更新、なければ作成する。
     */
    public function updateOrCreate(
        Candidacy $candidacy,
        int $interviewerId,
        int $round, int $score,
        ?string $comment
    ): InterviewEvaluation {
        return InterviewEvaluation::updateOrCreate(
            [
                'candidacy_id' => $candidacy->id,
                'interviewer_id' => $interviewerId,
                'round' => $round,
            ],
            [
                'score' => $score,
                'comment' => $comment,
            ],
        );
    }
}
