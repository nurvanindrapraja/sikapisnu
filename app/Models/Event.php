<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'unique_code',
        'description',
        'section_id',
        'program_id',
        'location',
        'method',
        'meeting_link',
        'event_date',
        'start_time',
        'end_time',
        'presence_start_at',
        'presence_end_at',
        'status',
        'budget',
        'lpj_file',
        'documentation_photos',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'presence_start_at' => 'datetime',
        'presence_end_at' => 'datetime',
        'documentation_photos' => 'array',
        'budget' => 'decimal:2',
    ];

    public function presences(): HasMany
    {
        return $this->hasMany(EventPresence::class, 'event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Seksi / Bidang pengampu kegiatan.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Program kerja yang menaungi kegiatan ini.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function getFormattedBudgetAttribute(): ?string
    {
        if ($this->budget === null) {
            return null;
        }

        return 'Rp '.number_format((float) $this->budget, 0, ',', '.');
    }

    public function isPresenceActive(): bool
    {
        $now = now();

        return $now->greaterThanOrEqualTo($this->presence_start_at) && $now->lessThanOrEqualTo($this->presence_end_at);
    }

    public function presenceStatus(): string
    {
        $now = now();
        if ($now->lessThan($this->presence_start_at)) {
            return 'not_started';
        }
        if ($now->greaterThan($this->presence_end_at)) {
            return 'ended';
        }

        return 'active';
    }

    public function getPresenceMessageAttribute(): string
    {
        $status = $this->presenceStatus();
        if ($status === 'not_started') {
            return 'Acara belum dimulai. Presensi kehadiran dapat dilakukan mulai '.$this->presence_start_at->translatedFormat('d F Y H:i').' WIB.';
        }
        if ($status === 'ended') {
            return 'Acara telah selesai / Masa presensi telah berakhir pada '.$this->presence_end_at->translatedFormat('d F Y H:i').' WIB.';
        }

        return 'Masa presensi sedang aktif.';
    }
}
