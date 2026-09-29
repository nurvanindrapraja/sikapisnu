<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberEmployment extends Model
{
    use HasFactory;

    protected $table = 'member_employments';

    protected $fillable = [
        'member_id',
        'company_name',
        'position',
        'field',
        'start_year',
        'end_year',
        'employment_status',
        'description',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
