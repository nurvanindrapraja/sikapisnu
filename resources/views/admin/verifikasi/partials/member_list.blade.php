<!-- Desktop Table View (md and above) -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Pendaftar</th>
                <th>NIK</th>
                <th>Pekerjaan</th>
                <th>Kecamatan</th>
                <th>Status</th>
                <th class="text-end">Aksi Review</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $index => $m)
                <tr>
                    <td>{{ $members->firstItem() + $index }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover" style="width: 40px; height: 40px;">
                            <div>
                                <strong class="d-block text-dark">{{ $m->full_name }}</strong>
                                <small class="text-muted">{{ $m->email }} | {{ $m->phone }}</small>
                            </div>
                        </div>
                    </td>
                    <td><code>{{ $m->nik }}</code></td>
                    <td>{{ $m->occupation }}</td>
                    <td>{{ $m->kecamatan }}</td>
                    <td>
                        <span class="badge 
                            @if($m->membership_status === 'menunggu_verifikasi') bg-secondary text-white 
                            @elseif($m->membership_status === 'terverifikasi') bg-success text-white 
                            @elseif($m->membership_status === 'pengurus') bg-warning text-dark 
                            @elseif($m->membership_status === 'perbaikan') bg-info text-white
                            @elseif($m->membership_status === 'ditolak') bg-danger text-white
                            @else bg-secondary text-white @endif px-3 py-1">
                            {{ strtoupper(str_replace('_', ' ', $m->membership_status)) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.verifikasi.show', $m->id) }}" class="btn btn-sm btn-outline-success rounded-pill px-3">
                            <i class="bi bi-eye-fill me-1"></i> Detail & Verifikasi
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada data pendaftar dalam kategori ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View (sm and below) -->
<div class="d-block d-md-none space-y-3">
    @forelse($members as $m)
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white position-relative">
            <!-- Badge Status di Pojok Kanan Atas -->
            <span class="badge position-absolute top-0 end-0 m-3 
                @if($m->membership_status === 'menunggu_verifikasi') bg-secondary text-white 
                @elseif($m->membership_status === 'terverifikasi') bg-success text-white 
                @elseif($m->membership_status === 'pengurus') bg-warning text-dark 
                @elseif($m->membership_status === 'perbaikan') bg-info text-white
                @elseif($m->membership_status === 'ditolak') bg-danger text-white
                @else bg-secondary text-white @endif px-2.5 py-1.5 small text-wrap" style="max-width: 130px; z-index: 1;">
                {{ strtoupper(str_replace('_', ' ', $m->membership_status)) }}
            </span>

            <div class="d-flex align-items-start gap-3 mb-3" style="padding-right: 120px;">
                <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover border border-2 border-success flex-shrink-0" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px; border-radius: 50%;">
                <div class="flex-grow-1 min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                    <h6 class="fw-bold text-dark mb-1 text-wrap text-break lh-sm">{{ $m->full_name }}</h6>
                    <small class="text-muted d-block text-wrap text-break">{{ $m->email }}</small>
                </div>
            </div>

            <div class="bg-light p-2.5 rounded-3 mb-3 small">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">NIK:</span>
                    <code class="text-dark fw-bold">{{ $m->nik }}</code>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Pekerjaan:</span>
                    <span class="fw-semibold text-dark text-end text-break ms-2">{{ $m->occupation }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">No. HP / WA:</span>
                    <span class="text-dark ms-2">{{ $m->phone }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Kecamatan:</span>
                    <span class="fw-semibold text-success ms-2">{{ $m->kecamatan }}</span>
                </div>
            </div>

            <a href="{{ route('admin.verifikasi.show', $m->id) }}" class="btn btn-sm btn-success w-100 rounded-pill fw-bold">
                <i class="bi bi-search me-1"></i> Detail & Verifikasi
            </a>
        </div>
    @empty
        <div class="text-center py-4 text-muted bg-light rounded-4">
            <i class="bi bi-folder-x display-5 d-block mb-2 text-secondary"></i>
            Tidak ada data pendaftar dalam kategori ini.
        </div>
    @endforelse
</div>

<!-- Pagination Footer -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-2 border-top">
    <div class="text-muted small text-center text-md-start">
        Menampilkan <strong>{{ $members->firstItem() ?? 0 }}</strong> sampai <strong>{{ $members->lastItem() ?? 0 }}</strong> dari total <strong>{{ $members->total() }}</strong> pendaftar
    </div>
    <div>
        {{ $members->links() }}
    </div>
</div>
