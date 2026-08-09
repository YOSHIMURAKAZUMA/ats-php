<?php

namespace App\Http\Controllers;

use App\Enums\JobPostingStatus;
use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Models\JobPosting;
use App\Repositories\JobPostingRepository;
use App\Services\JobPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function __construct(
        private readonly JobPostingService $service,
        private readonly JobPostingRepository $repository,
    ) {}

    /**
     * SCR-02 求人票一覧(REQ-002)
     */
    public function index(): View
    {
        $jobPostings = $this->repository->getAllForList();

        return view('job_postings.index', compact('jobPostings'));
    }

    /**
     * SCR-01 求人票登録フォーム(REQ-001)
     */
    public function create(): View
    {
        $this->authorize('create', JobPosting::class);

        return view('job_postings.create');
    }

    /**
     * 求人票の登録処理(REQ-001)
     */
    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user()->id);

        return redirect()
            ->route('job-postings.index')
            ->with('status', '求人票を登録しました(ステータス:下書き)。');
    }

    /**
     * SCR-02 求人票編集フォーム(REQ-002)
     */
    public function edit(JobPosting $jobPosting): View
    {
        $this->authorize('update', $jobPosting);

        return view('job_postings.edit', compact('jobPosting'));
    }

    /**
     * 求人票の更新処理(REQ-002)
     */
    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        $this->service->update($jobPosting, $request->validated());

        return redirect()
            ->route('job-posting.index')
            ->with('status', '求人票を更新しました。');
    }

    /**
     * ステータス変更処理(REQ-003)
     */
    public function updateStatus(Request $request, JobPosting $jobPosting)
    {
        $this->authorize('updateStatus', $jobPosting);

        $validated = $request->validate([
            'status' => ['required', 'integer', Rule::enum(JobPostingStatus::class)],
        ]);

        $to = JobPostingStatus::from($validated['status']);
        $this->service->changeStatus($jobPosting, $to);

        return redirect()
            ->route('job-postings.index')
            ->with('status', 'ステータスを変更しました。');
    }
}
