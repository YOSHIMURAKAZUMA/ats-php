<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * 役割は付けない(役割なしユーザー)。役割が必要なテストでは admin() などの state を使う
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * 管理者の役割を付ける
     */
    public function admin(): static
    {
        return $this->withRoles(UserRole::Admin);
    }

    /**
     * 採用担当者の役割を付ける
     */
    public function recruiter(): static
    {
        return $this->withRoles(UserRole::Recruiter);
    }

    /**
     * 面接官の役割を付ける
     */
    public function interviewer(): static
    {
        return $this->withRoles(UserRole::Interviewer);
    }

    /**
     * 無効化されたユーザーにする
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 指定した役割を user_roles に登録する
     *
     * user_roles は users とは別テーブルなので、ユーザーの保存後(afterCreating)に作る
     */
    public function withRoles(UserRole ...$roles): static
    {
        return $this->afterCreating(function (User $user) use ($roles) {
            foreach ($roles as $role) {
                $user->roles()->create(['role' => $role]);
            }
        });
    }
}
