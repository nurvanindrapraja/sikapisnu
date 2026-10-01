<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventPresence extends Model
{
    use HasFactory;

    protected $table = 'event_presences';

    protected $fillable = [
        'event_id',
        'member_id',
        'user_id',
        'name',
        'nik_or_member_number',
        'phone',
        'institution_or_pac',
        'notes',
        'attended_at',
        'ip_address',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
