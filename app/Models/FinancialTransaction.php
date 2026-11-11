<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const CATEGORIES_IN = [
        'Iuran Kas Anggota',
        'Donasi / Infaq',
        'Sponsorship / Hibah',
        'Penjualan Merchandise / KTA',
        'Bagi Hasil Unit Usaha',
        'Bantuan Pihak Ketiga',
        'Penerimaan Lainnya',
    ];

    public const CATEGORIES_OUT = [
        'Operasional Kantor & Sekretariat',
        'Konsumsi Rapat & Koordinasi',
        'Pelaksanaan Kegiatan & Acara',
        'ATK, Cetak & Kesekretariatan',
        'Transportasi & Perjalanan Dinas',
        'Publikasi, Dokumentasi & IT',
        'Bantuan Sosial & Santunan',
        'Pengadaan Inventaris / Aset',
        'Pengeluaran Lainnya',
    ];

    public const PAYMENT_METHODS = [
        'Tunai / Kas',
        'Transfer Bank',
        'QRIS',
        'Lainnya',
    ];

    protected $table = 'financial_transactions';

    protected $fillable = [
        'transaction_code',
        'type',
        'category',
        'amount',
        'transaction_date',
        'title',
        'description',
        'payment_method',
        'proof_path',
        'level',
        'pac_id',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pac(): BelongsTo
    {
        return $this->belongsTo(Pac::class);
    }

    public function isIncome(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function isExpense(): bool
    {
        return $this->type === self::TYPE_OUT;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->isIncome() ? 'Uang Masuk' : 'Uang Keluar';
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format((float) $this->amount, 0, ',', '.');
    }

    public function getFormattedDateAttribute(): string
    {
        if (! $this->transaction_date) {
            return '-';
        }

        return Carbon::parse($this->transaction_date)->translatedFormat('d M Y');
    }

    /**
     * Generate unique transaction code.
     * Format: KM-YYYYMMDD-XXXX (Kas Masuk) / KK-YYYYMMDD-XXXX (Kas Keluar)
     */
    public static function generateTransactionCode(string $type): string
    {
        $prefix = $type === self::TYPE_IN ? 'KM' : 'KK';
        $datePart = date('Ymd');

        do {
            $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
            $code = "{$prefix}-{$datePart}-{$random}";
        } while (static::where('transaction_code', $code)->exists());

        return $code;
    }
}
