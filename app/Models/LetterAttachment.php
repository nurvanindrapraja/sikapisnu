<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LetterAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_id',
        'file_path',
        'file_name',
        'file_size',
    ];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '-';
        }

        $bytes = (float) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), 1, '.', '').' '.$units[$power];
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
