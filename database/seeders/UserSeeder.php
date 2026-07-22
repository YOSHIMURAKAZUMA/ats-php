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
        ];

        foreach ($accounts as $account) {
            $user = User::create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => Hash::make('password'),
            ]);

            foreach ($account['roles'] as $role) {
                $user->roles()->create(['role' => $role]);
            }
        }
    }
}
