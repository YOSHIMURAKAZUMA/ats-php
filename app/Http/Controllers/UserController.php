<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
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
}
