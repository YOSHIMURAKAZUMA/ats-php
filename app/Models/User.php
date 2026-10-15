<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole as UserRoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * モデルの既定値。
     * DBの既定値(is_active=true)と揃え、create()直後のモデルでも is_active を正しく参照できるようにする。
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasRole(UserRoleEnum $role): bool
    {
        return $this->roles->contains(fn ($r) => $r->role === $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->contains(fn ($r) => in_array($r->role, $roles, true));
    }

    public function primaryRole(): ?UserRoleEnum
    {
        $owned = $this->roles->pluck('role');

        foreach (UserRoleEnum::priorityOrder() as $role) {
            if ($owned->contains($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * 一覧表示用に、保持している役割の表示名をカンマ区切りで返す(例:「採用担当者, 面接官」)。
     * 並び順は enum の値の順(管理者→採用担当者→面接官)にそろえる。
     */
    public function roleLabels(): string
    {
        return $this->roles
            ->sortBy(fn (UserRole $userRole) => $userRole->role->value)
            ->map(fn (UserRole $userRole) => $userRole->role->label())
            ->implode(', ');
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class, 'created_by');
    }

    // 面接官としてアサインされた選考(candidacy_interviewers 経由)。
    // Candidacy::interviewers() の逆方向
    public function assignedCandidacies(): BelongsToMany
    {
        return $this->belongsToMany(Candidacy::class, 'candidacy_interviewers', 'interviewer_id', 'candidacy_id')->withPivot(['round', 'assigned_at']);
    }

    // 面接官として入力した面接評価
    public function evaluations(): HasMany
    {
        return $this->hasMany(InterviewEvaluation::class, 'interviewer_id');
    }
}
