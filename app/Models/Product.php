<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'price',
        'weight_grams',
        'stock',
        'image_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight_grams' => 'integer',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name).'-'.strtolower(Str::random(5));
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true)->latest();
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format((float) $this->price, 0, ',', '.');
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }

        if ($this->image_path && file_exists(public_path('images/'.$this->image_path))) {
            return asset('images/'.$this->image_path);
        }

        return asset('images/logo_isnu.png');
    }

    public function hasRealImage(): bool
    {
        return (! empty($this->image_path) && Storage::disk('public')->exists($this->image_path))
            || (! empty($this->image_path) && file_exists(public_path('images/'.$this->image_path)));
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'seragam' => 'Seragam Resmi',
            'batik' => 'Batik ISNU',
            'kaos' => 'Kaos & Polo',
            'pin' => 'Pin & Lencana',
            'buku' => 'Buku & Literatur',
            'aksesoris' => 'Aksesoris & Cinderamata',
            default => 'Lainnya',
        };
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return match ($this->category) {
            'seragam' => 'bg-success text-white',
            'batik' => 'bg-primary text-white',
            'kaos' => 'bg-info text-dark',
            'pin' => 'bg-warning text-dark',
            'buku' => 'bg-secondary text-white',
            default => 'bg-dark text-white',
        };
    }

    public function getAverageRatingAttribute(): float
    {
        $avg = $this->reviews()->avg('rating');

        return $avg ? round((float) $avg, 1) : 5.0;
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, ?string $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }

        return $query;
    }
}
