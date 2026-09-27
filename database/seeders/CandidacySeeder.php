<?php

namespace Database\Seeders;

use App\Enums\CandidacyStatus;
use App\Models\Candidacy;
use App\Models\CandidacyStatusHistory;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CandidacySeeder extends Seeder
{
    /** 全選考が共有するダミー履歴書のパス */
    private const DUMMY_RESUME_PATH = 'resumes/dummy_resume.pdf';

    public function run(): void
    {
        // 依存データの確認(UserSeeder / JobPostingSeeder が先に必要)
        $recruiter = User::where('email', 'recruiter@example.com')->first();
        $interviewer = User::where('email', 'interviewer@example.com')->first();
        $both = User::where('email', 'both@example.com')->first();

        if ($recruiter === null || $interviewer === null || $both === null) {
            $this->command->warn('テストユーザーがみつかりません。先に UserSeeder を実行してください。');

            return;
        }

        $jobPostings = JobPosting::orderBy('id')->get();

        if ($jobPostings->count() < 3) {
            $this->command->warn('求人票が不足しています。先に JobPostingSeeder を実行してください。');

            return;
        }

        // 求人を分かりやすい変数に割り当て(1:公開/2:下書き/3:募集終了)
        $backend = $jobPostings[0]; // バックエンドエンジニア(公開)
        $frontend = $jobPostings[1]; // フロントエンジニア(下書き)

        // ダミー履歴書PDFを非公開ストレージに作成(REQ-017の表示確認用)
        $this->createDummyResume();

        // 候補者と選考のデータ定義
        $entries = [
            ['name' => '山田 太郎', 'email' => 'yamada@example.com', 'phone' => '090-1111-0001', 'job' => $backend, 'status' => CandidacyStatus::Screening, 'interviewers' => []],
            ['name' => '佐藤 次郎', 'email' => 'sato@example.com', 'phone' => '090-1111-0002', 'job' => $backend, 'status' => CandidacyStatus::Screening, 'interviewers' => []],
            ['name' => '鈴木 一郎', 'email' => 'suzuki@example.com', 'phone' => '090-1111-0003', 'job' => $backend, 'status' => CandidacyStatus::FirstInterview, 'interviewers' => [1 => [$interviewer, $both]]],
            ['name' => '高橋 三郎', 'email' => 'takahashi@example.com', 'phone' => null, 'job' => $backend, 'status' => CandidacyStatus::FirstInterview, 'interviewers' => [1 => [$interviewer]]],
            ['name' => '田中 花子', 'email' => 'tanaka@example.com', 'phone' => '090-1111-0005', 'job' => $frontend, 'status' => CandidacyStatus::SecondInterview, 'interviewers' => [2 => [$both]]],
            ['name' => '伊藤 五郎', 'email' => 'ito@example.com', 'phone' => '090-1111-0006', 'job' => $frontend, 'status' => CandidacyStatus::Offer, 'interviewers' => [1 => [$interviewer], 2 => [$both]]],
            ['name' => '渡辺 明', 'email' => 'watanabe@example.com', 'phone' => null, 'job' => $backend, 'status' => CandidacyStatus::OfferAccepted, 'interviewers' => []],
            ['name' => '小林 真里', 'email' => 'kobayashi@example.com', 'phone' => '090-1111-0008', 'job' => $frontend, 'status' => CandidacyStatus::OfferDeclined, 'interviewers' => []],
            ['name' => '加藤 亮', 'email' => 'kato@example.com', 'phone' => '090-1111-0009', 'job' => $backend, 'status' => CandidacyStatus::Rejected, 'interviewers' => [1 => [$interviewer]]],
        ];

        foreach ($entries as $index => $data) {
            // エントリー日を少しずつずらす(新しい順の並びを確認できるように)
            $entryDate = now()->subDays(30 - $index * 2);

            $candidate = new Candidate([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);
            $candidate->created_at = $entryDate;
            $candidate->save();

            $candidacy = new Candidacy([
                'job_posting_id' => $data['job']->id,
                'candidate_id' => $candidate->id,
                'resume_path' => self::DUMMY_RESUME_PATH,
            ]);
            $candidacy->status = $data['status'];
            $candidacy->created_at = $entryDate;
            $candidacy->updated_at = $entryDate;
            $candidacy->save();

            // 面接官アサイン(ラウンド別)
            foreach ($data['interviewers'] as $round => $users) {
                foreach ($users as $user) {
                    $candidacy->interviewers()->attach($user->id, [
                        'round' => $round,
                        'assigned_at' => $entryDate->copy()->addDays(1),
                    ]);
                }
            }
            // 現在ステータスに至るまでのステータス変更履歴
            $this->createHistories($candidacy, $data['status'], $recruiter->id, $entryDate);
        }

        $this->command->info('選考データを '.count($entries).'件作成しました。');
    }

    /**
     * 現在のステータスに至るまでの遷移経路を、状態遷移定義に沿って履歴として作成する。
     * 例)内定(11)なら 0->1, 1->2, 2->11 の3件を作る。
     */
    private function createHistories(
        Candidacy $candidacy,
        CandidacyStatus $current,
        int $changedBy,
        Carbon $entryDate,
    ): void {
        // 現在ステータスごとの遷移経路(from→to の連なり)
        $paths = [
            CandidacyStatus::Screening->value => [],
            CandidacyStatus::FirstInterview->value => [
                [CandidacyStatus::Screening, CandidacyStatus::FirstInterview],
            ],
            CandidacyStatus::SecondInterview->value => [
                [CandidacyStatus::Screening, CandidacyStatus::FirstInterview],
                [CandidacyStatus::FirstInterview, CandidacyStatus::SecondInterview],
            ],
            CandidacyStatus::Offer->value => [
                [CandidacyStatus::Screening, CandidacyStatus::FirstInterview],
                [CandidacyStatus::FirstInterview, CandidacyStatus::SecondInterview],
                [CandidacyStatus::SecondInterview, CandidacyStatus::Offer],
            ],
            CandidacyStatus::OfferAccepted->value => [
                [CandidacyStatus::Screening, CandidacyStatus::FirstInterview],
                [CandidacyStatus::FirstInterview, CandidacyStatus::SecondInterview],
                [CandidacyStatus::SecondInterview, CandidacyStatus::Offer],
                [CandidacyStatus::Offer, CandidacyStatus::OfferAccepted],
            ],
            CandidacyStatus::OfferDeclined->value => [
                [CandidacyStatus::Screening, CandidacyStatus::FirstInterview],
                [CandidacyStatus::FirstInterview, CandidacyStatus::SecondInterview],
                [CandidacyStatus::SecondInterview, CandidacyStatus::Offer],
                [CandidacyStatus::Offer, CandidacyStatus::OfferDeclined],
            ],
            CandidacyStatus::Rejected->value => [
                [CandidacyStatus::Screening, CandidacyStatus::FirstInterview],
                [CandidacyStatus::FirstInterview, CandidacyStatus::Rejected],
            ],
        ];

        foreach ($paths[$current->value] as $step => [$from, $to]) {
            CandidacyStatusHistory::create([
                'candidacy_id' => $candidacy->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $changedBy,
                // エントリー日の２日後から、1ステップごとに３日ずつ進める
                'changed_at' => $entryDate->copy()->addDays(2 + $step * 3),
            ]);
        }
    }

    /**
     * 最小構成のダミーPDFを非公開ストレージに作成する。
     * 全選考が同じファイルを参照し、REQ-017(履歴書閲覧)の表示確認に使う。
     */
    private function createDummyResume(): void
    {
        if (Storage::disk('local')->exists(self::DUMMY_RESUME_PATH)) {
            return; // 既にあれば作り直さない。
        }

        Storage::disk('local')->put(self::DUMMY_RESUME_PATH, $this->dummyPdfContent());
    }

    /**
     * 1ページに短いテキストだけを持つ最小構成のPDFバイナリを組み立てる。
     * (日本語はフォント埋め込みが必要になるため英数字のみ)
     */
    private function dummyPdfContent(): string
    {
        $text = 'Dummy Resume for Testing';

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '.'/Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>';
        $stream = "BT /F1 18 Tf 72 760 Td ({$text}) Tj ET";
        $objects[4] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($objects as $num => $body) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$num]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }
}
