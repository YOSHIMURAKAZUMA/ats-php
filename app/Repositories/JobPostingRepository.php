<?php

namespace App\Repositories;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Collection;

class JobPostingRepository
{
    /**
     * 一覧を作成日の新しい順で取得(SCR-02用)。作成者名も併せて取得。
     */
    public function getAllForList(): Collection
    {
        return JobPosting::with('creator')
            ->latest()
            ->get();
    }

    /**
     * 求人票を新規作成する。
     *
     * @param  array{title: string, description: string}  $attributes
     */
    public function create(array $attributes, int $createdBy): JobPosting
    {
        $jobPosting = new JobPosting($attributes);
        $jobPosting->created_by = $createdBy;
        $jobPosting->save();

        return $jobPosting;
    }

    /**
     * 求人票の内容(title/description)を更新する。
     *
     * @param  array{title: string, description: string}  $attributes
     */
    public function update(JobPosting $jobPosting, array $attributes): JobPosting
    {
        $jobPosting->update($attributes);

        return $jobPosting;
    }

    /**
     * ステータスのみ更新する。
     */
    public function updateStatus(JobPosting $jobPosting, JobPostingStatus $status): JobPosting
    {
        $jobPosting->status = $status;
        $jobPosting->save();

        return $jobPosting;
    }

    /**
     * 公開中の求人票のみを取得(SCR-09 / REQ-015)。新しい順。
     */
    public function getPublished(): Collection
    {
        return JobPosting::where('status', JobPostingStatus::Published)->latest()->get();
    }

    /**
     * 公開中の求人票を1件取得。非公開(下書き・募集終了)なら null(SCR-10 / REQ-016)。
     */
    public function findPublished(int $id): ?JobPosting
    {
        return JobPosting::where('status', JobPostingStatus::Published)->find($id);
    }
}
