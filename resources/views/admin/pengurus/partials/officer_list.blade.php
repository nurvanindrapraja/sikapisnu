<!-- Desktop Table View (md and above) -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Pengurus</th>
                <th>Jabatan</th>
                <th>Tingkat & Periode</th>
                <th>PAC / Wilayah</th>
                <th>Kartu Digital</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($officers as $index => $o)
                @php $pos = $o->activePosition; @endphp
                <tr>
                    <td>{{ $officers->firstItem() + $index }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $o->photo_url }}" alt="" class="rounded-circle object-fit-cover border border-warning" style="width: 40px; height: 40px;">
                            <div>
                                <strong class="d-block text-dark">{{ $o->full_name }}</strong>
                                <small class="text-muted">{{ $o->member_number }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <strong class="text-success">{{ $pos ? $pos->position_title : 'Pengurus' }}</strong>
                        @if($pos && $pos->section)
                            <small class="d-block text-muted mt-0.5"><i class="bi bi-diagram-2 me-1"></i> {{ $pos->section->name }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-dark me-1">{{ $pos ? $pos->level : 'PC ISNU' }}</span>
                        <small class="text-muted">{{ $pos ? $pos->period : '-' }}</small>
                    </td>
                    <td>
                        @if($pos && ($pos->level === 'PAC ISNU' || $pos->level === 'PAC') && $pos->pac)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                PAC ISNU {{ $pos->pac->name }}
                            </span>
                        @elseif($o->pac)
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                PAC ISNU {{ $o->pac->name }}
                            </span>
                        @else
                            <span class="text-muted">PC ISNU Kota Surabaya</span>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-2.5 py-1 rounded-pill shadow-sm" title="Download Kartu Pengurus (PNG/PDF)" onclick="openCardDownloadModal('{{ route('admin.anggota.card.download', $o->id) }}', '{{ addslashes($o->full_name) }}', '{{ $o->member_number ?? '' }}')">
                            <i class="bi bi-person-vcard-fill me-1"></i> KARTU PENGURUS
                        </button>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.anggota.show', $o->id) }}" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                <i class="bi bi-pencil-square me-1"></i> Detail & Kelola
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Batalkan Pengurus" onclick="openDemoteModal('{{ route('admin.pengurus.demote', $o->id) }}', '{{ addslashes($o->full_name) }}')">
                                <i class="bi bi-x-circle me-1"></i> Batalkan
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data pengurus yang sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View (sm and below) -->
<div class="d-block d-md-none space-y-3">
    @forelse($officers as $o)
        @php $pos = $o->activePosition; @endphp
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white position-relative">
            <!-- Badge Status / Jabatan di Pojok Kanan Atas -->
            <span class="badge position-absolute top-0 end-0 m-3 bg-warning text-dark px-2.5 py-1.5 small fw-bold text-wrap" style="max-width: 130px; z-index: 1;">
                {{ $pos ? $pos->position_title : 'Pengurus' }}
            </span>

            <div class="d-flex align-items-start gap-3 mb-3" style="padding-right: 120px;">
                <img src="{{ $o->photo_url }}" alt="" class="rounded-circle object-fit-cover border border-2 border-warning flex-shrink-0" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px; border-radius: 50%;">
                <div class="flex-grow-1 min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                    <h6 class="fw-bold text-dark mb-1 text-wrap text-break lh-sm">{{ $o->full_name }}</h6>
                    <small class="text-muted d-block text-wrap text-break">{{ $o->member_number }}</small>
                </div>
            </div>
            @if($pos && $pos->section)
                <div class="mb-2 text-secondary small">
                    <i class="bi bi-diagram-2 me-1"></i> Seksi: <strong>{{ $pos->section->name }}</strong>
                </div>
            @endif

            <div class="bg-light p-2.5 rounded-3 mb-3 small">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Tingkat:</span>
                    <span class="badge bg-dark">{{ $pos ? $pos->level : 'PC ISNU' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Periode:</span>
                    <span class="fw-semibold text-dark">{{ $pos ? $pos->period : '-' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Wilayah / PAC:</span>
                    <span class="fw-semibold text-success ms-2 text-end text-break">
                        @if($pos && ($pos->level === 'PAC ISNU' || $pos->level === 'PAC') && $pos->pac)
                            PAC ISNU {{ $pos->pac->name }}
                        @elseif($o->pac)
                            PAC ISNU {{ $o->pac->name }}
                        @else
                            PC ISNU Kota Surabaya
                        @endif
                    </span>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.anggota.show', $o->id) }}" class="btn btn-sm btn-outline-success flex-grow-1 rounded-pill fw-bold">
                    <i class="bi bi-pencil-square me-1"></i> Detail & Kelola
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" onclick="openDemoteModal('{{ route('admin.pengurus.demote', $o->id) }}', '{{ addslashes($o->full_name) }}')">
                    <i class="bi bi-x-circle me-1"></i> Batalkan
                </button>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted bg-light rounded-4">
            <i class="bi bi-award display-5 d-block mb-2 text-secondary"></i>
            Belum ada data pengurus yang sesuai filter.
        </div>
    @endforelse
</div>

<!-- Pagination Footer -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-2 border-top">
    <div class="text-muted small text-center text-md-start">
        Menampilkan <strong>{{ $officers->firstItem() ?? 0 }}</strong> sampai <strong>{{ $officers->lastItem() ?? 0 }}</strong> dari total <strong>{{ $officers->total() }}</strong> pengurus
    </div>
    <div>
        {{ $officers->links() }}
    </div>
</div>
