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
        return in_array($this->role, ['super_admin', 'admin_kota', 'admin_mwc', 'admin_pac']) || $this->isOfficer();
    }

    public function isOfficer(): bool
    {
        return (bool) ($this->member && ($this->member->membership_status === 'pengurus' || $this->member->activePosition !== null));
    }

    public function getOfficerPosition(): ?Position
    {
        return $this->member?->activePosition;
    }

    public function getOfficerPositionTitle(): ?string
    {
        return $this->getOfficerPosition()?->position_title;
    }

    public function getOfficerSectionId(): ?int
    {
        return $this->getOfficerPosition()?->section_id;
    }

    public function getOfficerSection(): ?Section
    {
        return $this->getOfficerPosition()?->section;
    }

    /**
     * Memeriksa apakah user adalah Ketua atau Sekretaris (atau Super Admin / Admin Kota / Admin)
     * yang memiliki hak akses penuh atas semua program dan kegiatan di semua seksi.
     */
    public function canManageAllProgramsAndEvents(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        $title = $this->getOfficerPositionTitle();

        return in_array($title, ['Ketua', 'Sekretaris'], true);
    }

    /**
     * Memeriksa apakah user adalah Wakil Ketua atau Wakil Sekretaris.
     */
    public function isWakilPengurus(): bool
    {
        $title = $this->getOfficerPositionTitle();

        return in_array($title, ['Wakil Ketua', 'Wakil Sekretaris'], true);
    }

    /**
     * Memeriksa apakah user memiliki hak akses untuk menambah, mengedit, atau menghapus
     * program atau kegiatan pada seksi tertentu.
     */
    public function canManageSectionProgramOrEvent(?int $sectionId): bool
    {
        if ($this->canManageAllProgramsAndEvents()) {
            return true;
        }

        if ($this->isWakilPengurus()) {
            $userSectionId = $this->getOfficerSectionId();

            return $userSectionId !== null && $sectionId !== null && (int) $userSectionId === (int) $sectionId;
        }

        return false;
    }

    /**
     * Memeriksa apakah user dapat mengelola setidaknya satu seksi untuk program atau kegiatan.
     */
    public function canManageAnyProgramOrEvent(): bool
    {
        if ($this->canManageAllProgramsAndEvents()) {
            return true;
        }

        return $this->isWakilPengurus() && $this->getOfficerSectionId() !== null;
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

    /**
     * Memeriksa apakah user berhak mengelola data anggota dan data pengurus.
     * Hanya Superadmin, Admin, Ketua, dan Sekretaris yang dapat mengelola data,
     * sedangkan posisi pengurus lainnya hanya dapat melihat saja.
     */
    public function canManageMembersAndOfficers(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return true;
        }

        $positionTitle = $this->getOfficerPositionTitle();

        return in_array($positionTitle, ['Ketua', 'Sekretaris'], true);
    }

    /**
     * Memeriksa apakah user dapat mengelola Master Lokasi.
     * Hanya Superadmin dan Admin PC yang berhak mengelola Master Lokasi.
     */
    public function canManageLocations(): bool
    {
        return $this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin';
    }

    /**
     * Memeriksa apakah user dapat mengelola Master PAC, Master Seksi, dan Data Sampah.
     * Hanya Superadmin, Admin PC, Ketua, dan Sekretaris yang berhak mengelola.
     */
    public function canManagePacSectionTrash(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        $positionTitle = $this->getOfficerPositionTitle();

        return in_array($positionTitle, ['Ketua', 'Sekretaris'], true);
    }

    /**
     * Memeriksa apakah user dapat mengelola menu Kartu Fisik.
     * Hanya Superadmin, Admin PC, Ketua, Sekretaris, dan Bendahara yang berhak mengelola.
     */
    public function canManageCardOrders(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        $positionTitle = $this->getOfficerPositionTitle();

        return in_array($positionTitle, ['Ketua', 'Sekretaris', 'Bendahara'], true);
    }

    public function canManageMember(?Member $member): bool
    {
        if (! $member) {
            return false;
        }

        if (! $this->canManageMembersAndOfficers()) {
            return false;
        }

        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        $title = $this->getOfficerPositionTitle();
        if (in_array($title, ['Ketua', 'Sekretaris'], true)) {
            $pos = $this->getOfficerPosition();
            if (! $pos || in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || ! $pos->pac_id) {
                return true;
            }

            if ($pos->pac_id && $member->pac_id) {
                return (int) $member->pac_id === (int) $pos->pac_id;
            }

            if ($this->member?->kecamatan && $member->kecamatan) {
                return strtolower(trim($this->member->kecamatan)) === strtolower(trim($member->kecamatan));
            }

            return false;
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

    public function canViewMember(?Member $member): bool
    {
        if (! $member) {
            return false;
        }

        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminMwc() || $this->role === 'admin') {
            return true;
        }

        $pos = $this->getOfficerPosition();
        if ($pos) {
            if (! $pos->pac_id || in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true)) {
                return true;
            }

            if ($pos->pac_id && $member->pac_id) {
                return (int) $member->pac_id === (int) $pos->pac_id;
            }

            if ($this->member?->kecamatan && $member->kecamatan) {
                return strtolower(trim($this->member->kecamatan)) === strtolower(trim($member->kecamatan));
            }

            return false;
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

        if ($this->member && (int) $this->member->id === (int) $member->id) {
            return true;
        }

        return false;
    }
}
