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

    public function workgroups()
    {
        return $this->belongsToMany(Workgroup::class, 'workgroup_members')
            ->withPivot(['id', 'role', 'notes'])
            ->withTimestamps();
    }

    public function workgroupMembers()
    {
        return $this->hasMany(WorkgroupMember::class, 'member_id');
    }

    public function isEligibleForWorkgroup(): bool
    {
        return $this->activePosition && $this->activePosition->section_id !== null;
    }

    public function getLatestEducation(): ?MemberEducation
    {
        if ($this->relationLoaded('educations')) {
            return $this->educations->sortByDesc(fn ($e) => [$e->end_year ?? 0, $e->id])->first();
        }

        return $this->educations()->orderByDesc('end_year')->orderByDesc('id')->first();
    }

    public function getLatestEmployment(): ?MemberEmployment
    {
        if ($this->relationLoaded('employments')) {
            return $this->employments->sortByDesc(fn ($e) => [$e->end_year ?? 9999, $e->id])->first();
        }

        return $this->employments()->orderByDesc('end_year')->orderByDesc('id')->first();
    }

    public function getLatestCertification(): ?MemberCertification
    {
        if ($this->relationLoaded('certifications')) {
            return $this->certifications->sortByDesc(fn ($c) => [$c->issue_year ?? 0, $c->id])->first();
        }

        return $this->certifications()->orderByDesc('issue_year')->orderByDesc('id')->first();
    }

    /**
     * Mendapatkan data profil singkat anggota untuk ditampilkan pada popup modal publik.
     *
     * @return array<string, mixed>
     */
    public function getShortProfileData(): array
    {
        $latestEdu = $this->getLatestEducation();
        $latestEmp = $this->getLatestEmployment();
        $latestCert = $this->getLatestCertification();

        $eduText = null;
        if ($latestEdu) {
            $parts = array_filter([$latestEdu->level, $latestEdu->major]);
            $eduText = implode(' - ', $parts);
            if ($latestEdu->institution_name) {
                $eduText .= ($eduText ? ' di ' : '').$latestEdu->institution_name;
            }
            if ($latestEdu->end_year) {
                $eduText .= ' (Lulus '.$latestEdu->end_year.')';
            }
        }

        $empText = null;
        if ($latestEmp) {
            $empText = $latestEmp->position ?: $this->occupation;
            if ($latestEmp->company_name) {
                $empText .= ' di '.$latestEmp->company_name;
            }
        } else {
            $empText = $this->occupation ?: null;
        }

        $certText = null;
        if ($latestCert) {
            $certText = $latestCert->certification_name;
            if ($latestCert->field) {
                $certText .= ' (Bidang: '.$latestCert->field.')';
            }
            if ($latestCert->issue_year) {
                $certText .= ' - Tahun '.$latestCert->issue_year;
            }
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'member_number' => $this->member_number ?? '-',
            'membership_status' => $this->membership_status,
            'is_pengurus' => $this->membership_status === 'pengurus',
            'position_title' => $this->activePosition?->position_title,
            'photo_url' => $this->photo_url,
            'mwc_name' => $this->mwc?->name ?? ($this->kecamatan ? 'Kec. '.$this->kecamatan : null),
            'latest_education' => [
                'level' => $latestEdu?->level,
                'major' => $latestEdu?->major,
                'institution' => $latestEdu?->institution_name,
                'degree' => $latestEdu?->degree,
                'year' => $latestEdu?->end_year ? 'Lulus '.$latestEdu->end_year : ($latestEdu?->start_year ? 'Tahun '.$latestEdu->start_year : null),
                'formatted' => $eduText ?? 'Belum ada data pendidikan terakhir',
                'has_data' => $latestEdu !== null,
            ],
            'latest_employment' => [
                'position' => $latestEmp?->position ?? $this->occupation,
                'company' => $latestEmp?->company_name,
                'status' => $latestEmp?->employment_status,
                'year' => $latestEmp?->start_year ? $latestEmp->start_year.($latestEmp->end_year ? ' - '.$latestEmp->end_year : ' - Sekarang') : null,
                'formatted' => $empText ?? 'Belum ada data pekerjaan terakhir',
                'has_data' => ($latestEmp !== null || ! empty($this->occupation)),
            ],
            'latest_certification' => [
                'name' => $latestCert?->certification_name,
                'field' => $latestCert?->field,
                'organizer' => $latestCert?->issuing_organization,
                'year' => $latestCert?->issue_year ? 'Tahun '.$latestCert->issue_year : null,
                'formatted' => $certText ?? 'Belum ada data keahlian / sertifikasi',
                'has_data' => $latestCert !== null,
            ],
        ];
    }
}
