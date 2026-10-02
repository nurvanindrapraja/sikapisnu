<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'is_active',
        'activation_token',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function presences()
    {
        return $this->hasMany(EventPresence::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdminKota(): bool
    {
        return in_array($this->role, ['super_admin', 'admin_kota']);
    }

    public function isAdminMwc(): bool
    {
        return $this->role === 'admin_mwc';
    }

    public function isAdminPac(): bool
    {
        return $this->role === 'admin_pac';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin_kota', 'admin_mwc', 'admin_pac']);
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function getManagedPacId(): ?int
    {
        if ($this->member?->pac_id) {
            return $this->member->pac_id;
        }

        if ($this->member?->activePosition?->pac_id) {
            return $this->member->activePosition->pac_id;
        }

        return null;
    }

    public function canManageMember(?Member $member): bool
    {
        if (! $member) {
            return false;
        }

        if ($this->isSuperAdmin() || $this->isAdminKota()) {
            return true;
        }

        if ($this->isAdminPac()) {
            $pacId = $this->getManagedPacId();
            if ($pacId && $member->pac_id) {
                return (int) $member->pac_id === (int) $pacId;
            }

            if ($this->member?->kecamatan && $member->kecamatan) {
                return strtolower(trim($this->member->kecamatan)) === strtolower(trim($member->kecamatan));
            }

            return false;
        }

        return false;
    }
}
