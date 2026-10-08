<!-- Desktop View: Table Data (Hidden on Mobile) -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nomor Anggota</th>
                <th>Nama & Profil</th>
                <th>Pekerjaan / Profesi</th>
                <th>Kecamatan</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $index => $m)
                <tr>
                    <td>{{ $members->firstItem() + $index }}</td>
                    <td><strong class="font-monospace text-success">{{ $m->member_number ?? '-' }}</strong></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover" style="width: 38px; height: 38px;">
                            <div>
                                <strong class="d-block text-dark">{{ $m->full_name }}</strong>
                                <small class="text-muted">{{ $m->email }} | {{ $m->phone }}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $m->occupation }}</span></td>
                    <td>{{ $m->kecamatan ?? '-' }}</td>
                    <td>
                        <span class="badge @if($m->membership_status === 'menunggu_verifikasi') bg-secondary text-white @elseif($m->membership_status === 'terverifikasi') bg-success text-white @elseif($m->membership_status === 'pengurus') bg-warning text-dark @elseif($m->membership_status === 'perbaikan') bg-info text-white @elseif($m->membership_status === 'ditolak') bg-danger text-white @else bg-secondary text-white @endif px-3 py-1">
                            {{ strtoupper($m->membership_status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            @if(in_array($m->membership_status, ['terverifikasi', 'pengurus']))
                                <button type="button" class="btn btn-sm btn-outline-success rounded-circle" title="Download Kartu Anggota (PNG/PDF)" data-bs-toggle="tooltip" onclick="openCardDownloadModal('{{ route('admin.anggota.card.download', $m->id) }}', '{{ addslashes($m->full_name) }}', '{{ $m->member_number ?? '' }}')">
                                    <i class="bi bi-person-vcard-fill"></i>
                                </button>
                            @endif
                            <a href="{{ route('admin.anggota.cv.download', $m->id) }}" class="btn btn-sm btn-outline-danger rounded-circle" title="Download CV (PDF)" data-bs-toggle="tooltip">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </a>
                            <a href="{{ route('admin.anggota.show', $m->id) }}" class="btn btn-sm btn-outline-info rounded-circle" title="Detail Profil" data-bs-toggle="tooltip">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.anggota.edit', $m->id) }}" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Data" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Data" data-bs-toggle="tooltip" @click="confirmDelete('{{ route('admin.anggota.destroy', $m->id) }}', '{{ addslashes($m->full_name) }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada data anggota yang sesuai dengan kriteria filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile View: Card List View (Only Visible on Mobile) -->
<div class="d-md-none">
    @forelse($members as $index => $m)
        <div class="card border border-light-subtle shadow-sm rounded-4 p-3 mb-3 position-relative">
            <!-- Badge Status di Pojok Kanan Atas -->
            <span class="badge position-absolute top-0 end-0 m-3 @if($m->membership_status === 'menunggu_verifikasi') bg-secondary text-white @elseif($m->membership_status === 'terverifikasi') bg-success text-white @elseif($m->membership_status === 'pengurus') bg-warning text-dark @elseif($m->membership_status === 'perbaikan') bg-info text-white @elseif($m->membership_status === 'ditolak') bg-danger text-white @else bg-secondary text-white @endif px-2 py-1 text-wrap" style="max-width: 120px; font-size: 0.65rem; z-index: 1;">
                {{ strtoupper($m->membership_status) }}
            </span>

            <div class="d-flex align-items-start gap-3 mb-3" style="padding-right: 110px;">
                <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover border flex-shrink-0" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px; border-radius: 50%;">
                <div class="flex-grow-1 min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                    <h6 class="fw-bold text-dark mb-1 text-wrap text-break lh-sm" style="font-size: 0.95rem;">{{ $m->full_name }}</h6>
                    <small class="font-monospace text-success fw-bold d-block text-break">{{ $m->member_number ?? '-' }}</small>
                </div>
            </div>

            <div class="small mb-3">
                <div class="mb-2">
                    <span class="text-muted d-block mb-1">Pekerjaan / Profesi</span>
                    <span class="badge bg-light text-dark border text-wrap text-start text-break lh-base" style="white-space: normal; display: inline-block; max-width: 100%;">
                        {{ $m->occupation }}
                    </span>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <span class="text-muted d-block">Kecamatan</span>
                        <span class="fw-semibold text-dark text-break">{{ $m->kecamatan ?? '-' }}</span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">No. HP / WA</span>
                        <span class="text-dark fw-semibold text-break">{{ $m->phone }}</span>
                    </div>
                    <div class="col-12">
                        <span class="text-muted d-block">Email</span>
                        <span class="text-dark text-break">{{ $m->email }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center gap-2 pt-2 border-top">
                @if(in_array($m->membership_status, ['terverifikasi', 'pengurus']))
                    <button type="button" class="btn btn-sm btn-outline-success rounded-circle" title="Download Kartu Anggota (PNG/PDF)" data-bs-toggle="tooltip" onclick="openCardDownloadModal('{{ route('admin.anggota.card.download', $m->id) }}', '{{ addslashes($m->full_name) }}', '{{ $m->member_number ?? '' }}')">
                        <i class="bi bi-person-vcard-fill"></i>
                    </button>
                @endif
                <a href="{{ route('admin.anggota.cv.download', $m->id) }}" class="btn btn-sm btn-outline-danger rounded-circle" title="Download CV (PDF)" data-bs-toggle="tooltip">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                </a>
                <a href="{{ route('admin.anggota.show', $m->id) }}" class="btn btn-sm btn-outline-info rounded-circle" title="Detail Profil" data-bs-toggle="tooltip">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="{{ route('admin.anggota.edit', $m->id) }}" class="btn btn-sm btn-outline-primary rounded-circle" title="Edit Data" data-bs-toggle="tooltip">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Data" data-bs-toggle="tooltip" @click="confirmDelete('{{ route('admin.anggota.destroy', $m->id) }}', '{{ addslashes($m->full_name) }}')">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted card border-0 bg-light rounded-4">
            Tidak ada data anggota yang sesuai dengan kriteria filter.
        </div>
    @endforelse
</div>

<div class="mt-3">
    {{ $members->links() }}
</div>
