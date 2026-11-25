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
        'workgroup_id',
        'program_id',
        'location',
        'method',
        'meeting_link',
        'event_date',
        'start_time',
        'end_time',
        'presence_start_at',
        'presence_end_at',
        'rsvp_enabled',
        'rsvp_deadline_at',
        'status',
        'budget',
        'lpj_file',
        'documentation_photos',
        'media_links',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'presence_start_at' => 'datetime',
        'presence_end_at' => 'datetime',
        'rsvp_enabled' => 'boolean',
        'rsvp_deadline_at' => 'datetime',
        'documentation_photos' => 'array',
        'media_links' => 'array',
        'budget' => 'decimal:2',
    ];

    public function presences(): HasMany
    {
        return $this->hasMany(EventPresence::class, 'event_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class, 'event_id');
    }

    public function attendingRsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class, 'event_id')
            ->where('attendance_status', EventRsvp::STATUS_HADIR);
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
     * Kelompok Kerja (Pokja) pengampu kegiatan.
     */
    public function workgroup(): BelongsTo
    {
        return $this->belongsTo(Workgroup::class, 'workgroup_id');
    }

    /**
     * Program kerja yang menaungi kegiatan ini.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
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

    public function rsvpStatus(): string
    {
        if (! $this->rsvp_enabled) {
            return 'disabled';
        }

        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->status === 'completed') {
            return 'completed';
        }

        if ($this->rsvp_deadline_at && now()->greaterThan($this->rsvp_deadline_at)) {
            return 'ended';
        }

        return 'open';
    }

    public function isRsvpOpen(): bool
    {
        return $this->rsvpStatus() === 'open';
    }

    public function getRsvpMessageAttribute(): string
    {
        return match ($this->rsvpStatus()) {
            'disabled' => 'Konfirmasi kehadiran (RSVP) tidak diaktifkan untuk kegiatan ini.',
            'cancelled' => 'Kegiatan ini telah dibatalkan sehingga konfirmasi kehadiran ditutup.',
            'completed' => 'Kegiatan ini telah selesai dilaksanakan sehingga konfirmasi kehadiran ditutup.',
            'ended' => 'Batas waktu konfirmasi kehadiran telah berakhir pada '.$this->rsvp_deadline_at?->translatedFormat('d F Y H:i').' WIB.',
            default => 'Konfirmasi kehadiran sedang dibuka.',
        };
    }
}
