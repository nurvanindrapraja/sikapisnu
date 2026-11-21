<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pac extends Model
{
    use HasFactory;

    protected $table = 'pac';

    protected $fillable = [
        'mwc_id',
        'code',
        'name',
        'province',
        'city',
        'kecamatan',
        'stamp_path',
    ];

    public function mwc()
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'pac_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $raw = trim((string) $this->name);
        $clean = preg_replace('/^(PAC\s+ISNU\s+|PAC\s+)/i', '', $raw);

        return 'PAC ISNU '.$clean;
    }

    public function getResolvedStampPathAttribute(): ?string
    {
        if ($this->stamp_path && Storage::disk('public')->exists($this->stamp_path)) {
            return $this->stamp_path;
        }

        $fallbackPath = "stamps/pac_{$this->id}_stamp.png";
        if (Storage::disk('public')->exists($fallbackPath)) {
            return $fallbackPath;
        }

        return null;
    }

    public function getStampUrlAttribute(): ?string
    {
        $path = $this->resolved_stamp_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function hasStamp(): bool
    {
        return $this->resolved_stamp_path !== null;
    }
}
