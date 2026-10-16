<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
    ) {}

    /**
     * ユーザーを新規登録する(REQ-013, REQ-019)。
     * ユーザー本体と役割を1トランザクションで保存する(役割0件のユーザーを残さないため)。
     *
     * @param  array{name: string, email: string, password: string, roles: array<int|string>}  $validated
     */
    public function create(array $validated): User
    {
        $roles = $this->toRoles($validated['roles']);

        return DB::transaction(function () use ($validated, $roles) {
            $user = $this->repository->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $this->repository->syncRoles($user, $roles);

            return $user;
        });
    }

    /**
     * ユーザーを更新する(REQ-013, REQ-019)。
     * パスワードは入力があった場合のみ変更する(空欄なら変更しない)。
     * 操作者が自分自身から管理者の役割を外すことは禁止する(管理者不在の防止)。
     *
     * @param  array{name: string, email: string, password: ?string, roles: array<int|string>}  $validated
     */
    public function update(User $user, array $validated, User $operator): User
    {
        $roles = $this->toRoles($validated['roles']);

        if ($user->id === $operator->id && ! in_array(UserRole::Admin, $roles, true)) {
            throw ValidationException::withMessages([
                'roles' => '自分自身の管理者の役割は外せません。',
            ]);
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (filled($validated['password'] ?? null)) {
            $attributes['password'] = $validated['password'];
        }

        return DB::transaction(function () use ($user, $attributes, $roles) {
            $this->repository->update($user, $attributes);
            $this->repository->syncRoles($user, $roles);

            return $user;
        });
    }

    /**
     * ユーザーを無効化する(REQ-013)。
     * 無効化と同時に既存セッションを削除し、ログイン中であっても即時にログアウトさせる。
     * (自分自身の無効化は UserPolicy::deactivate で禁止済み)
     */
    public function deactivate(User $user): void
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'user' => "「{$user->name}」は既に無効化されています。",
            ]);
        }

        DB::transaction(function () use ($user) {
            $this->repository->updateActive($user, false);
            $this->repository->deleteSessions($user);
        });
    }

    /**
     * 無効化したユーザーを再有効化する。
     */
    public function activate(User $user): void
    {
        if ($user->is_active) {
            throw ValidationException::withMessages([
                'user' => "「{$user->name}」は既に有効です。",
            ]);
        }

        $this->repository->updateActive($user, true);
    }

    /**
     * フォームから送られた役割の値(0/1/2)を enum の配列に変換する。
     *
     * @param  array<int|string>  $values
     * @return array<UserRole>
     */
    private function toRoles(array $values): array
    {
        return array_map(
            fn ($value) => UserRole::from((int) $value),
            array_values(array_unique($values)),
        );
    }
}
