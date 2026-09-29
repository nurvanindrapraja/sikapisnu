<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberNuTraining extends Model
{
    use HasFactory;

    protected $table = 'member_nu_trainings';

    protected $fillable = [
        'member_id',
        'training_type',
        'organizer',
        'year',
        'location',
        'certificate_number',
        'certificate_file',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
