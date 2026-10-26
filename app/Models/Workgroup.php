<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workgroup extends Model
{
    use HasFactory;

    protected $table = 'workgroups';

    protected $fillable = [
        'level',
        'pac_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke PAC jika tingkat PAC ISNU.
     */
    public function pac(): BelongsTo
    {
        return $this->belongsTo(Pac::class, 'pac_id');
    }

    /**
     * Relasi anggota (Pengurus Seksi) dalam Kelompok Kerja.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'workgroup_members')
            ->withPivot(['id', 'role', 'notes'])
            ->withTimestamps();
    }

    /**
     * Relasi ke entitas pivot WorkgroupMember.
     */
    public function workgroupMembers(): HasMany
    {
        return $this->hasMany(WorkgroupMember::class, 'workgroup_id');
    }
}
