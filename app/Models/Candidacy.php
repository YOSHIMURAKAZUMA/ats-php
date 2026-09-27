<?php

namespace App\Models;

use App\Enums\CandidacyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // アサインされた面接官(candidacy_interviewers 中間テーブル経由)。
    // round・assigned_at を中間テーブルの追加属性として取得する
    public function interviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'candidacy_interviewers', 'candidacy_id', 'interviewer_id')->withPivot(['round', 'assigned_at']);
    }

    // ステータス変更履歴。新しい順で扱いたいので changed_at 降順をデフォルトに
    public function statusHistories(): HasMany
    {
        return $this->hasMany(CandidacyStatusHistory::class)->orderByDesc('changed_at');
    }

    // 送信(または送信スキップ)された通知の記憶
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
