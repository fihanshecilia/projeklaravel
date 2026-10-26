<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'wa_number',
        'alamat',
        'pin',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin',
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
            'pin' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['phone'] ?? $this->attributes['wa_number'] ?? null;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
        if (array_key_exists('wa_number', $this->attributes)) {
            $this->attributes['wa_number'] = $value;
        }
    }

    public function getWaNumberAttribute(): ?string
    {
        return $this->attributes['wa_number'] ?? $this->attributes['phone'] ?? null;
    }

    public function setWaNumberAttribute(?string $value): void
    {
        $this->attributes['wa_number'] = $value;
        if (array_key_exists('phone', $this->attributes)) {
            $this->attributes['phone'] = $value;
        }
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['address'] ?? $this->attributes['alamat'] ?? null;
    }

    public function setAddressAttribute(?string $value): void
    {
        $this->attributes['address'] = $value;
        if (array_key_exists('alamat', $this->attributes)) {
            $this->attributes['alamat'] = $value;
        }
    }

    public function getAlamatAttribute(): ?string
    {
        return $this->attributes['alamat'] ?? $this->attributes['address'] ?? null;
    }

    public function setAlamatAttribute(?string $value): void
    {
        $this->attributes['alamat'] = $value;
        if (array_key_exists('address', $this->attributes)) {
            $this->attributes['address'] = $value;
        }
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isStoreAdmin();
    }

    public function isStoreAdmin(): bool
    {
        return in_array($this->role, ['admin_toko', 'owner', 'admin'], true);
    }

    public function isCashier(): bool
    {
        return in_array($this->role, ['kasir', 'cashier'], true);
    }

    public function isBuyer(): bool
    {
        return in_array($this->role, ['pembeli', 'customer'], true);
    }

    public function isEmployee(): bool
    {
        return $this->isStoreAdmin() || $this->isCashier();
    }

    public function isActiveAccount(): bool
    {
        return $this->is_active ?? true;
    }

    public function isLastStoreAdmin(): bool
    {
        if (! $this->isStoreAdmin()) {
            return false;
        }

        return static::query()
            ->where('role', $this->role)
            ->count() <= 1;
    }

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if (
                $user->isDirty('role')
                && in_array($user->getOriginal('role'), ['admin_toko', 'owner', 'admin'], true)
                && ! in_array($user->role, ['admin_toko', 'owner', 'admin'], true)
                && static::query()->whereIn('role', ['admin_toko', 'owner', 'admin'])->count() <= 1
            ) {
                throw new \RuntimeException('Akun Super Admin terakhir tidak boleh dinonaktifkan atau diubah perannya.');
            }
        });

        static::deleting(function (User $user): void {
            if (
                in_array($user->role, ['admin_toko', 'owner', 'admin'], true)
                && static::query()->whereIn('role', ['admin_toko', 'owner', 'admin'])->count() <= 1
            ) {
                throw new \RuntimeException('Akun Super Admin terakhir tidak boleh dihapus.');
            }
        });
    }

    public function canAccessPos(): bool
    {
        return $this->isStoreAdmin() || $this->isCashier();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
