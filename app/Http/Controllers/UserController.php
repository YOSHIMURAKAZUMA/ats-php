<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service,
        private readonly UserRepository $repository,
    ) {}

    /**
     * SCR-07 ユーザー一覧(REQ-013)
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = $this->repository->getAllForList();

        return view('users.index', compact('users'));
    }

    /**
     * SCR-07 ユーザー登録フォーム(REQ-013, REQ-019)
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    /**
     * ユーザーの登録処理(REQ-013, REQ-019)
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->service->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('status', "ユーザー「{$user->name}」を登録しました。");
    }

    /**
     * SCR-07 ユーザー編集フォーム(REQ-013, REQ-019)
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    /**
     * ユーザーの更新処理(REQ-013, REQ-019)
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated(), $request->user());

        return redirect()
            ->route('users.index')
            ->with('status', "ユーザー「{$user->name}」を更新しました。");
    }
}
