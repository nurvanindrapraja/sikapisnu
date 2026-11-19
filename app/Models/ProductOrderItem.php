<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'subtotal',
        'variant_note',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format((float) $this->price, 0, ',', '.');
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp '.number_format((float) $this->subtotal, 0, ',', '.');
    }
}
