<?php

namespace Database\Seeders;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobPostingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 作成者となる採用担当者(UserSeederで作成済み)
        $recruiter = User::where('email', 'recruiter@example.com')->first();

        if ($recruiter === null) {
            $this->command->warn('recruiter@example.com が見つかりません。先に UserSeeder を実行してください。');

            return;
        }

        $jobPostings = [
            [
                'title' => 'バックエンドエンジニア(PHP/Laravel)',
                'description' => "【業務内容】\n自社サービスのサーバーサイド開発をお任せします。\n\n【必須スキル】\nPHP/Laravelでの開発経験2年以上\n\n【歓迎スキル】\nAWSの利用経験、チーム開発経験",
                'status' => JobPostingStatus::Published,
            ],
            [
                'title' => 'フロントエンジニア(Vue.js)',
                'description' => "【業務内容】\nVue3を用いたSPA開発。\n\n【必須スキル】\nJavaScript/TypeScriptの実務経験\n\n【歓迎スキル】\nVue3 Composition APIの利用経験",
                'status' => JobPostingStatus::Draft,
            ],
            [
                'title' => 'インフラエンジニア(AWS)',
                'description' => '【業務内容】\nAWS上のインフラ設計・構築・運用。\n\n【必須スキル】\nAWSでの構築経験\n\n【歓迎スキル】\nTerraformによるIaC経験',
                'status' => JobPostingStatus::Closed,
            ],
        ];

        foreach ($jobPostings as $data) {
            $jobPosting = new JobPosting([
                'title' => $data['title'],
                'description' => $data['description'],
            ]);
            $jobPosting->created_by = $recruiter->id;
            $jobPosting->status = $data['status'];
            $jobPosting->save();
        }
    }
}
