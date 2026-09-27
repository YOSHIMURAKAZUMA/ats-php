<?php

namespace App\Repositories;

use App\Enums\CandidacyStatus;
use App\Models\Candidacy;
use App\Models\CandidacyStatusHistory;
use Illuminate\Database\Eloquent\Collection;

class CandidacyRepository
{
    /**
     * カンバン(SCR-04)表示用に選考を取得する。
     * カードに候補者名・求人名を出すため candidate と jobPosting を eager load。
     * 求人IDが指定されていればその求人で絞り込む(未指定なら全求人)。
     */
    public function getForBoard(?int $jobPostingId = null): Collection
    {
        return Candidacy::with(['candidate', 'jobPosting', 'interviewers'])->when($jobPostingId, fn ($query) => $query->where('job_posting_id', $jobPostingId))->latest()->get();
    }

    /**
     * 面接官の担当分のみに絞ったカンバン用の選考を取得する
     * (権限マトリクス:面接官は自分がアサインされた選考のみ参照可)。
     * 求人IDが指定されていれば、さらにその求人で絞り込む。
     */
    public function getForBoardAssignedTo(int $interviewerId, ?int $jobPostingId = null): Collection
    {
        return Candidacy::with(['candidate', 'jobPosting', 'interviewers'])->whereHas('interviewers', fn ($query) => $query->where('users.id', $interviewerId))->when($jobPostingId, fn ($query) => $query->where('job_posting_id', $jobPostingId))->latest()->get();
    }

    /**
     * 選考のステータスのみを更新する。
     */
    public function updateStatus(Candidacy $candidacy, CandidacyStatus $status): Candidacy
    {
        $candidacy->status = $status;
        $candidacy->save();

        return $candidacy;
    }

    /**
     * ステータス変更履歴を1件作成する。
     * changed_at は呼び出し側(Service)が明示的に渡す。
     */
    public function createStatusHistory(
        Candidacy $candidacy,
        ?CandidacyStatus $from,
        CandidacyStatus $to,
        int $changedBy,
        \DateTimeInterface $changedAt,
    ): CandidacyStatusHistory {
        return CandidacyStatusHistory::create([
            'candidacy_id' => $candidacy->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $changedBy,
            'changed_at' => $changedAt,
        ]);
    }

    /**
     * 指定選考・指定ラウンドの面接官を、渡されたリストで洗い替える(追加・削除を包含)。
     * 既存の同ラウンドの一旦削除し、新しいリストで入れ直す。
     * assigned_at は呼び出し側(Service)が渡した時刻を用いる。
     *
     * @param  array<int>  $interviewerIds
     */
    public function syncInterviewers(
        Candidacy $candidacy,
        int $round,
        array $interviewerIds,
        \DateTimeInterface $assignedAt,
    ): void {
        // 対象ラウンドの既存アサインを削除(他ラウンドには触れない)
        $candidacy->interviewers()
            ->wherePivot('round', $round)
            ->detach();

        // 新しいリストを attach(pivotに round と assigned_at を付与)
        $attach = [];
        foreach ($interviewerIds as $interviewerId) {
            $attach[$interviewerId] = [
                'round' => $round,
                'assigned_at' => $assignedAt,
            ];
        }

        $candidacy->interviewers()->attach($attach);
    }

    /**
     * SCR-05(応募者詳細)表示用に、選考を関連ごと1件取得する。
     * 候補者・対象求人・アサイン面接官・ステータス変更履歴(変更者付き)を eager load。
     * 存在しない場合は null。
     */
    public function findWithDetail(int $id): ?Candidacy
    {
        return Candidacy::with([
            'candidate',
            'jobPosting',
            'interviewers',
            'statusHistories.changedBy',
        ])->find($id);
    }
}
