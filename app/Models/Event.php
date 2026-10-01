<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'unique_code',
        'description',
        'location',
        'method',
        'meeting_link',
        'event_date',
        'start_time',
        'end_time',
        'presence_start_at',
        'presence_end_at',
        'status',
        'lpj_file',
        'documentation_photos',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'presence_start_at' => 'datetime',
        'presence_end_at' => 'datetime',
        'documentation_photos' => 'array',
    ];

    public function presences()
    {
        return $this->hasMany(EventPresence::class, 'event_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
