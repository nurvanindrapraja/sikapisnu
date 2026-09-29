<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberCertification extends Model
{
    use HasFactory;

    protected $table = 'member_certifications';

    protected $fillable = [
        'member_id',
        'certification_name',
        'issuing_organization',
        'certificate_number',
        'issue_year',
        'valid_until',
        'field',
        'certificate_file',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
