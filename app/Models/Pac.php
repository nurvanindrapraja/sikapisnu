<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    public function mwc()
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'pac_id');
    }
}
