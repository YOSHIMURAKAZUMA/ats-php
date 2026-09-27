<?php

namespace App\Models;

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
        'type',
        'cc_email',
        'send_status',
        'sent_at',
        'created_at',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function candidacy(): BelongsTo
    {
        return $this->belongsTo(Candidacy::class);
    }
}
