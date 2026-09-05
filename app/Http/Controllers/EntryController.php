<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryRequest;
use App\Repositories\JobPostingRepository;
use App\Services\EntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntryController extends Controller
{
    public function __construct(
        private readonly JobPostingRepository $jobPostingRepository,
        private readonly EntryService $entryService,
    ) {}

    /**
     * SCR-03 応募エントリーフォーム表示(REQ-004)。公開求人のみ。
     */
    public function create(int $id): View
    {
        $jobPosting = $this->jobPostingRepository->findPublished($id);

        if ($jobPosting === null) {
            throw new NotFoundHttpException;
        }

        return view('public.entries.create', compact('jobPosting'));
    }

    /**
     * エントリー登録処理(REQ-004)。公開求人のみ。
     */
    public function store(StoreEntryRequest $request, int $id): RedirectResponse
    {
        $jobPosting = $this->jobPostingRepository->findPublished($id);

        if ($jobPosting === null) {
            throw new NotFoundHttpException;
        }

        $this->entryService->entry(
            $jobPosting->id,
            $request->safe()->only(['name', 'email', 'phone']),
            $request->file('resume'),
        );

        return redirect()
            ->route('public.entries.complete', $jobPosting->id)
            ->with('status', '応募を受け付けました。');
    }

    /**
     * 応募完了画面(REQ-004)。
     */
    public function complete(int $id): View
    {
        $jobPosting = $this->jobPostingRepository->findPublished($id);

        if ($jobPosting === null) {
            throw new NotFoundHttpException;
        }

        return view('public.entries.complete', compact('jobPosting'));
    }
}
