<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRsvp extends Model
{
    use HasFactory;

    public const STATUS_HADIR = 'hadir';

    public const STATUS_RAGU = 'ragu';

    public const STATUS_TIDAK_HADIR = 'tidak_hadir';

    protected $table = 'event_rsvps';

    protected $fillable = [
        'event_id',
        'member_id',
        'user_id',
        'name',
        'nik_or_member_number',
        'phone',
        'institution_or_pac',
        'attendance_status',
        'notes',
        'confirmed_at',
        'ip_address',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->attendance_status) {
            self::STATUS_HADIR => 'Akan Hadir',
            self::STATUS_RAGU => 'Masih Ragu',
            self::STATUS_TIDAK_HADIR => 'Tidak Hadir',
            default => ucfirst((string) $this->attendance_status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->attendance_status) {
            self::STATUS_HADIR => 'bg-success-subtle text-success border border-success-subtle',
            self::STATUS_RAGU => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::STATUS_TIDAK_HADIR => 'bg-danger-subtle text-danger border border-danger-subtle',
            default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        };
    }
}
