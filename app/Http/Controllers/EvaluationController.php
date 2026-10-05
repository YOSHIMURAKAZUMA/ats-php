<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluationRequest;
use App\Repositories\CandidacyRepository;
use App\Repositories\EvaluationRepository;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationService $service,
        private readonly EvaluationRepository $repository,
        private readonly CandidacyRepository $candidacyRepository,
    ) {}

    /**
     * SCR-06 面接評価入力(REQ-009)。
     * 現在の選考ラウンドにアサインされた面接官のみ表示可(CandidacyPolicy@evaluate で制御)。
     * 入力済みの評価があれば初期表示し、上書き編集できるようにする。
     */
    public function create(Request $request, int $id): View
    {
        $candidacy = $this->candidacyRepository->findWithDetail($id);

        if ($candidacy === null) {
            throw new NotFoundHttpException;
        }

        $this->authorize('evaluate', $candidacy);

        // 対象ラウンドは現在ステータスから決まる(認可を通過していれば必ず面接ラウンド)
        $round = $candidacy->status->round();

        // ログイン中の面接官自身の、このラウンドの入力済み評価(未入力なら null)
        $evaluation = $this->repository->findByInterviewerAndRound($candidacy, $request->user()->id, $round);

        return view('evaluations.create', compact(
            'candidacy',
            'round',
            'evaluation',
        ));
    }

    /**
     * 面接評価の保存(REQ-009)。
     * 認可・入力検証は StoreEvaluationRequest が担う。
     * 同じラウンドの自分の評価があれば上書きする。
     */
    public function store(StoreEvaluationRequest $request, int $id): RedirectResponse
    {
        $candidacy = $this->candidacyRepository->findWithDetail($id);

        if ($candidacy === null) {
            throw new NotFoundHttpException;
        }

        $this->service->store(
            $candidacy,
            $request->user()->id,
            $request->integer('round'),
            $request->integer('score'),
            $request->input('comment'),
        );

        return redirect()
            ->route('candidacies.show', $candidacy->id)
            ->with('status', '面接評価を保存しました。');
    }
}
