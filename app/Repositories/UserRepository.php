<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    /**
     * ユーザー管理画面(SCR-07)の一覧用に、全ユーザーを取得する。
     * 役割列を表示するため roles を eager load する。
     */
    public function getAllForList(): Collection
    {
        return User::with('roles')->orderBy('id')->get();
    }

    /**
     * ユーザーを1件作成する。
     * password は User の casts('hashed')により保存時に自動でハッシュ化される。
     *
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    /**
     * ユーザーの基本情報(氏名・メール・パスワード)を更新する。
     * 渡されたキーだけを更新する(パスワードを変えない場合は呼び出し側でキーを除く)。
     *
     * @param  array{name?: string, email?: string, password?: string}  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user;
    }

    /**
     * ユーザーの役割を、渡されたリストで洗い替える(REQ-019)。
     * 既存の役割をすべて削除し、新しいリストで入れ直す。
     * user_roles は「現在の状態」を表すため物理削除してよい(テーブル定義書 削除方針)。
     *
     * @param  array<UserRole>  $roles
     */
    public function syncRoles(User $user, array $roles): void
    {
        // 既存の役割をすべて削除
        $user->roles()->delete();

        // 新しいリストで作成(created_at はDBの useCurrent() で自動設定)
        $user->roles()->createMany(
            array_map(fn (UserRole $role) => ['role' => $role], $roles)
        );

        // 読み込み済みの roles が古いままにならないよう、キャッシュを破棄する
        $user->unsetRelation('roles');
    }

    /**
     * ユーザーの有効/無効を切り替える(REQ-013)。
     * is_active は $fillable に含めず、この専用メソッドでのみ変更する。
     */
    public function updateActive(User $user, bool $isActive): User
    {
        $user->is_active = $isActive;
        $user->save();

        return $user;
    }

    /**
     * 指定ユーザーのログイン中セッションをすべて削除する(無効化時の即時ログアウト)。
     * SESSION_DRIVER=database のため sessions テーブルから行を削除する。
     *
     * @return int 削除したセッション数
     */
    public function deleteSessions(User $user): int
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
    }
}
