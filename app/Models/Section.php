<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $table = 'sections';

    protected $fillable = [
        'level',
        'pac_id',
        'name',
        'code',
        'description',
    ];

    public function pac()
    {
        return $this->belongsTo(Pac::class, 'pac_id');
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'section_id');
    }
}
