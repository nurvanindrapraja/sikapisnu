<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'member_number',
        'nik',
        'full_name',
        'birth_place',
        'birth_date',
        'gender',
        'address',
        'kelurahan',
        'kecamatan',
        'city',
        'province',
        'phone',
        'email',
        'occupation',
        'photo',
        'membership_status',
        'mwc_id',
        'pac_id',
        'verified_at',
        'verified_by',
        'rejection_note',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function mwc()
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    public function pac()
    {
        return $this->belongsTo(Pac::class, 'pac_id');
    }

    public function educations()
    {
        return $this->hasMany(MemberEducation::class);
    }

    public function organizations()
    {
        return $this->hasMany(MemberOrganization::class);
    }

    public function employments()
    {
        return $this->hasMany(MemberEmployment::class);
    }

    public function nuTrainings()
    {
        return $this->hasMany(MemberNuTraining::class);
    }

    public function certifications()
    {
        return $this->hasMany(MemberCertification::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function activePosition()
    {
        return $this->hasOne(Position::class)->where('is_active', true)->latestOfMany();
    }

    public function statusHistories()
    {
        return $this->hasMany(MembershipStatusHistory::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function activeCard()
    {
        return $this->hasOne(Card::class)->where('is_active', true)->latestOfMany();
    }

    public function cardOrders()
    {
        return $this->hasMany(CardOrder::class);
    }

    public function presences()
    {
        return $this->hasMany(EventPresence::class, 'member_id');
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
                return $this->photo;
            }

            return asset('storage/'.$this->photo);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->full_name).'&background=006837&color=ffffff&size=256';
    }

    public function isPengurus(): bool
    {
        return $this->membership_status === 'pengurus' || $this->positions()->where('is_active', true)->exists();
    }
}
