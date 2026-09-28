<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewEvaluation extends Model
{
    protected $fillable = [
        'candidacy_id',
        'interviewer_id',
        'round',
        'score',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'score' => 'integer',
        ];
    }

    public function candidacy(): BelongsTo
    {
        return $this->belongsTo(Candidacy::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
