<?php

namespace App\Http\Controllers;

use App\Enums\CandidacyStatus;
use App\Enums\UserRole;
use App\Http\Requests\ManageInterviewersRequest;
use App\Http\Requests\UpdateCandidacyStatusRequest;
use App\Models\Candidacy;
use App\Models\JobPosting;
use App\Models\User;
use App\Repositories\CandidacyRepository;
use App\Services\CandidacyService;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CandidacyController extends Controller
{
    public function __construct(
        private readonly CandidacyService $service,
        private readonly CandidacyRepository $repository,
        private readonly EvaluationService $evaluationService,
    ) {}

    /**
     * SCR-04 応募者一覧(カンバン)(REQ-005)。
     * 採用担当者・管理者は全件、面接官は自分の担当分のみ表示する。
     * 求人フィルタ(任意)で絞り込み可能。
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Candidacy::class);

        $jobPostingId = $request->integer('job_posting_id') ?: null;
        $user = $request->user();

        // 面接官(かつ採用担当者・管理者ではない)は担当分のみ。それ以外は全件。
        $candidacies = $this->isInterviewerOnly($user) ? $this->repository->getForBoardAssignedTo($user->id, $jobPostingId) : $this->repository->getForBoard($jobPostingId);

        // Vue(カンバン)へ渡すデータ。必要な項目だけに絞って整形する
        $board = [
            'columns' => collect(CandidacyStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'defaultShown' => in_array($status, CandidacyStatus::boardColumns(), true),
            ])->all(),
            'candidacies' => $candidacies->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->candidate->name,
                'email' => $c->candidate->email,
                'status' => $c->status->value,
                'statusLabel' => $c->status->label(),
                'jobPostingId' => $c->job_positng_id,
                'jobPostingTitle' => $c->jobPosting->title,
                'entryDate' => $c->created_at->format('Y-m-d'),
                'showUrl' => route('candidacies.show', $c->id),
                'interviewers' => $c->interviewers->map(fn ($u) => [
                    'name' => $u->name,
                    'round' => $u->pivot->round,
                    'roundLabel' => CandidacyStatus::from($u->pivot->round)->label(),
                ])->all(),
            ])->all(),
            'jobPostings' => JobPosting::orderBy('id')->get()->map(fn ($j) => ['id' => $j->id, 'title' => $j->title])->all(),
            'selectedJobPostingId' => $jobPostingId,
        ];

        return view('candidacies.index', compact('board'));
    }

    /**
     * SCR-05 応募者詳細(REQ-006/010)。
     * 面接官は自分の担当分のみ閲覧可(CandidacyPolicy@view で制御)。
     * 存在しない選考は404。
     */
    public function show(Request $request, int $id): View
    {
        $candidacy = $this->repository->findWithDetail($id);

        if ($candidacy === null) {
            throw new NotFoundHttpException;
        }

        $this->authorize('view', $candidacy);

        // 現在ステータスから遷移可能なステータス(ステータス変更プルダウン用)
        $allowedStatuses = collect(CandidacyService::allowedNextValues($candidacy->status))
            ->map(fn ($value) => CandidacyStatus::from($value))
            ->all();

        // 現在のステータスに対応する面接ラウンド(面接ステータス以外は null)
        $currentRound = $candidacy->status->round();

        // 面接官アサインUI用のデータ(面接ラウンドがある場合のみ)
        $assignedInterviewers = [];
        $interviewerCandidates = [];

        if ($currentRound != null) {
            // このラウンドに現在アサインされている面接官
            $assignedInterviewers = $candidacy->interviewers
                ->filter(fn ($u) => $u->pivot->round === $currentRound)
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->values()
                ->all();

            // 面接官ロールを持つ有効なユーザー(アサイン候補)
            $interviewerCandidates = User::whereHas('roles', fn ($q) => $q->where('role', UserRole::Interviewer))
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->all();
        }

        // 面接評価一覧(REQ-010)。所感コメントは閲覧可能な場合のみ渡す(ブラインド評価)
        $user = $request->user();
        $evaluations = $this->evaluationService->getForDisplay($candidacy)
            ->map(function ($e) use ($user) {
                $canViewComment = $user->can('viewComment', $e);

                return [
                    'id' => $e->id,
                    'interviewerName' => $e->interviewer->name,
                    'roundLabel' => CandidacyStatus::from($e->round)->label(),
                    'score' => $e->score,
                    'createdAt' => $e->created_at->format('Y-m-d H:i'),
                    'canViewComment' => $canViewComment,
                    'comment' => $canViewComment ? $e->comment : null,
                ];
            })
            ->all();

        return view('candidacies.show', compact(
            'candidacy',
            'allowedStatuses',
            'currentRound',
            'assignedInterviewers',
            'interviewerCandidates',
            'evaluations',
        ));
    }

    /**
     * 選考ステータス変更(REQ-006/007)。
     * 認可・遷移バリデーションは UpdateCandidacyStatusRequest が担う。
     * ステータス更新と履歴記録は Service 側で1トランザクションで行う。
     * ※ メール通知(send_notification)の処理はタスク14で本実装する。
     */
    public function updateStatus(UpdateCandidacyStatusRequest $request, int $id): RedirectResponse
    {
        $candidacy = $this->repository->findWithDetail($id);

        if ($candidacy === null) {
            throw new NotFoundHttpException;
        }

        $to = CandidacyStatus::from($request->integer('status'));

        $this->service->changeStatus($candidacy, $to, $request->user()->id);

        return redirect()
            ->route('candidacies.show', $candidacy->id)
            ->with('status', '選考ステータスを変更しました。');
    }

    /**
     * 面接官アサイン更新(REQ-008)。
     * 認可・入力検証は ManageInterviewersRequest が担う。
     * 対象ラウンドの既存アサインを全部消して、送られたリストで入れ直す。
     */
    public function manageInterviewers(ManageInterviewersRequest $request, int $id): RedirectResponse
    {
        $candidacy = $this->repository->findWithDetail($id);

        if ($candidacy === null) {
            throw new NotFoundHttpException;
        }

        $this->service->manageInterviewers(
            $candidacy,
            $request->integer('round'),
            $request->input('interviewer_ids'),
        );

        return redirect()
            ->route('candidacies.show', $candidacy->id)
            ->with('status', '面接官のアサインを更新しました。');
    }

    /**
     * 履歴書PDFの表示(REQ-017)
     * 非公開ストレージ(local)に保存されたPDFを、認可チェックのうえ配信する。
     * 面接官は担当分のみ閲覧可(CandidacyPolicy@view で制御)
     */
    public function resume(int $id): BinaryFileResponse
    {
        $candidacy = $this->repository->findWithDetail($id);

        if ($candidacy === null) {
            throw new NotFoundHttpException;
        }

        $this->authorize('view', $candidacy);

        // 実ファイルが存在しない場合も404(DBにパスはあるがファイルが無いケース)
        if (! Storage::disk('local')->exists($candidacy->resume_path)) {
            throw new NotFoundHttpException;
        }

        // インライン表示(ブラウザ内でPDFを開く)。ダウンロードさせたい場合は download() を使う。
        return response()->file(
            Storage::disk('local')->path($candidacy->resume_path)
        );
    }

    /**
     * 面接官ロールのみを持つ(採用担当者・管理者ではない)ユーザーか。
     * 複数ロールの和集合方針により、採用担当者/管理者を兼ねる場合は全件閲覧とする。
     */
    private function isInterviewerOnly(User $user): bool
    {
        return $user->hasRole(UserRole::Interviewer) && ! $user->hasAnyRole([UserRole::Recruiter, UserRole::Admin]);
    }
}
