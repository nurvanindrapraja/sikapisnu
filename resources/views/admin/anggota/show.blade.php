@extends('layouts.admin')

@section('title', 'Detail Profil Anggota')
@section('header_title', 'Profil Lengkap')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold text-dark m-0"><i class="bi bi-person-badge text-success me-2"></i> Detail Profil Kader</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.anggota.cv.download', $member->id) }}" class="btn btn-outline-danger rounded-pill btn-sm px-2.5 px-md-3 shadow-sm fw-semibold" title="Download CV (PDF)">
            <i class="bi bi-file-earmark-pdf-fill"></i>
            <span class="d-none d-md-inline ms-1">Download CV (PDF)</span>
        </a>
        <a href="{{ route('admin.anggota.index') }}" class="btn btn-outline-secondary rounded-pill btn-sm px-2.5 px-md-3 shadow-sm" title="Kembali ke Daftar Kader">
            <i class="bi bi-arrow-left"></i>
            <span class="d-none d-md-inline ms-1">Kembali ke Daftar Kader</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center mb-4">
            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="rounded-3 border border-3 border-success mx-auto mb-3 object-fit-cover shadow-sm" style="width: 140px; height: 165px;">
            <h5 class="fw-bold text-dark mb-1">{{ $member->full_name }}</h5>
            <span class="badge {{ $member->membership_status === 'pengurus' ? 'bg-warning text-dark' : 'bg-success' }} px-3 py-1 mb-3 text-uppercase">
                {{ $member->membership_status }}
            </span>

            <div class="text-start small space-y-2 border-top pt-3">
                <div><span class="text-muted">No. Anggota:</span> <strong class="float-end font-monospace text-success">{{ $member->member_number }}</strong></div>
                <div><span class="text-muted">NIK:</span> <strong class="float-end font-monospace">{{ $member->nik }}</strong></div>
                <div><span class="text-muted">Email:</span> <strong class="float-end">{{ $member->email }}</strong></div>
                <div><span class="text-muted">No. HP:</span> <strong class="float-end">{{ $member->phone }}</strong></div>
                <div><span class="text-muted">Pekerjaan:</span> <strong class="float-end">{{ $member->occupation }}</strong></div>
            </div>

            <!-- Modal Promote Officer Trigger & Demote Officer -->
            <div class="mt-4 pt-3 border-top">
                <button type="button" class="btn btn-warning w-100 fw-bold rounded-pill text-dark mb-2" data-bs-toggle="modal" data-bs-target="#modalPromote">
                    <i class="bi bi-award-fill me-1"></i> {{ $member->membership_status === 'pengurus' ? 'Update Jabatan Pengurus' : 'Jadikan Pengurus ISNU' }}
                </button>
                @if($member->membership_status === 'pengurus')
                    <button type="button" class="btn btn-outline-danger w-100 fw-semibold rounded-pill" onclick="openDemoteModal('{{ route('admin.pengurus.demote', $member->id) }}', '{{ addslashes($member->full_name) }}')">
                        <i class="bi bi-x-circle me-1"></i> Batalkan Status Pengurus
                    </button>
                @endif
            </div>
        </div>

        <!-- Digital Card Preview -->
        <div class="card border-0 shadow-sm rounded-4 p-3 text-center">
            <h6 class="fw-bold text-dark mb-2">Pratinjau Kartu Digital</h6>
            <x-digital_card :member="$member" :card="$member->activeCard" />
        </div>
    </div>

    <!-- Right Column: Full Details & Potensi -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-3 mb-3"><i class="bi bi-card-checklist text-success me-2"></i> Identitas Lengkap</h6>
            <div class="row g-3 small">
                <div class="col-md-6"><span class="text-muted d-block">Tempat, Tanggal Lahir</span><strong>{{ $member->birth_place }}, {{ $member->birth_date ? $member->birth_date->translatedFormat('d F Y') : '-' }}</strong></div>
                <div class="col-md-6"><span class="text-muted d-block">Jenis Kelamin</span><strong>{{ $member->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong></div>
                <div class="col-12"><span class="text-muted d-block">Alamat Rumah</span><strong>{{ $member->address }}, Kel. {{ $member->kelurahan }}, Kec. {{ $member->kecamatan }}</strong></div>
                <div class="col-md-6"><span class="text-muted d-block">MWC NU Kecamatan</span><strong>{{ $member->mwc ? $member->mwc->name : '-' }}</strong></div>
                <div class="col-md-6"><span class="text-muted d-block">Tanggal Verifikasi</span><strong>{{ $member->verified_at ? $member->verified_at->translatedFormat('d F Y') : '-' }}</strong></div>
            </div>
        </div>

        <!-- History & Position -->
        @if($member->activePosition)
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-warning-subtle border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-award-fill text-warning me-1"></i> Jabatan Pengurus Aktif</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-semibold" onclick="openDemoteModal('{{ route('admin.pengurus.demote', $member->id) }}', '{{ addslashes($member->full_name) }}')">
                        <i class="bi bi-x-circle me-1"></i> Batalkan Pengurus
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark m-0">{{ $member->activePosition->position_title }}</h5>
                        <small class="text-muted">Tingkat: {{ $member->activePosition->level }} | Periode: {{ $member->activePosition->period }}</small>
                    </div>
                    @if($member->activePosition->sk_number)
                        <span class="badge bg-dark">SK: {{ $member->activePosition->sk_number }}</span>
                    @endif
                </div>
            </div>
        @endif

        <!-- Potensi Accordion -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-journal-bookmark-fill text-success me-2"></i> Rekam Jejak Potensi Kader</h6>
            
            <div class="accordion" id="accordionPotensi">
                <!-- 1. Riwayat Pendidikan -->
                <div class="accordion-item border-0 mb-2 rounded-3 bg-light">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-transparent fw-bold text-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdu">
                            <i class="bi bi-mortarboard-fill me-2"></i> Riwayat Pendidikan ({{ $member->educations->count() }})
                        </button>
                    </h2>
                    <div id="collapseEdu" class="accordion-collapse collapse show" data-bs-parent="#accordionPotensi">
                        <div class="accordion-body pt-0">
                            @forelse($member->educations as $edu)
                                <div class="border-bottom pb-2 mb-2">
                                    <strong class="text-dark">{{ $edu->level }} @if($edu->major){{ $edu->major }}@endif</strong>
                                    @if($edu->degree) <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">{{ $edu->degree }}</span> @endif
                                    <div class="text-muted small">{{ $edu->institution_name }} ({{ $edu->start_year ?? '?' }} - {{ $edu->end_year ?? 'Sekarang' }})</div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0 fst-italic">Belum ada riwayat pendidikan yang dicatat.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 2. Riwayat Organisasi -->
                <div class="accordion-item border-0 mb-2 rounded-3 bg-light">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-transparent fw-bold text-success collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOrg">
                            <i class="bi bi-people-fill me-2"></i> Riwayat Organisasi ({{ $member->organizations->count() }})
                        </button>
                    </h2>
                    <div id="collapseOrg" class="accordion-collapse collapse" data-bs-parent="#accordionPotensi">
                        <div class="accordion-body pt-0">
                            @forelse($member->organizations as $org)
                                <div class="border-bottom pb-2 mb-2">
                                    <strong class="text-dark">{{ $org->organization_name }}</strong>
                                    @if($org->position) - <span class="fw-semibold text-secondary">{{ $org->position }}</span> @endif
                                    @if($org->period) <div class="text-muted small"><i class="bi bi-calendar-check me-1"></i>Periode: {{ $org->period }}</div> @endif
                                </div>
                            @empty
                                <p class="text-muted small mb-0 fst-italic">Belum ada riwayat organisasi yang dicatat.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 3. Riwayat Pekerjaan & Profesi -->
                <div class="accordion-item border-0 mb-2 rounded-3 bg-light">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-transparent fw-bold text-success collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEmp">
                            <i class="bi bi-briefcase-fill me-2"></i> Riwayat Pekerjaan & Profesi ({{ $member->employments->count() }})
                        </button>
                    </h2>
                    <div id="collapseEmp" class="accordion-collapse collapse" data-bs-parent="#accordionPotensi">
                        <div class="accordion-body pt-0">
                            @forelse($member->employments as $emp)
                                <div class="border-bottom pb-2 mb-2">
                                    <strong class="text-dark">{{ $emp->position }}</strong> - {{ $emp->company_name }}
                                    @if($emp->start_year || $emp->end_year)
                                        <div class="text-muted small"><i class="bi bi-calendar-range me-1"></i>{{ $emp->start_year ?? '?' }} - {{ $emp->end_year ?? 'Sekarang' }}</div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted small mb-0 fst-italic">Belum ada riwayat pekerjaan yang dicatat.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 4. Riwayat Kaderisasi NU -->
                <div class="accordion-item border-0 mb-2 rounded-3 bg-light">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-transparent fw-bold text-success collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNu">
                            <i class="bi bi-award-fill me-2"></i> Riwayat Kaderisasi NU ({{ $member->nuTrainings->count() }})
                        </button>
                    </h2>
                    <div id="collapseNu" class="accordion-collapse collapse" data-bs-parent="#accordionPotensi">
                        <div class="accordion-body pt-0">
                            @forelse($member->nuTrainings as $nu)
                                <div class="border-bottom pb-2 mb-2">
                                    <span class="badge bg-success me-1">{{ $nu->training_type }}</span>
                                    @if($nu->organizer) <strong class="text-dark">{{ $nu->organizer }}</strong> @endif
                                    @if($nu->year) <span class="text-muted">({{ $nu->year }})</span> @endif
                                    @if($nu->certificate_number)
                                        <div class="text-muted small">No. Sertifikat: <code>{{ $nu->certificate_number }}</code></div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted small mb-0 fst-italic">Belum ada kaderisasi NU yang dicatat.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- 5. Sertifikasi Keahlian -->
                <div class="accordion-item border-0 mb-2 rounded-3 bg-light">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-transparent fw-bold text-success collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCert">
                            <i class="bi bi-patch-check-fill me-2"></i> Sertifikasi Keahlian ({{ $member->certifications->count() }})
                        </button>
                    </h2>
                    <div id="collapseCert" class="accordion-collapse collapse" data-bs-parent="#accordionPotensi">
                        <div class="accordion-body pt-0">
                            @forelse($member->certifications as $cert)
                                <div class="border-bottom pb-2 mb-2">
                                    <strong class="text-dark">{{ $cert->certification_name }}</strong>
                                    @if($cert->field) <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle ms-1">{{ $cert->field }}</span> @endif
                                    @if($cert->issue_year) <span class="text-muted ms-1">- {{ $cert->issue_year }}</span> @endif
                                    @if($cert->certificate_number)
                                        <div class="text-muted small">No. Sertifikat: <code>{{ $cert->certificate_number }}</code></div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted small mb-0 fst-italic">Belum ada sertifikasi keahlian yang dicatat.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Promote Officer -->
<div class="modal fade" id="modalPromote" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.pengurus.promote', $member->id) }}" method="POST" enctype="multipart/form-data" class="modal-content" x-data="{
            levelChoice: '{{ (optional($member->activePosition)->level === 'PAC' || optional($member->activePosition)->level === 'PAC ISNU') ? 'PAC ISNU' : 'PC ISNU' }}',
            selectedPacId: '{{ optional($member->activePosition)->pac_id ?? $member->pac_id ?? '' }}'
        }">
            @csrf
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-award-fill me-1"></i> Form Penetapan Pengurus ISNU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jabatan Pengurus <span class="text-danger">*</span></label>
                        <input type="text" name="position_title" class="form-control" placeholder="Contoh: Ketua Umum, Sekretaris, Bendahara..." value="{{ $member->activePosition ? $member->activePosition->position_title : '' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tingkat Kepengurusan <span class="text-danger">*</span></label>
                        <select name="level" class="form-select" x-model="levelChoice" required>
                            <option value="PC ISNU">PC ISNU Kota Surabaya</option>
                            <option value="PAC ISNU">PAC ISNU</option>
                        </select>
                    </div>
                    <div class="col-md-6" x-show="levelChoice === 'PAC ISNU'" x-transition>
                        <label class="form-label fw-semibold">List PAC ISNU <span class="text-danger">*</span></label>
                        <select name="pac_id" class="form-select" x-model="selectedPacId" :required="levelChoice === 'PAC ISNU'">
                            <option value="">-- Pilih PAC ISNU --</option>
                            @foreach($pacs as $p)
                                <option value="{{ $p->id }}" {{ (optional($member->activePosition)->pac_id == $p->id || $member->pac_id == $p->id) ? 'selected' : '' }}>PAC {{ $p->name }} (Kec. {{ $p->kecamatan ?? $p->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pilih Seksi / Bidang <span class="text-secondary font-normal">(Opsional)</span></label>
                        <select name="section_id" class="form-select">
                            <option value="">-- Tanpa Seksi (Ketua/Sekretaris/Bendahara) --</option>
                            @foreach($sections as $sec)
                                @if($sec->level === 'PC ISNU')
                                    <option value="{{ $sec->id }}" 
                                            x-show="levelChoice === 'PC ISNU'"
                                            {{ optional($member->activePosition)->section_id == $sec->id ? 'selected' : '' }}>
                                        {{ $sec->name }}
                                    </option>
                                @else
                                    <option value="{{ $sec->id }}" 
                                            x-show="levelChoice === 'PAC ISNU' && selectedPacId == '{{ $sec->pac_id }}'"
                                            {{ optional($member->activePosition)->section_id == $sec->id ? 'selected' : '' }}>
                                        {{ $sec->name }} @if($sec->pac) (PAC {{ $sec->pac->name }}) @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Bagi Ketua/Sekretaris/Bendahara tidak perlu memilih Seksi.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Periode Kepengurusan <span class="text-danger">*</span></label>
                        <input type="text" name="period" class="form-control" placeholder="Contoh: 2026-2030" value="{{ optional($member->activePosition)->period ?? '2026-2030' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor SK Pengurus</label>
                        <input type="text" name="sk_number" class="form-control" placeholder="SK-PW-ISNU/2026/..." value="{{ optional($member->activePosition)->sk_number }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Upload File SK (PDF/Gambar)</label>
                        <input type="file" name="sk_file" class="form-control" accept="application/pdf,image/*">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark">Simpan Status Pengurus & Update Kartu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Pembatalan Status Pengurus -->
<div class="modal fade" id="modalDemoteOfficer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Pembatalan Pengurus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-person-x-fill text-danger display-3 d-block mb-3"></i>
                <p class="fs-6 text-dark mb-1">Apakah Anda yakin ingin membatalkan status pengurus untuk:</p>
                <h6 class="fw-bold text-danger mb-3" id="demoteMemberName"></h6>
                <p class="small text-muted mb-0">Anggota ini akan kembali ke status <strong>Anggota Terverifikasi</strong> biasa dan jenis Kartu Digital kembali ke Kartu Anggota.</p>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                <form id="formDemoteOfficer" action="" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="bi bi-x-circle me-1"></i> Ya, Batalkan Status Pengurus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openDemoteModal(url, name) {
    document.getElementById('formDemoteOfficer').action = url;
    document.getElementById('demoteMemberName').textContent = name;
    const modalEl = document.getElementById('modalDemoteOfficer');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
@endsection
