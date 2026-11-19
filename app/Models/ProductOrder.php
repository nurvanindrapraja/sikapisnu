<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAYMENT_UPLOADED = 'payment_uploaded';

    public const STATUS_PAID = 'paid';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'user_id',
        'member_id',
        'total_amount',
        'shipping_cost',
        'grand_total',
        'recipient_name',
        'recipient_phone',
        'shipping_address',
        'notes',
        'status',
        'payment_method',
        'payment_proof_path',
        'payment_uploaded_at',
        'payment_verified_at',
        'payment_verified_by',
        'rejection_reason',
        'courier_name',
        'tracking_number',
        'shipped_at',
        'shipped_by',
        'shipping_notes',
        'received_at',
        'receipt_proof_path',
        'receipt_notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'payment_uploaded_at' => 'datetime',
        'payment_verified_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(5));

        return "ORD-{$date}-{$random}";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class, 'order_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function shippedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'order_id');
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp '.number_format((float) $this->total_amount, 0, ',', '.');
    }

    public function getFormattedShippingCostAttribute(): string
    {
        return 'Rp '.number_format((float) $this->shipping_cost, 0, ',', '.');
    }

    public function getFormattedGrandTotalAttribute(): string
    {
        return 'Rp '.number_format((float) $this->grand_total, 0, ',', '.');
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        if ($this->payment_proof_path && Storage::disk('public')->exists($this->payment_proof_path)) {
            return Storage::disk('public')->url($this->payment_proof_path);
        }

        return null;
    }

    public function getReceiptProofUrlAttribute(): ?string
    {
        if ($this->receipt_proof_path && Storage::disk('public')->exists($this->receipt_proof_path)) {
            return Storage::disk('public')->url($this->receipt_proof_path);
        }

        return null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_PAYMENT => 'Menunggu Pembayaran',
            self::STATUS_PAYMENT_UPLOADED => 'Menunggu Konfirmasi Bendahara',
            self::STATUS_PAID => 'Pembayaran Dikonfirmasi (Diproses)',
            self::STATUS_SHIPPED => 'Barang Dikirim',
            self::STATUS_RECEIVED => 'Pesanan Selesai (Diterima)',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_PAYMENT => 'bg-secondary text-white',
            self::STATUS_PAYMENT_UPLOADED => 'bg-warning text-dark',
            self::STATUS_PAID => 'bg-info text-dark',
            self::STATUS_SHIPPED => 'bg-primary text-white',
            self::STATUS_RECEIVED => 'bg-success text-white',
            self::STATUS_CANCELLED => 'bg-danger text-white',
            default => 'bg-light text-dark',
        };
    }

    public function canUploadPaymentProof(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_PAYMENT, self::STATUS_PAYMENT_UPLOADED], true);
    }

    public function canConfirmReceipt(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function canReview(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isReviewed(): bool
    {
        return $this->reviews()->exists();
    }
}
