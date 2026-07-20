<?php

namespace App\Models;

use App\Enums\UserRole as UserRoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    public $timestamps = false;

    protected $fillable = ['role'];

    protected $casts = [
        'role' => UserRoleEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
