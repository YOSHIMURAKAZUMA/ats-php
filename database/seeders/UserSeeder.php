<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => '管理太郎',
                'email' => 'admin@example.com',
                'roles' => [UserRole::Admin],
            ],
            [
                'name' => '採用一郎',
                'email' => 'recruiter@example.com',
                'roles' => [UserRole::Recruiter],
            ],
            [
                'name' => '面接花子',
                'email' => 'interviewer@example.com',
                'roles' => [UserRole::Interviewer],
            ],
            [
                'name' => '兼務次郎',
                'email' => 'both@example.com',
                'roles' => [UserRole::Recruiter, UserRole::Interviewer],
            ],
            [
                'name' => '無効三郎',
                'email' => 'inactive@example.com',
                'roles' => [UserRole::Interviewer],
                'is_active' => false,
            ],
        ];

        foreach ($accounts as $account) {
            $user = new User([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => Hash::make('password'),
            ]);
            // is_active は $fillable に含めないため、個別に代入する(既定値は true)
            $user->is_active = $account['is_active'] ?? true;
            $user->save();

            foreach ($account['roles'] as $role) {
                $user->roles()->create(['role' => $role]);
            }
        }
    }
}
