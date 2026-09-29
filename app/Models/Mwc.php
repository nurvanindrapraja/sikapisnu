<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mwc extends Model
{
    use HasFactory;

    protected $table = 'mwc';

    protected $fillable = [
        'code',
        'name',
        'city',
    ];

    public function pacs()
    {
        return $this->hasMany(Pac::class, 'mwc_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'mwc_id');
    }
}
