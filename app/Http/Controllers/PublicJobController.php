<?php

namespace App\Http\Controllers;

use App\Repositories\JobPostingRepository;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicJobController extends Controller
{
    public function __construct(
        private readonly JobPostingRepository $repository,
    ) {}

    /**
     * SCR-09 公開求人一覧(REQ-015)。未ログインでも閲覧可。
     */
    public function index(): View
    {
        $jobPostings = $this->repository->getPublished();

        return view('public.jobs.index', compact('jobPostings'));
    }

    /**
     * SCR-10 求人詳細(REQ-016)。公開中のみ。非公開・存在しないIDは404。
     */
    public function show(int $id): View
    {
        $jobPosting = $this->repository->findPublished($id);

        if ($jobPosting === null) {
            throw new NotFoundHttpException;
        }

        return view('public.jobs.show', compact('jobPosting'));
    }
}
