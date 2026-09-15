<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
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
        'email_verified_at',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password'                => 'hashed',
        ];
    }

    /* ── Relations ───────────────────────────────────────────── */

    public function providerProfile(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function createdAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class)->latest('created_at');
    }

    /**
     * Check if a plain-text password matches any of the last N stored hashes.
     */
    public function passwordUsedBefore(string $plainPassword, int $limit = 5): bool
    {
        return $this->passwordHistories()
            ->limit($limit)
            ->get()
            ->contains(fn ($history) => Hash::check($plainPassword, $history->password));
    }

    /**
     * Store the current password in history, keeping only the last $keep entries.
     */
    public function recordPasswordHistory(int $keep = 5): void
    {
        PasswordHistory::create([
            'user_id'  => $this->id,
            'password' => $this->password,   // already hashed
        ]);

        // Prune old entries beyond the limit
        $oldest = $this->passwordHistories()
            ->skip($keep)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($oldest->isNotEmpty()) {
            PasswordHistory::whereIn('id', $oldest)->delete();
        }
    }

    /* ── Role helpers ────────────────────────────────────────── */

    public function isOwner(): bool        { return $this->role === self::ROLE_OWNER; }
    public function isReceptionist(): bool { return $this->role === self::ROLE_RECEPTIONIST; }
    public function isProvider(): bool     { return $this->role === self::ROLE_PROVIDER; }
    public function isAssistant(): bool    { return $this->role === self::ROLE_ASSISTANT; }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /* ── 2FA helpers ─────────────────────────────────────────── */

    /**
     * Whether 2FA has been enabled AND confirmed by the user.
     * A secret can be generated but not yet confirmed — this returns false then.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at)
            && ! is_null($this->two_factor_secret);
    }

    /**
     * Whether a 2FA secret has been generated but not yet confirmed.
     * Used to show the QR code page.
     */
    public function hasPendingTwoFactor(): bool
    {
        return ! is_null($this->two_factor_secret)
            && is_null($this->two_factor_confirmed_at);
    }

    /**
     * Return decrypted recovery codes as a Collection.
     * Returns empty collection if none exist.
     */
    public function getRecoveryCodes(): Collection
    {
        if (! $this->two_factor_recovery_codes) {
            return collect();
        }

        return collect(json_decode(decrypt($this->two_factor_recovery_codes), true));
    }

    /**
     * Generate and store 8 fresh recovery codes (hashed for storage).
     * Returns the plain-text codes — these are shown to the user ONCE.
     *
     * @return array<string> Plain-text recovery codes
     */
    public function generateRecoveryCodes(): array
    {
        $plain = collect(range(1, 8))->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))->all();

        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(
                array_map(fn ($code) => Hash::make($code), $plain)
            )),
        ])->save();

        return $plain;
    }

    /**
     * Check a plain-text recovery code against stored hashed codes.
     * Invalidates the used code (one-time use).
     *
     * @return bool Whether the code was valid
     */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->getRecoveryCodes();

        $matched = $codes->first(fn ($hashed) => Hash::check($code, $hashed));

        if (! $matched) {
            return false;
        }

        // Remove the used code
        $remaining = $codes->reject(fn ($hashed) => $hashed === $matched)->values();

        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($remaining->all())),
        ])->save();

        return true;
    }

    /**
     * Clear all 2FA data from the user — called on disable.
     */
    public function disableTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /* ── Token abilities ─────────────────────────────────────── */

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
