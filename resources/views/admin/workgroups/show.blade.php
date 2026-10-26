@extends('layouts.admin')

@section('title', 'Kelola Anggota Pokja: ' . $workgroup->name . ' - SIKAP ISNU')
@section('header_title', 'Kelola Anggota Kelompok Kerja')

@section('content')
<!-- Breadcrumbs & Navigasi -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.workgroups.index') }}" class="text-decoration-none text-success">Master Kelompok Kerja</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($workgroup->name, 35) }}</li>
            </ol>
        </nav>
        <h4 class="fw-bold text-dark m-0">
            <i class="bi bi-person-workspace text-success me-2"></i> {{ $workgroup->name }}
        </h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.workgroups.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
        <button type="button" class="btn btn-sm btn-isnu-primary px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddPokjaMember">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota dari Seksi
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Info Card Header Pokja -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            <div class="col-md-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    @if($workgroup->level === 'PC ISNU')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill">
                            <i class="bi bi-building me-1"></i> PC ISNU Kota Surabaya
                        </span>
                    @else
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill">
                            <i class="bi bi-geo-alt me-1"></i> PAC ISNU {{ $workgroup->pac->name ?? '' }}
                        </span>
                    @endif

                    @if($workgroup->code)
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill">
                            <code>{{ $workgroup->code }}</code>
                        </span>
                    @endif

                    @if($workgroup->is_active)
                        <span class="badge bg-success text-white px-2.5 py-1 rounded-pill">
                            <i class="bi bi-check-circle me-1"></i> Aktif
                        </span>
                    @else
                        <span class="badge bg-secondary text-white px-2.5 py-1 rounded-pill">
                            <i class="bi bi-dash-circle me-1"></i> Non-Aktif
                        </span>
                    @endif
                </div>

                <p class="text-secondary mb-0">
                    {{ $workgroup->description ?: 'Belum ada deskripsi khusus untuk kelompok kerja ini.' }}
                </p>
            </div>

            <div class="col-md-4 text-md-end">
                <div class="d-inline-flex flex-column align-items-md-end bg-light p-3 rounded-4 border">
                    <span class="text-muted small">Total Anggota Pokja</span>
                    <h3 class="fw-bold text-success mb-0">
                        {{ $workgroup->members->count() }} <small class="fs-6 fw-normal text-muted">Pengurus Seksi</small>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ketentuan Khusus Pokja -->
<div class="alert alert-info bg-info-subtle border-info-subtle rounded-4 p-3 mb-4 shadow-sm">
    <div class="d-flex align-items-start gap-3">
        <div class="bg-info text-white rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
            <i class="bi bi-shield-check fs-5"></i>
        </div>
        <div class="small">
            <strong class="text-info-emphasis d-block mb-1">Struktur Khusus: Pengurus Seksi Merangkap di Pokja</strong>
            <span class="text-secondary">
                Kelompok Kerja tidak menerima anggota di luar seksi. Setiap kader yang terdaftar di bawah ini <strong>tetap memegang jabatan aktif di Seksi asalnya</strong>, dan penugasan di Pokja ini merupakan gugus tugas tambahan strategis.
            </span>
        </div>
    </div>
</div>

<!-- Daftar Anggota Pokja -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white border-0 p-3 p-md-4 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-success"></i> Anggota Kelompok Kerja yang Ditugaskan
            </h6>
            <small class="text-muted">Daftar pengurus seksi yang saat ini aktif bernaung dalam Pokja ini</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAddPokjaMember">
            <i class="bi bi-plus-lg me-1"></i> Tambah Anggota
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Pengurus</th>
                    <th>Seksi Asal (Struktural)</th>
                    <th>Jabatan Utama</th>
                    <th>Peran di Pokja</th>
                    <th>Catatan Penugasan</th>
                    <th>Tgl Bergabung</th>
                    <th class="text-end" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workgroup->members as $index => $m)
                    @php
                        $pos = $m->activePosition;
                        $seksi = $pos?->section;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover border border-2 border-warning" style="width: 40px; height: 40px;">
                                <div>
                                    <strong class="d-block text-dark">{{ $m->full_name }}</strong>
                                    <small class="text-muted">{{ $m->member_number }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($seksi)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                                    <i class="bi bi-diagram-2 me-1"></i> {{ $seksi->name }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-secondary small">{{ $pos ? $pos->position_title : 'Pengurus' }}</strong>
                            <small class="d-block text-muted">
                                {{ $pos?->level === 'PAC' || $pos?->level === 'PAC ISNU' ? 'PAC ISNU '.($pos->pac->name ?? '') : 'PC ISNU' }}
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark px-2.5 py-1.5 fw-bold rounded-pill">
                                <i class="bi bi-person-badge me-1"></i> {{ $m->pivot->role ?? 'Anggota Pokja' }}
                            </span>
                        </td>
                        <td class="text-secondary small">
                            {{ $m->pivot->notes ?: '-' }}
                        </td>
                        <td class="text-muted small">
                            {{ $m->pivot->created_at ? $m->pivot->created_at->format('d M Y') : '-' }}
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                        style="width: 32px; height: 32px;"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEditMemberRole{{ $m->id }}" 
                                        title="Ubah Peran di Pokja">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                        style="width: 32px; height: 32px;"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalRemoveMember{{ $m->id }}" 
                                        title="Keluarkan dari Pokja">
                                    <i class="bi bi-person-x-fill"></i>
                                </button>
                                <a href="{{ route('admin.anggota.show', $m->id) }}" class="btn btn-sm btn-outline-secondary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Lihat Profil Anggota" target="_blank">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>

                            <!-- Modal Ubah Peran Anggota Pokja -->
                            <div class="modal fade text-start" id="modalEditMemberRole{{ $m->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                        <form action="{{ route('admin.workgroups.members.update', [$workgroup->id, $m->id]) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-warning text-dark border-0 p-3">
                                                <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1"></i> Ubah Peran di Pokja</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-semibold">Nama Pengurus</label>
                                                    <div class="fw-bold text-dark">{{ $m->full_name }}</div>
                                                    <div class="text-success small">Seksi: {{ $seksi?->name ?? '-' }}</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Peran / Posisi dalam Pokja <span class="text-danger">*</span></label>
                                                    <select name="role" class="form-select" required>
                                                        <option value="Ketua Pokja" {{ $m->pivot->role === 'Ketua Pokja' ? 'selected' : '' }}>Ketua Pokja</option>
                                                        <option value="Wakil Ketua Pokja" {{ $m->pivot->role === 'Wakil Ketua Pokja' ? 'selected' : '' }}>Wakil Ketua Pokja</option>
                                                        <option value="Sekretaris Pokja" {{ $m->pivot->role === 'Sekretaris Pokja' ? 'selected' : '' }}>Sekretaris Pokja</option>
                                                        <option value="Bendahara Pokja" {{ $m->pivot->role === 'Bendahara Pokja' ? 'selected' : '' }}>Bendahara Pokja</option>
                                                        <option value="Koordinator Bidang" {{ $m->pivot->role === 'Koordinator Bidang' ? 'selected' : '' }}>Koordinator Bidang</option>
                                                        <option value="Anggota Pokja" {{ $m->pivot->role === 'Anggota Pokja' ? 'selected' : '' }}>Anggota Pokja</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Catatan Penugasan / Fokus Tugas</label>
                                                    <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: Bertanggung jawab pada pengembangan modul presensi barcode">{{ $m->pivot->notes }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light border-0 px-4 py-3">
                                                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-sm btn-warning fw-bold rounded-pill px-4">
                                                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Konfirmasi Hapus Anggota Pokja -->
                            <div class="modal fade text-start" id="modalRemoveMember{{ $m->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                        <div class="modal-body text-center p-4">
                                            <div class="text-danger mb-3">
                                                <i class="bi bi-person-x-fill fs-1"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">Keluarkan dari Pokja?</h6>
                                            <p class="text-muted small mb-3">
                                                Keluarkan <strong>"{{ $m->full_name }}"</strong> dari gugus tugas Pokja ini?
                                            </p>
                                            <p class="text-success small mb-4 bg-success-subtle p-2 rounded-3">
                                                <i class="bi bi-info-circle me-1"></i> Status kepengurusan di Seksi <strong>{{ $seksi?->name ?? 'Seksi' }}</strong> tetap aktif dan tidak berubah.
                                            </p>
                                            <form action="{{ route('admin.workgroups.members.remove', [$workgroup->id, $m->id]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">
                                                        <i class="bi bi-trash-fill me-1"></i> Ya, Keluarkan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                            Belum ada pengurus seksi yang ditugaskan ke Kelompok Kerja ini.
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-isnu-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalAddPokjaMember">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Anggota Pertama
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Anggota Pokja Baru -->
<div class="modal fade" id="modalAddPokjaMember" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="{{ route('admin.workgroups.members.add', $workgroup->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-isnu text-white border-0 p-3">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota ke {{ $workgroup->name }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning bg-warning-subtle border-warning-subtle text-dark small rounded-3 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
                        <strong>Pemberitahuan:</strong> Hanya pengurus yang <strong>sudah terdaftar dalam Seksi</strong> yang muncul di daftar pilihan di bawah ini. Pengurus di luar seksi atau kader non-pengurus tidak dapat dimasukkan ke Pokja.
                    </div>

                    @if($eligibleMembers->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i>
                            <h6 class="fw-bold text-dark">Tidak Ada Kandidat Pengurus Seksi yang Tersedia</h6>
                            <p class="text-muted small mb-0">Semua pengurus seksi yang memenuhi syarat sudah tergabung dalam Kelompok Kerja ini, atau belum ada pengurus yang ditempatkan pada Seksi.</p>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Pengurus dari Seksi <span class="text-danger">*</span></label>
                            <select name="member_id" class="form-select" required>
                                <option value="">-- Pilih Pengurus Seksi --</option>
                                @foreach($eligibleMembers as $em)
                                    @php
                                        $emPos = $em->activePosition;
                                        $emSeksi = $emPos?->section;
                                    @endphp
                                    <option value="{{ $em->id }}">
                                        {{ $em->full_name }} — [{{ $emSeksi?->name ?? 'Seksi' }}] ({{ $emPos?->position_title ?? 'Pengurus' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted">Format: [Nama Kader] — [Seksi Asal] ([Jabatan Struktural])</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Peran / Jabatan dalam Pokja <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="Anggota Pokja" selected>Anggota Pokja</option>
                                <option value="Ketua Pokja">Ketua Pokja</option>
                                <option value="Wakil Ketua Pokja">Wakil Ketua Pokja</option>
                                <option value="Sekretaris Pokja">Sekretaris Pokja</option>
                                <option value="Bendahara Pokja">Bendahara Pokja</option>
                                <option value="Koordinator Bidang">Koordinator Bidang</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Penugasan / Fokus Tugas <span class="text-secondary small">(Opsional)</span></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Tuliskan spesifikasi peran atau tanggung jawab pengurus pada pokja ini..."></textarea>
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    @if($eligibleMembers->isNotEmpty())
                        <button type="submit" class="btn btn-sm btn-isnu-primary fw-bold rounded-pill px-4">
                            <i class="bi bi-check-lg me-1"></i> Tambahkan ke Pokja
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
