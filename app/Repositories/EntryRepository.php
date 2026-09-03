<?php

namespace App\Repositories;

use App\Enums\CandidacyStatus;
use App\Models\Candidacy;
use App\Models\Candidate;

class EntryRepository
{
    /**
     * メールアドレスで候補者を取得、なければ作成(1人=1candidate行)。
     *
     * @param  array{name: string, email: string, phone: ?string}  $attributes
     */
    public function firstOrCreateCandidate(array $attributes): Candidate
    {
        return Candidate::firstOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'phone' => $attributes['phone'] ?? null,
            ],
        );
    }

    /**
     * 選考(応募)を作成する。
     */
    public function createCandidacy(int $jobPostingId, int $candidateId, string $resumePath): Candidacy
    {
        return Candidacy::create([
            'job_posting_id' => $jobPostingId,
            'candidate_id' => $candidateId,
            'status' => CandidacyStatus::Screening,
            'resume_path' => $resumePath,
        ]);
    }
}
