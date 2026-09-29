<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'status',
        'shipping_address',
        'phone',
        'notes',
        'payment_proof',
        'ordered_at',
        'printed_at',
        'shipped_at',
        'delivered_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'printed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
