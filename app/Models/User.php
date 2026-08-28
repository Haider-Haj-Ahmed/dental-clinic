<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_OWNER        = 'owner';
    public const ROLE_RECEPTIONIST = 'receptionist';
    public const ROLE_PROVIDER     = 'provider';
    public const ROLE_ASSISTANT    = 'assistant';

    public const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_RECEPTIONIST,
        self::ROLE_PROVIDER,
        self::ROLE_ASSISTANT,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at', // required so seeder updateOrCreate and register flow can set it
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function providerProfile(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function createdAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function isOwner(): bool        { return $this->role === self::ROLE_OWNER; }
    public function isReceptionist(): bool { return $this->role === self::ROLE_RECEPTIONIST; }
    public function isProvider(): bool     { return $this->role === self::ROLE_PROVIDER; }
    public function isAssistant(): bool    { return $this->role === self::ROLE_ASSISTANT; }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Token abilities scoped per role.
     * Owner gets '*' — bypasses all token.ability checks.
     */
    public function tokenAbilities(): array
    {
        return match ($this->role) {
            self::ROLE_OWNER => ['*'],

            self::ROLE_RECEPTIONIST => [
                'patients:read', 'patients:write',
                'appointments:read', 'appointments:write',
                'clinical:read',
                'billing:read', 'billing:write',
                'recalls:read', 'recalls:write',
                'inventory:read', 'inventory:write',
                'schedule-blocks:read', 'schedule-blocks:write',
            ],

            self::ROLE_PROVIDER => [
                'patients:read',
                'appointments:read', 'appointments:write',
                'clinical:read', 'clinical:write',
                'documents:write',
                'recalls:read',
                'billing:read',
                'schedule-blocks:read',
            ],

            self::ROLE_ASSISTANT => [
                'patients:read',
                'appointments:read',
                'clinical:read',
                'documents:write',
                'schedule-blocks:read',
            ],

            default => [],
        };
    }
}
