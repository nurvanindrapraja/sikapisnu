<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberEducation extends Model
{
    use HasFactory;

    protected $table = 'member_educations';

    protected $fillable = [
        'member_id',
        'level',
        'institution_name',
        'major',
        'start_year',
        'end_year',
        'degree',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
