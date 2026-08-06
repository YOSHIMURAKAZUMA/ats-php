<?php

namespace App\Services;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use App\Repositories\JobPostingRepository;
use Illuminate\Validation\ValidationException;

class JobPostingService
{
    public function __construct(
        private readonly JobPostingRepository $repository,
    ) {}

    /**
     * 許可されたステータス遷移の定義。
     * キー=現在ステータス / 値=遷移先として許可するステータスの配列。
     * (設計書「状態遷移定義」の求人票ステータス遷移に対応)
     */
    private const ALLOWED_TRANSITIONS = [
        JobPostingStatus::Draft->value => [JobPostingStatus::Published->value],
        JobPostingStatus::Published->value => [JobPostingStatus::Closed->value, JobPostingStatus::Draft->value],
        JobPostingStatus::Closed->value => [], // 終端。遷移不可
    ];

    /**
     * 求人票を新規作成する(登録直後は下書き)。
     *
     * @param  array{title: string, description: string}  $attributes
     */
    public function create(array $attributes, int $createdBy): JobPosting
    {
        // statusはDBデフォルト(0=下書き)に委ねるため、ここでは設定しない(REQ-001)
        return $this->repository->create($attributes, $createdBy);
    }

    /**
     * 求人票の内容を更新する(REQ-002)。
     *
     * @param  array{title: string, description: string}  $attributes
     */
    public function update(JobPosting $jobPosting, array $attributes): JobPosting
    {
        return $this->repository->update($jobPosting, $attributes);
    }

    /**
     * ステータスを変更する(REQ-003)。定義外の遷移は拒否する。
     */
    public function changeStatus(JobPosting $jobPosting, JobPostingStatus $to): JobPosting
    {
        $from = $jobPosting->status;

        if (! $this->canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "「{$from->label()}」から「{$to->label()}」への変更はできません。",
            ]);
        }

        return $this->repository->updateStatus($jobPosting, $to);
    }

    /**
     * from -> to の遷移が許可されているか。
     */
    private function canTransition(JobPostingStatus $from, JobPostingStatus $to): bool
    {
        return in_array($to->value, self::ALLOWED_TRANSITIONS[$from->value], true);
    }
}
