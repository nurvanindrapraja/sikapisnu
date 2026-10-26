<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function pac(): BelongsTo
    {
        return $this->belongsTo(Pac::class, 'pac_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'section_id');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'section_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'section_id');
    }
}
