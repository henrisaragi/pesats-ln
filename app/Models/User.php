<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Entitas\Entitas;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Permission yang menandai user sebagai penandatangan SATS-LN (lihat PenandatanganSeeder).
     */
    public const PENANDATANGAN_PERMISSION = 'penandatanganan';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'entity_id',
        'nip',
        'jabatan',
        'phone_number',
        'signature_path',
        'is_active',
        'onboarding_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'onboarding_completed' => 'boolean',
            'otp_expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entitas::class, 'entity_id');
    }

    /**
     * User aktif yang memiliki permission penandatangan (langsung maupun lewat role).
     */
    public function scopePenandatangan(Builder $query): void
    {
        $query->permission(self::PENANDATANGAN_PERMISSION)->where('is_active', true);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name));

        return collect($words)
            ->filter()
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->take(2)
            ->implode('');
    }
}
