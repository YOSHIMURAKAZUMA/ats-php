<?php

namespace App\Services;

use App\Models\Candidacy;
use App\Models\InterviewEvaluation;
use App\Repositories\EvaluationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(
        private readonly EvaluationRepository $repository,
    ) {}

    /**
     * 面接評価を保存する(REQ-009)
     * 同じ選考・面接官・ラウンドの評価があれば上書きし、なければ新規作成する。
     * 対象ラウンドは選考の現在ステータスから決まるため、送信されたラウンドと一致しない場合は拒否する。
     */
    public function store(
        Candidacy $candidacy,
        int $interviewerId,
        int $round,
        int $score,
        ?string $comment,
    ): InterviewEvaluation {
        if ($candidacy->status->round() !== $round) {
            throw ValidationException::withMessages([
                'round' => '選考ステータスが変更されたため、評価を保存できませんでした。',
            ]);
        }

        return $this->repository->updateOrCreate($candidacy, $interviewerId, $round, $score, $comment);
    }

    /**
     * SCR-05表示用に、選考に紐づく面接評価の一覧を取得する(REQ-010)。
     * 所感の閲覧判定(InterviewEvaluationPolicy@viewComment)を評価ごとに行っても
     * 追加のクエリが発生しないよう、選考と評価をメモリ上で相互に紐づけておく。
     *
     * @return Collection<int, InterviewEvaluation>
     */
    public function getForDisplay(Candidacy $candidacy): Collection
    {
        $evaluations = $this->repository->getByCandidacy($candidacy);

        // 選考 → 評価一覧(「自分が提出済みか」の判定で使う)
        $candidacy->setRelation('evaluations', $evaluations);

        // 評価 → 選考(全評価が同じ Candidacy オブジェクトを指すようにする)
        $evaluations->each(fn (InterviewEvaluation $e) => $e->setRelation('candidacy', $candidacy));

        return $evaluations;
    }
}
