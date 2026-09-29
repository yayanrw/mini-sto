<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'area',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isKoordinator(): bool
    {
        return $this->role === 'admin';
    }

    /** Label tampilan; nilai `role` di DB tidak berubah. */
    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Koordinator Area',
            'superadmin' => 'Superadmin',
            default => 'Sales',
        };
    }

    /** Boleh menyentuh data di $area? Tanpa area = tanpa akses (kecuali superadmin). */
    public function canAccessArea(?string $area): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->isKoordinator() && $area !== null && $area === $this->area;
    }

    /** Akhiran judul halaman koordinator, mis. " · Kab. Malang". */
    public function areaSuffix(): string
    {
        return $this->isKoordinator() && $this->area ? " · {$this->area}" : '';
    }

    /** Pengguna yang boleh dilihat $viewer: superadmin semua, koordinator hanya sales di areanya. */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isSuperadmin()) {
            return $query;
        }

        // Jangan pakai where('area', null): Laravel mengubahnya jadi IS NULL,
        // dan koordinator tanpa area akan melihat semua data berarea null.
        if (! $viewer->isKoordinator() || $viewer->area === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('users.role', 'sales')->where('users.area', $viewer->area);
    }
}
