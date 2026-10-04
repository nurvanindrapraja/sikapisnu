<!-- Desktop Table (Visible on MD and larger screens) -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th>Nama Kader / NIK</th>
                <th>Status Kategori</th>
                <th>PAC / Wilayah</th>
                <th>Jabatan Active</th>
                <th class="text-center">Total Kehadiran</th>
                <th>Kehadiran Terakhir</th>
                <th class="text-center" style="width: 100px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $index => $member)
                @php
                    $lastPresence = $member->presences->sortByDesc('created_at')->first();
                    $status = strtolower($member->membership_status ?? '');
                @endphp
                <tr>
                    <td>{{ $members->firstItem() + $index }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $member->full_name ?? $member->name }}</div>
                        <small class="text-muted">
                            @if($member->nik)
                                NIK: {{ $member->nik }}
                            @elseif($member->member_number)
                                No: {{ $member->member_number }}
                            @else
                                ID: #{{ $member->id }}
                            @endif
                        </small>
                    </td>
                    <td>
                        @if(str_contains($status, 'pengurus'))
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-shield-check me-1"></i> Pengurus
                            </span>
                        @elseif(str_contains($status, 'calon') || str_contains($status, 'menunggu'))
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-clock-history me-1"></i> Calon Anggota
                            </span>
                        @else
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-person me-1"></i> Anggota
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="fw-medium text-dark">{{ $member->pac->name ?? $member->activePosition->pac->name ?? '-' }}</div>
                        @if($member->mwc)
                            <small class="text-muted fs-xs">MWC: {{ $member->mwc->name }}</small>
                        @endif
                    </td>
                    <td>
                        @if($member->activePosition)
                            <span class="badge bg-info-subtle text-info border border-info-subtle fs-xs">
                                {{ $member->activePosition->position_name ?? ($member->activePosition->section->name ?? 'Pengurus') }}
                            </span>
                        @else
                            <span class="text-muted fs-xs">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-teal-subtle text-teal fs-6 fw-bold px-3 py-1 rounded-pill" style="background-color: #e6f4ea; color: #137333;">
                            <i class="bi bi-calendar-check me-1"></i> {{ $member->presences_count }} Kegiatan
                        </span>
                    </td>
                    <td>
                        @if($lastPresence && $lastPresence->event)
                            <div class="fw-medium text-dark fs-xs">{{ Str::limit($lastPresence->event->title ?? $lastPresence->event->name, 25) }}</div>
                            <small class="text-muted fs-xs"><i class="bi bi-clock me-1"></i> {{ $lastPresence->created_at->format('d M Y') }}</small>
                        @else
                            <span class="text-muted fs-xs">Belum ada</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button type="button" 
                                class="btn btn-sm btn-outline-success shadow-sm rounded-circle p-2 d-inline-flex align-items-center justify-content-center" 
                                style="width: 36px; height: 36px;"
                                @click="openDetail({{ $member->id }})"
                                data-bs-toggle="tooltip"
                                title="Lihat Detail Kehadiran">
                            <i class="bi bi-eye-fill fs-6"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i>
                            <p class="mb-0 fw-medium">Tidak ada data kehadiran kader yang ditemukan.</p>
                            <small class="text-muted">Coba ubah kata kunci pencarian atau filter Anda.</small>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View (Visible on Mobile/Small screens) -->
<div class="d-block d-md-none p-3">
    @forelse($members as $index => $member)
        @php
            $lastPresence = $member->presences->sortByDesc('created_at')->first();
            $status = strtolower($member->membership_status ?? '');
        @endphp
        <div class="card border shadow-sm rounded-3 mb-3 bg-white position-relative">
            <div class="card-body p-3">
                <!-- Status Badge di Pojok Kanan Atas -->
                <div class="position-absolute top-0 end-0 m-3" style="z-index: 1;">
                    @if(str_contains($status, 'pengurus'))
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                            <i class="bi bi-shield-check me-1"></i> Pengurus
                        </span>
                    @elseif(str_contains($status, 'calon') || str_contains($status, 'menunggu'))
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                            <i class="bi bi-clock-history me-1"></i> Calon
                        </span>
                    @else
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                            <i class="bi bi-person me-1"></i> Anggota
                        </span>
                    @endif
                </div>

                <div style="padding-right: 110px;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-light text-secondary border">#{{ $members->firstItem() + $index }}</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1 text-wrap text-break lh-sm">{{ $member->full_name ?? $member->name }}</h6>
                    <div class="text-muted small mb-2 text-break">
                        @if($member->nik)
                            NIK: {{ $member->nik }}
                        @elseif($member->member_number)
                            No: {{ $member->member_number }}
                        @else
                            ID: #{{ $member->id }}
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-2">
                    <button type="button" 
                            class="btn btn-sm btn-outline-success rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 shadow-sm" 
                            @click="openDetail({{ $member->id }})">
                        <i class="bi bi-eye-fill"></i> Detail Kehadiran
                    </button>
                </div>

                <div class="row g-2 pt-2 border-top fs-xs">
                    <div class="col-6">
                        <span class="text-muted d-block">PAC / Wilayah:</span>
                        <strong class="text-dark d-block text-truncate">{{ $member->pac->name ?? $member->activePosition->pac->name ?? '-' }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Total Kehadiran:</span>
                        <span class="badge bg-success text-white px-2 py-1 rounded-pill">
                            <i class="bi bi-calendar-check me-1"></i> {{ $member->presences_count }} Kegiatan
                        </span>
                    </div>
                    <div class="col-12 mt-2">
                        <span class="text-muted d-block">Kehadiran Terakhir:</span>
                        @if($lastPresence && $lastPresence->event)
                            <div class="fw-medium text-dark">{{ Str::limit($lastPresence->event->title ?? $lastPresence->event->name, 40) }}</div>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i> {{ $lastPresence->created_at->format('d M Y') }}</small>
                        @else
                            <span class="text-muted">Belum ada</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <i class="bi bi-clipboard-x display-6 d-block mb-2 text-secondary"></i>
            <p class="mb-0 fw-medium">Tidak ada data kehadiran kader yang ditemukan.</p>
            <small class="text-muted">Coba ubah kata kunci pencarian atau filter Anda.</small>
        </div>
    @endforelse
</div>

@if($members->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 border-top bg-light gap-2">
        <div class="text-muted small text-center text-md-start">
            Menampilkan {{ $members->firstItem() }} - {{ $members->lastItem() }} dari total {{ $members->total() }} kader
        </div>
        <div>
            {{ $members->links() }}
        </div>
    </div>
@endif
