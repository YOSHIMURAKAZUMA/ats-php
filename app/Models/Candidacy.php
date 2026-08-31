<?php

namespace App\Models;

use App\Enums\CandidacyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidacy extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'candidate_id',
        'status',
        'resume_path',
    ];

    protected $casts = [
        'status' => CandidacyStatus::class,
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
