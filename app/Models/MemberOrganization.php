<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberOrganization extends Model
{
    use HasFactory;

    protected $table = 'member_organizations';

    protected $fillable = [
        'member_id',
        'organization_name',
        'position',
        'level',
        'period',
        'description',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
