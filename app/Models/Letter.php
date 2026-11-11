<?php

namespace App\Models;

use App\Services\LetterService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Letter extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_MENUNGGU_SEKRETARIS = 'menunggu_sekretaris';

    public const STATUS_MENUNGGU_KETUA = 'menunggu_ketua';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DITOLAK = 'ditolak';

    public const TYPE_SURAT_BIASA = 'surat_biasa';

    public const TYPE_SURAT_MANDAT = 'surat_mandat';

    public const TYPE_SURAT_REKOMENDASI = 'surat_rekomendasi';

    public const LEVEL_PC = 'PC ISNU';

    public const LEVEL_PAC = 'PAC ISNU';

    protected $fillable = [
        'user_id',
        'letter_code',
        'letter_type',
        'level',
        'pac_id',
        'letter_number',
        'roman_month',
        'year',
        'date_letter',
        'lampiran',
        'perihal',
        'kegiatan',
        'tanggal_pelaksanaan',
        'lokasi_pelaksanaan',
        'kota_asal',
        'tujuan_surat',
        'kota_tujuan',
        'isi_surat',
        'status',
        'rejection_note',
        'secretary_user_id',
        'secretary_name',
        'secretary_registration_number',
        'secretary_position',
        'secretary_signed_at',
        'secretary_qr_path',
        'chairman_user_id',
        'chairman_name',
        'chairman_registration_number',
        'chairman_position',
        'chairman_signed_at',
        'chairman_qr_path',
        'docx_path',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'date_letter' => 'date',
            'secretary_signed_at' => 'datetime',
            'chairman_signed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_user_id');
    }

    public function chairman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairman_user_id');
    }

    public function pac(): BelongsTo
    {
        return $this->belongsTo(Pac::class, 'pac_id');
    }

    public function isLevelPac(): bool
    {
        return $this->level === self::LEVEL_PAC;
    }

    public function isLevelPc(): bool
    {
        return $this->level === self::LEVEL_PC || empty($this->level);
    }

    public function getPacDisplayNameAttribute(): string
    {
        if (! $this->pac) {
            return 'PAC ISNU';
        }

        $raw = trim($this->pac->name);
        $clean = preg_replace('/^(PAC\s+ISNU\s+|PAC\s+)/i', '', $raw);

        return 'PAC ISNU '.$clean;
    }

    public function getPacKecamatanNameAttribute(): string
    {
        if (! $this->pac) {
            return '';
        }

        if (! empty($this->pac->kecamatan)) {
            return $this->pac->kecamatan;
        }

        $raw = trim($this->pac->name);

        return preg_replace('/^(PAC\s+ISNU\s+|PAC\s+)/i', '', $raw);
    }

    public function getLevelLabelAttribute(): string
    {
        if ($this->isLevelPac() && $this->pac) {
            return $this->pac_display_name;
        }

        return 'PC ISNU Kota Surabaya';
    }

    public function getFormattedDateLetterAttribute(): string
    {
        if (! $this->date_letter) {
            return '';
        }

        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $carbon = Carbon::parse($this->date_letter);
        $bulan = $bulanIndo[(int) $carbon->format('n')] ?? $carbon->format('F');

        return $carbon->format('j').' '.$bulan.' '.$carbon->format('Y');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Konsep (Draft)',
            self::STATUS_MENUNGGU_SEKRETARIS => 'Menunggu TTD Sekretaris',
            self::STATUS_MENUNGGU_KETUA => 'Menunggu TTD Ketua',
            self::STATUS_SELESAI => 'Selesai & Tertandatangani',
            self::STATUS_DITOLAK => 'Ditolak / Perlu Revisi',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'badge bg-secondary',
            self::STATUS_MENUNGGU_SEKRETARIS => 'badge bg-warning text-dark',
            self::STATUS_MENUNGGU_KETUA => 'badge bg-info text-dark',
            self::STATUS_SELESAI => 'badge bg-success',
            self::STATUS_DITOLAK => 'badge bg-danger',
            default => 'badge bg-light text-dark',
        };
    }

    public function isMandat(): bool
    {
        return $this->letter_type === self::TYPE_SURAT_MANDAT;
    }

    public function isRekomendasi(): bool
    {
        return $this->letter_type === self::TYPE_SURAT_REKOMENDASI;
    }

    public function isBiasa(): bool
    {
        return $this->letter_type === self::TYPE_SURAT_BIASA || empty($this->letter_type);
    }

    public function getLetterTypeLabelAttribute(): string
    {
        return match ($this->letter_type) {
            self::TYPE_SURAT_MANDAT => 'Surat Mandat',
            self::TYPE_SURAT_REKOMENDASI => 'Surat Rekomendasi',
            default => 'Surat Biasa / Dinas',
        };
    }

    public function getLetterTypeBadgeClassAttribute(): string
    {
        return match ($this->letter_type) {
            self::TYPE_SURAT_MANDAT => 'badge bg-primary-subtle text-primary border border-primary-subtle',
            self::TYPE_SURAT_REKOMENDASI => 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'badge bg-success-subtle text-success border border-success-subtle',
        };
    }

    public function getFormattedLetterNumberAttribute(): string
    {
        $number = (string) $this->letter_number;

        // Normalisasi jika masih tersimpan dengan format PC-ISNU/SBY lama
        $number = str_replace('/PC-ISNU/SBY/', '/PC-VI/ISNU-SBY/', $number);

        // Jika nomor surat sudah lengkap (berisi format garis miring '/')
        if (str_contains($number, '/')) {
            return $number;
        }

        // Jika hanya memasukkan digit urutan (contoh: 002)
        return sprintf('%s/PC-VI/ISNU-SBY/%s/%s', $number, $this->roman_month, $this->year);
    }

    public static function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V',
            6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
            11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return false;
        }

        return $user->canEditLetter($this);
    }

    public function getSecretaryQrDataUrlAttribute(): ?string
    {
        if (! $this->secretary_signed_at) {
            return null;
        }

        if ($this->secretary_qr_path && Storage::disk('public')->exists($this->secretary_qr_path)) {
            try {
                $content = Storage::disk('public')->get($this->secretary_qr_path);
                if (! empty($content)) {
                    return 'data:image/png;base64,'.base64_encode($content);
                }
            } catch (\Throwable $e) {
                Log::warning('Error reading secretary QR: '.$e->getMessage());
            }
        }

        try {
            /** @var LetterService $letterService */
            $letterService = app(LetterService::class);
            $payload = $letterService->buildQrPayload($this, 'secretary');
            $png = $letterService->generateQrPng($payload);

            try {
                $dir = 'letters/qrcodes';
                Storage::disk('public')->makeDirectory($dir);
                $path = "{$dir}/qr_sec_{$this->letter_code}.png";
                Storage::disk('public')->put($path, $png);

                if ($this->secretary_qr_path !== $path) {
                    $this->secretary_qr_path = $path;
                    $this->saveQuietly();
                }
            } catch (\Throwable $diskException) {
                Log::warning('Could not write secretary QR to disk: '.$diskException->getMessage());
            }

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable $e) {
            Log::warning('Error generating secretary QR on the fly: '.$e->getMessage());

            return null;
        }
    }

    public function getChairmanQrDataUrlAttribute(): ?string
    {
        if (! $this->chairman_signed_at) {
            return null;
        }

        if ($this->chairman_qr_path && Storage::disk('public')->exists($this->chairman_qr_path)) {
            try {
                $content = Storage::disk('public')->get($this->chairman_qr_path);
                if (! empty($content)) {
                    return 'data:image/png;base64,'.base64_encode($content);
                }
            } catch (\Throwable $e) {
                Log::warning('Error reading chairman QR: '.$e->getMessage());
            }
        }

        try {
            /** @var LetterService $letterService */
            $letterService = app(LetterService::class);
            $payload = $letterService->buildQrPayload($this, 'chairman');
            $png = $letterService->generateQrPng($payload);

            try {
                $dir = 'letters/qrcodes';
                Storage::disk('public')->makeDirectory($dir);
                $path = "{$dir}/qr_chair_{$this->letter_code}.png";
                Storage::disk('public')->put($path, $png);

                if ($this->chairman_qr_path !== $path) {
                    $this->chairman_qr_path = $path;
                    $this->saveQuietly();
                }
            } catch (\Throwable $diskException) {
                Log::warning('Could not write chairman QR to disk: '.$diskException->getMessage());
            }

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable $e) {
            Log::warning('Error generating chairman QR on the fly: '.$e->getMessage());

            return null;
        }
    }
}
