<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
        'signature_path',
        'stamp_path',
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

    public function getSignatureUrlAttribute(): ?string
    {
        if ($this->signature_path && Storage::disk('public')->exists($this->signature_path)) {
            return Storage::disk('public')->url($this->signature_path);
        }

        return null;
    }

    public function hasSignature(): bool
    {
        return ! empty($this->signature_path) && Storage::disk('public')->exists($this->signature_path);
    }

    public function getStampUrlAttribute(): ?string
    {
        if ($this->isScopedToPacLetters()) {
            $pacId = $this->getLetterPacId();
            if ($pacId) {
                $pac = Pac::find($pacId);
                if ($pac && $pac->stamp_url) {
                    return $pac->stamp_url;
                }
            }

            if ($this->stamp_path && Storage::disk('public')->exists($this->stamp_path)) {
                return Storage::disk('public')->url($this->stamp_path);
            }

            return null;
        }

        if ($this->stamp_path && Storage::disk('public')->exists($this->stamp_path)) {
            return Storage::disk('public')->url($this->stamp_path);
        }

        if (Storage::disk('public')->exists('stamps/official_stamp.png')) {
            return Storage::disk('public')->url('stamps/official_stamp.png');
        }

        if (file_exists(public_path('images/stempel_isnu.png'))) {
            return asset('images/stempel_isnu.png');
        }

        return null;
    }

    public function hasStamp(): bool
    {
        if ($this->isScopedToPacLetters()) {
            $pacId = $this->getLetterPacId();
            if ($pacId) {
                $pac = Pac::find($pacId);
                if ($pac && $pac->hasStamp()) {
                    return true;
                }
            }

            return ! empty($this->stamp_path) && Storage::disk('public')->exists($this->stamp_path);
        }

        return (! empty($this->stamp_path) && Storage::disk('public')->exists($this->stamp_path))
            || Storage::disk('public')->exists('stamps/official_stamp.png');
    }

    /**
     * Memeriksa apakah user berhak mengunggah/memperbarui stempel PC ISNU.
     * Hak akses: Sekretaris / Wakil Sekretaris PC ISNU, serta Admin Kota / Super Admin.
     */
    public function canUploadPcStamp(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        return $this->canSignAsSecretary() && ! $this->isOfficerPac();
    }

    /**
     * Memeriksa apakah user berhak mengunggah/memperbarui stempel PAC ISNU tertentu.
     * Hak akses: Sekretaris / Wakil Sekretaris PAC ISNU pada wilayah PAC terkait.
     */
    public function canUploadPacStamp(?int $pacId = null): bool
    {
        if (! $this->canSignAsSecretary() || ! $this->isOfficerPac()) {
            return false;
        }

        if ($pacId === null) {
            return $this->getLetterPacId() !== null;
        }

        return (int) $this->getLetterPacId() === $pacId;
    }

    /**
     * Memeriksa apakah user memiliki wewenang mengunggah stempel organisasi (PC atau PAC-nya).
     */
    public function canUploadStamp(): bool
    {
        return $this->canUploadPcStamp() || $this->canUploadPacStamp();
    }

    /**
     * Label lingkup stempel yang dikelola oleh user (PC ISNU atau PAC ISNU wilayahnya).
     */
    public function getStampScopeLabelAttribute(): string
    {
        if ($this->isScopedToPacLetters()) {
            $pacId = $this->getLetterPacId();
            $pac = $pacId ? Pac::find($pacId) : null;

            return $pac ? $pac->display_name : 'PAC ISNU';
        }

        return 'PC ISNU Kota Surabaya';
    }

    /**
     * Mengambil URL stempel resmi aktif yang sesuai dengan tingkatan/lokasi surat.
     */
    public function getStampUrlForLetter(Letter $letter): ?string
    {
        if ($letter->isLevelPac()) {
            $pac = $letter->relationLoaded('pac') ? $letter->pac : ($letter->pac_id ? Pac::find($letter->pac_id) : null);
            if ($pac && $pac->stamp_url) {
                return $pac->stamp_url;
            }

            if ($this->isOfficerPac() && (int) $this->getLetterPacId() === (int) $letter->pac_id && $this->stamp_path && Storage::disk('public')->exists($this->stamp_path)) {
                return Storage::disk('public')->url($this->stamp_path);
            }

            return null;
        }

        if (Storage::disk('public')->exists('stamps/official_stamp.png')) {
            return Storage::disk('public')->url('stamps/official_stamp.png');
        }

        if (! $this->isOfficerPac() && $this->stamp_path && Storage::disk('public')->exists($this->stamp_path)) {
            return Storage::disk('public')->url($this->stamp_path);
        }

        if (file_exists(public_path('images/stempel_isnu.png'))) {
            return asset('images/stempel_isnu.png');
        }

        return null;
    }

    /**
     * Memeriksa apakah sudah tersedia stempel aktif untuk tingkatan/lokasi surat terkait.
     */
    public function hasStampForLetter(Letter $letter): bool
    {
        return $this->getStampUrlForLetter($letter) !== null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        Mail::to($this->getEmailForPasswordReset())->send(new ResetPasswordMail($this, $token, $url));
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

    public function isSystemAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin_kota', 'admin_mwc', 'admin_pac', 'admin'], true);
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
     * Memeriksa apakah user adalah Pengurus tingkat PAC ISNU.
     */
    public function isOfficerPac(): bool
    {
        $pos = $this->getOfficerPosition();
        if (! $pos) {
            return false;
        }

        return in_array($pos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true) || ! empty($pos->pac_id);
    }

    /**
     * Memeriksa apakah user adalah Pengurus tingkat PC ISNU.
     */
    public function isOfficerPc(): bool
    {
        return $this->isOfficer() && ! $this->isOfficerPac();
    }

    /**
     * Memeriksa apakah user adalah Ketua atau Sekretaris PAC ISNU.
     */
    public function isKetuaOrSekretarisPac(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return false;
        }

        $title = (string) $this->getOfficerPositionTitle();

        return in_array($title, ['Ketua', 'Sekretaris'], true) && $this->isOfficerPac();
    }

    /**
     * Memeriksa apakah user adalah Ketua atau Sekretaris PC ISNU.
     */
    public function isKetuaOrSekretarisPc(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return false;
        }

        $title = (string) $this->getOfficerPositionTitle();

        return in_array($title, ['Ketua', 'Sekretaris'], true) && $this->isOfficerPc();
    }

    /**
     * Memeriksa apakah user adalah Ketua atau Sekretaris PC ISNU (atau Super Admin / Admin Kota / Admin)
     * yang memiliki hak akses penuh atas semua program dan kegiatan di semua seksi.
     */
    public function canManageAllProgramsAndEvents(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        $title = $this->getOfficerPositionTitle();

        return in_array($title, ['Ketua', 'Sekretaris'], true) && ! $this->isOfficerPac();
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

        if ($this->isKetuaOrSekretarisPac()) {
            if ($sectionId === null) {
                return false;
            }
            $section = Section::find($sectionId);

            return $section && (int) $section->pac_id === (int) $this->getManagedPacId();
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

        if ($this->isKetuaOrSekretarisPac()) {
            return $this->getManagedPacId() !== null;
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
        if ($this->member?->activePosition?->pac_id) {
            return (int) $this->member->activePosition->pac_id;
        }

        if ($this->member?->pac_id) {
            return (int) $this->member->pac_id;
        }

        return null;
    }

    /**
     * Memeriksa apakah user dibatasi hanya pada lingkup wilayah PAC tertentu
     * (seluruh Pengurus PAC dan Admin PAC, kecuali Super Admin / Admin Kota / Admin PC).
     */
    public function isScopedToPac(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return false;
        }

        return $this->isOfficerPac() || $this->isAdminPac();
    }

    /**
     * Memeriksa apakah user dibatasi hanya pada lingkup surat milik PAC tertentu.
     */
    public function isScopedToPacLetters(): bool
    {
        return $this->isScopedToPac();
    }

    /**
     * Mendapatkan model PAC yang menaungi user saat ini.
     */
    public function getManagedPac(): ?Pac
    {
        $pacId = $this->getManagedPacId();

        return $pacId ? Pac::find($pacId) : null;
    }

    /**
     * Mendapatkan nama kecamatan wilayah PAC yang dikelola/dinaungi user
     * (diambil dari master PAC, bukan domisili pribadi akun pengurus).
     */
    public function getManagedPacKecamatan(): ?string
    {
        $pac = $this->getManagedPac();
        if ($pac) {
            if (! empty($pac->kecamatan)) {
                return trim((string) $pac->kecamatan);
            }

            $clean = trim((string) preg_replace('/^(PAC\s+ISNU\s+|PAC\s+)/i', '', trim((string) $pac->name)));
            if ($clean !== '') {
                return $clean;
            }
        }

        return $this->member?->kecamatan ? trim((string) $this->member->kecamatan) : null;
    }

    /**
     * Memeriksa apakah seorang anggota masuk dalam wilayah PAC yang dinaungi user.
     */
    public function isMemberInManagedPac(Member $member): bool
    {
        $pacId = $this->getManagedPacId();
        if (! $pacId) {
            return false;
        }

        if ($member->pac_id !== null && (int) $member->pac_id === $pacId) {
            return true;
        }

        $memberPos = $member->relationLoaded('activePosition') ? $member->activePosition : $member->activePosition()->first();
        if (
            $memberPos
            && in_array($memberPos->level, ['PAC', 'PAC ISNU', 'Kecamatan'], true)
            && (int) $memberPos->pac_id === $pacId
        ) {
            return true;
        }

        // Jika anggota sudah terdaftar secara eksplisit di PAC lain, tidak masuk wilayah PAC ini
        if ($member->pac_id !== null) {
            return false;
        }

        $pacKecamatan = $this->getManagedPacKecamatan();
        if ($pacKecamatan && $member->kecamatan) {
            $normPac = strtolower(trim($pacKecamatan));
            $normMember = strtolower(trim((string) $member->kecamatan));

            return $normMember === $normPac || str_contains($normMember, $normPac);
        }

        return false;
    }

    /**
     * Mendapatkan ID PAC yang menaungi user untuk keperluan surat-menyurat.
     */
    public function getLetterPacId(): ?int
    {
        return $this->getManagedPacId();
    }

    /**
     * Memeriksa apakah user berhak melihat / mengakses surat tertentu.
     */
    public function canViewLetter(Letter $letter): bool
    {
        if (! $this->canAccessLetterSubmission()) {
            return false;
        }

        if ($this->isScopedToPacLetters()) {
            $pacId = $this->getLetterPacId();

            return $pacId !== null
                && $letter->isLevelPac()
                && (int) $letter->pac_id === $pacId;
        }

        return true;
    }

    /**
     * Memeriksa apakah user berhak mengelola data anggota dan data pengurus.
     * Hanya Superadmin, Admin, Ketua PC, dan Sekretaris PC yang dapat mengelola data,
     * sedangkan posisi pengurus lainnya (termasuk Ketua & Sekretaris PAC) hanya dapat melihat saja.
     */
    public function canManageMembersAndOfficers(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return true;
        }

        if ($this->isKetuaOrSekretarisPac()) {
            return false;
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
     * Memeriksa apakah user dapat mengelola Master PAC dan Data Sampah.
     * Hanya Superadmin, Admin PC, Ketua PC, dan Sekretaris PC yang berhak mengelola.
     * Ketua dan Sekretaris PAC TIDAK DAPAT mengelola Master PAC dan Data Sampah.
     */
    public function canManagePacSectionTrash(): bool
    {
        if ($this->isKetuaOrSekretarisPac()) {
            return false;
        }

        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        $positionTitle = $this->getOfficerPositionTitle();

        return in_array($positionTitle, ['Ketua', 'Sekretaris'], true);
    }

    /**
     * Memeriksa apakah user dapat mengelola Master Seksi.
     * Superadmin, Admin PC, Ketua/Sekretaris PC, dan Ketua/Sekretaris PAC berhak mengelola
     * (untuk Ketua & Sekretaris PAC khusus seksi di wilayah PAC-nya).
     */
    public function canManageSections(): bool
    {
        return $this->canManagePacSectionTrash() || $this->isKetuaOrSekretarisPac();
    }

    /**
     * Memeriksa apakah user dapat mengelola menu Kartu Fisik.
     * Hanya Superadmin, Admin PC, Ketua PC, Sekretaris PC, dan Bendahara yang berhak mengelola.
     * Ketua dan Sekretaris PAC tidak bisa mengakses menu kartu fisik.
     */
    public function canManageCardOrders(): bool
    {
        if ($this->isKetuaOrSekretarisPac()) {
            return false;
        }

        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        $positionTitle = $this->getOfficerPositionTitle();

        return in_array($positionTitle, ['Ketua', 'Sekretaris', 'Bendahara'], true);
    }

    /**
     * Memeriksa apakah user adalah pengurus dengan jabatan Bendahara / Wakil Bendahara.
     */
    public function isBendahara(): bool
    {
        $title = (string) $this->getOfficerPositionTitle();

        return in_array($title, ['Bendahara', 'Bendahara Umum', 'Wakil Bendahara'], true)
            || stripos($title, 'Bendahara') !== false;
    }

    /**
     * Memeriksa apakah user adalah akun pengurus Bendahara / Wakil Bendahara (bukan admin sistem).
     */
    public function isBendaharaOfficer(): bool
    {
        if ($this->isSystemAdmin()) {
            return false;
        }

        return $this->isBendahara();
    }

    /**
     * Memeriksa apakah user berhak menambah, mengubah, dan menghapus Master Kelompok Kerja (Pokja).
     * Bendahara hanya diperbolehkan melihat data Pokja (read-only).
     */
    public function canManageWorkgroups(): bool
    {
        if ($this->isRestrictedOfficer() || $this->isBendaharaOfficer()) {
            return false;
        }

        return true;
    }

    /**
     * Memeriksa apakah user dapat mengelola menu Keuangan (Kas Masuk & Kas Keluar).
     * Hak akses: Bendahara dan Admin (Superadmin, Admin Kota, Admin PAC, Admin MWC).
     */
    public function canManageFinances(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return true;
        }

        return $this->isBendahara();
    }

    /**
     * Memeriksa apakah user dapat mengelola master produk toko merchandise ISNU.
     * Hak akses: Admin PC ISNU (Superadmin, Admin Kota, role admin) serta Bendahara / Wakil Bendahara PC ISNU.
     */
    public function canManageProducts(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        return $this->isBendahara() && ! $this->isOfficerPac();
    }

    /**
     * Memeriksa apakah user dapat mengelola pemesanan & pengiriman barang merchandise.
     * Hak akses: Bendahara dan Wakil Bendahara PCNU, serta Superadmin & Admin Kota.
     */
    public function canManageProductOrders(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->role === 'admin') {
            return true;
        }

        return $this->isBendahara();
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
            if (! $pos || (! $this->isOfficerPac() && (in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || ! $pos->pac_id))) {
                return true;
            }

            return $this->isMemberInManagedPac($member);
        }

        if ($this->isAdminPac()) {
            return $this->isMemberInManagedPac($member);
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

        if ($this->isScopedToPac()) {
            return $this->isMemberInManagedPac($member);
        }

        $pos = $this->getOfficerPosition();
        if ($pos) {
            if (! $pos->pac_id || in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true)) {
                return true;
            }

            return $this->isMemberInManagedPac($member);
        }

        if ($this->member && (int) $this->member->id === (int) $member->id) {
            return true;
        }

        return false;
    }

    /**
     * Memeriksa apakah user berhak mengakses menu pengajuan konsep surat dan e-TTD.
     * Hak akses: Admin, Ketua, Sekretaris, Bendahara, Wakil Ketua, Wakil Sekretaris, Wakil Bendahara, Koordinator Seksi, dan Anggota Seksi.
     */
    public function canAccessLetterSubmission(): bool
    {
        if ($this->isSuperAdmin() || $this->isAdminKota() || $this->isAdminPac() || $this->isAdminMwc() || $this->role === 'admin') {
            return true;
        }

        if ($this->isKoordinatorSeksi() || $this->isPengurusAnggotaSeksi() || $this->isBendahara()) {
            return true;
        }

        $title = (string) $this->getOfficerPositionTitle();
        if ($title === '') {
            return false;
        }

        if (in_array($title, [
            'Ketua',
            'Sekretaris',
            'Bendahara',
            'Wakil Ketua',
            'Wakil Sekretaris',
            'Wakil Bendahara',
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
                $officerPacId = (int) ($pos->pac_id ?: $this->getManagedPacId());

                return $isPacLevel && $officerPacId > 0 && $officerPacId === (int) $letter->pac_id;
            }

            // Surat level PC ISNU
            return ! $this->isOfficerPac() && (in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || empty($pos->pac_id));
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
                $officerPacId = (int) ($pos->pac_id ?: $this->getManagedPacId());

                return $isPacLevel && $officerPacId > 0 && $officerPacId === (int) $letter->pac_id;
            }

            // Surat level PC ISNU
            return ! $this->isOfficerPac() && (in_array($pos->level, ['Kota', 'PC ISNU', 'PC'], true) || empty($pos->pac_id));
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

        // 1b. Pengurus / Admin PAC hanya boleh menyunting surat milik PAC-nya sendiri
        if ($this->isScopedToPacLetters() && ! $this->canViewLetter($letter)) {
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
            if ($letter->isLevelPac() && (int) $this->getLetterPacId() === (int) $letter->pac_id) {
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
