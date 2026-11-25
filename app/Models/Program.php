<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $table = 'programs';

    protected $fillable = [
        'section_id',
        'workgroup_id',
        'name',
        'code',
        'indicator',
        'target',
        'description',
        'year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Seksi / Bidang yang menaungi program ini.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Kelompok Kerja (Pokja) yang menaungi program ini.
     */
    public function workgroup(): BelongsTo
    {
        return $this->belongsTo(Workgroup::class, 'workgroup_id');
    }

    /**
     * Kegiatan-kegiatan di bawah naungan program ini.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'program_id');
    }

    public function isOwnedByWorkgroup(): bool
    {
        return ! empty($this->workgroup_id);
    }

    public function getOwnerKeyAttribute(): string
    {
        if ($this->workgroup_id) {
            return 'workgroup:'.$this->workgroup_id;
        }

        if ($this->section_id) {
            return 'section:'.$this->section_id;
        }

        return '';
    }

    public function getOwnerNameAttribute(): string
    {
        return $this->workgroup?->name ?? $this->section?->name ?? '-';
    }

    public function getOwnerLevelAttribute(): ?string
    {
        return $this->workgroup?->level ?? $this->section?->level;
    }
}
