<?php

namespace App\Models;

use App\Enums\CandidacyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidacyStatusHistory extends Model
{
    // このテーブルは changed_at のみを持ち、created_at/updated_at を使わない
    public $timestamps = false;

    protected $fillable = [
        'candidacy_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'from_status' => CandidacyStatus::class,
        'to_status' => CandidacyStatus::class,
        'changed_at' => 'datetime',
    ];

    public function candidacy(): BelongsTo
    {
        return $this->belongsTo(Candidacy::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
