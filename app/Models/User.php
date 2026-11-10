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
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return false;
        }

        $title = $this->getOfficerPositionTitle();

        return in_array($title, ['Wakil Ketua', 'Wakil Sekretaris'], true);
    }

    /**
     * Memeriksa apakah user adalah Koordinator Seksi.
     */
    public function isKoordinatorSeksi(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return false;
        }

        $title = (string) $this->getOfficerPositionTitle();

        return in_array($title, ['Koordinator Seksi', 'Koordinator'], true) || stripos($title, 'Koordinator') !== false;
    }

    /**
     * Memeriksa apakah user adalah pengurus yang mengampu seksi tertentu
     * (Wakil Ketua, Wakil Sekretaris, atau Koordinator Seksi).
     */
    public function isSectionOfficer(): bool
    {
        return $this->isWakilPengurus() || $this->isKoordinatorSeksi();
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

        if ($this->isSectionOfficer()) {
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

        return $this->isSectionOfficer() && $this->getOfficerSectionId() !== null;
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Memeriksa apakah user adalah pengurus dengan jabatan Anggota Seksi.
     */
    public function isPengurusAnggotaSeksi(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return false;
        }

        $title = (string) $this->getOfficerPositionTitle();

        return $title === 'Anggota Seksi' || stripos($title, 'Anggota Seksi') !== false;
    }

    /**
     * Memeriksa apakah user adalah pengurus dengan hak akses terbatas
     * (Anggota Seksi, Koordinator Seksi, Wakil Ketua, atau Wakil Sekretaris)
     * pada menu verifikasi, database potensi kader, master kelompok kerja,
     * export laporan, audit log, dan aksi lihat detail data pengurus lain.
     */
    public function isRestrictedOfficer(): bool
    {
        return $this->isPengurusAnggotaSeksi() || $this->isKoordinatorSeksi() || $this->isWakilPengurus();
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

        if ($this->isRestrictedOfficer()) {
            return $this->member && (int) $this->member->id === (int) $member->id;
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

    /**
     * Memeriksa apakah user berhak mengakses menu pengajuan konsep surat dan e-TTD.
     * Hak akses: Admin, Ketua, Sekretaris, Wakil Ketua, Wakil Sekretaris, Koordinator Seksi, dan Anggota Seksi.
     */
    public function canAccessLetterSubmission(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return true;
        }

        if ($this->isKoordinatorSeksi() || $this->isPengurusAnggotaSeksi()) {
            return true;
        }

        $title = (string) $this->getOfficerPositionTitle();
        if ($title === '') {
            return false;
        }

        if (in_array($title, [
            'Ketua',
            'Sekretaris',
            'Wakil Ketua',
            'Wakil Sekretaris',
            'Koordinator Seksi',
            'Koordinator',
            'Anggota Seksi',
        ], true)) {
            return true;
        }

        return stripos($title, 'Koordinator') !== false || stripos($title, 'Anggota Seksi') !== false;
    }

    /**
     * Memeriksa apakah user dapat menandatangani surat secara digital sebagai Sekretaris.
     * Hak akses:
     * - Surat level PC ISNU: Akun yang bertindak sebagai Sekretaris / Wakil Sekretaris PC ISNU.
     * - Surat level PAC ISNU: Akun yang bertindak sebagai Sekretaris / Wakil Sekretaris PAC ISNU di wilayah PAC terkait.
     * - Admin TIDAK BISA menandatangani surat kecuali memiliki jabatan pengurus yang sesuai.
     */
    public function canSignAsSecretary(?Letter $letter = null): bool
    {
        $pos = $this->getOfficerPosition();
        if (! $pos) {
            return false;
        }

        $title = $pos->position_title;
        if (! in_array($title, ['Sekretaris', 'Wakil Sekretaris'], true)) {
            return false;
        }

        if ($letter) {
            if ($letter->isLevelPac()) {
                $isPacLevel = in_array($pos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true) || (bool) $pos->pac_id;

                return $isPacLevel && (int) $pos->pac_id === (int) $letter->pac_id;
            }

            // Surat level PC ISNU
            return in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || empty($pos->pac_id);
        }

        return true;
    }

    /**
     * Memeriksa apakah user dapat menandatangani surat secara digital sebagai Ketua.
     * Hak akses:
     * - Surat level PC ISNU: Akun yang bertindak sebagai Ketua / Wakil Ketua PC ISNU.
     * - Surat level PAC ISNU: Akun yang bertindak sebagai Ketua / Wakil Ketua PAC ISNU di wilayah PAC terkait.
     * - Admin TIDAK BISA menandatangani surat kecuali memiliki jabatan pengurus yang sesuai.
     */
    public function canSignAsChairman(?Letter $letter = null): bool
    {
        $pos = $this->getOfficerPosition();
        if (! $pos) {
            return false;
        }

        $title = $pos->position_title;
        if (! in_array($title, ['Ketua', 'Wakil Ketua'], true)) {
            return false;
        }

        if ($letter) {
            if ($letter->isLevelPac()) {
                $isPacLevel = in_array($pos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true) || (bool) $pos->pac_id;

                return $isPacLevel && (int) $pos->pac_id === (int) $letter->pac_id;
            }

            // Surat level PC ISNU
            return in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || empty($pos->pac_id);
        }

        return true;
    }

    /**
     * Memeriksa apakah user berhak menyunting / mengedit konsep surat.
     * Aturan:
     * 1. Surat yang sudah ditandatangani lengkap (status 'selesai') TIDAK BISA diedit oleh user apapun.
     * 2. Surat yang sudah di-TTD sekretaris lalu dibatalkan/ditolak ketua MASIH BISA diedit kembali.
     * 3. Surat hanya bisa diedit oleh:
     *    - Pembuat surat (user_id sama)
     *    - Admin (super_admin, admin_kota, admin, atau admin_pac pada wilayahnya)
     *    - Ketua (pada tingkatan surat terkait)
     *    - Sekretaris (pada tingkatan surat terkait)
     *    - Wakil Ketua dan Wakil Sekretaris HANYA jika surat diajukan oleh anggota seksinya.
     * 4. Di luar itu tidak bisa mengedit surat.
     */
    public function canEditLetter(Letter $letter): bool
    {
        // 1. Surat yang sudah ditandatangani lengkap (selesai) tidak bisa diedit oleh user apapun
        if ($letter->status === Letter::STATUS_SELESAI) {
            return false;
        }

        // 2. Pembuat surat selalu berhak mengedit surat buatannya
        if ((int) $this->id === (int) $letter->user_id) {
            return true;
        }

        // 3. Admin berhak mengedit surat
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        if ($this->isAdminPac()) {
            if (! $letter->isLevelPac() || (int) $this->getManagedPacId() === (int) $letter->pac_id) {
                return true;
            }
        }

        $pos = $this->getOfficerPosition();
        if (! $pos) {
            return false;
        }

        $title = $pos->position_title;

        // 4. Ketua (pada tingkatan surat terkait)
        if (in_array($title, ['Ketua', 'Ketua Umum'], true)) {
            if ($letter->isLevelPac()) {
                $isPacLevel = in_array($pos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true) || (bool) $pos->pac_id;

                return $isPacLevel && (int) $pos->pac_id === (int) $letter->pac_id;
            }

            return in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || empty($pos->pac_id);
        }

        // 5. Sekretaris (pada tingkatan surat terkait)
        if (in_array($title, ['Sekretaris', 'Sekretaris Umum'], true)) {
            if ($letter->isLevelPac()) {
                $isPacLevel = in_array($pos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true) || (bool) $pos->pac_id;

                return $isPacLevel && (int) $pos->pac_id === (int) $letter->pac_id;
            }

            return in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || empty($pos->pac_id);
        }

        // 6. Wakil Sekretaris dan Wakil Ketua dapat mengedit surat yang diajukan oleh anggota seksinya
        if (in_array($title, ['Wakil Ketua', 'Wakil Sekretaris'], true)) {
            $userSectionId = $this->getOfficerSectionId();
            $authorSectionId = $letter->user?->getOfficerSectionId();

            if ($userSectionId !== null && $authorSectionId !== null && (int) $userSectionId === (int) $authorSectionId) {
                if ($letter->isLevelPac()) {
                    $isPacLevel = in_array($pos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true) || (bool) $pos->pac_id;

                    return $isPacLevel && (int) $pos->pac_id === (int) $letter->pac_id;
                }

                return true;
            }
        }

        return false;
    }
}
