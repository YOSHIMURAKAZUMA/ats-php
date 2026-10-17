<?php

namespace Database\Factories;

use App\Enums\CandidacyStatus;
use App\Models\Candidacy;
use App\Models\Candidate;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Candidacy>
 */
class CandidacyFactory extends Factory
{
    /**
     * 既定は「公開中の求人票」への応募で、ステータスは「書類選考中」
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory()->published(),
            'candidate_id' => Candidate::factory(),
            'status' => CandidacyStatus::Screening,
            'resume_path' => 'resumes/'.Str::uuid().'.pdf',
        ];
    }

    /**
     * 指定した選考ステータスにする
     */
    public function withStatus(CandidacyStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
