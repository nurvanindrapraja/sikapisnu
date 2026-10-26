<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkgroupMember extends Model
{
    use HasFactory;

    protected $table = 'workgroup_members';

    protected $fillable = [
        'workgroup_id',
        'member_id',
        'role',
        'notes',
    ];

    /**
     * Kelompok Kerja tempat pengurus bernaung.
     */
    public function workgroup(): BelongsTo
    {
        return $this->belongsTo(Workgroup::class, 'workgroup_id');
    }

    /**
     * Anggota / Pengurus Seksi yang ditugaskan.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
