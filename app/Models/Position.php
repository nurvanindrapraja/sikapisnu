<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $table = 'positions';

    protected $fillable = [
        'member_id',
        'position_title',
        'level',
        'mwc_id',
        'pac_id',
        'section_id',
        'period',
        'period_start',
        'period_end',
        'sk_number',
        'sk_file',
        'is_active',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'is_active' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function mwc()
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    public function pac()
    {
        return $this->belongsTo(Pac::class, 'pac_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
