<?php

namespace App\Models;

use App\Enums\NotificationSendStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    // created_at は持つが updated_at は持たないため、自動タイムスタンプはオフにし
    // created_at は $fillable で明示的にセットする運用にする
    public $timestamps = false;

    protected $fillable = [
        'candidacy_id',
        'candidacy_status_history_id',
        'type',
        'cc_email',
        'send_status',
        'sent_at',
        'created_at',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'send_status' => NotificationSendStatus::class,
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function candidacy(): BelongsTo
    {
        return $this->belongsTo(Candidacy::class);
    }

    // この通知の対象となったステータス変更(変更前後のステータス・変更者を辿る)
    public function statusHistory(): BelongsTo
    {
        return $this->belongsTo(CandidacyStatusHistory::class, 'candidacy_status_history_id');
    }
}
