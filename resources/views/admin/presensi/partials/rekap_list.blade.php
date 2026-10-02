<div class="table-responsive">
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
                        @php
                            $status = strtolower($member->membership_status ?? '');
                        @endphp
                        @if(str_contains($status, 'pengurus'))
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-shield-check me-1"></i> Pengurus
                            </span>
                        @elseif(str_contains($status, 'calon'))
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-clock-history me-1"></i> Calon Anggota
                            </span>
                        @else
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-person me-1"></i> {{ ucfirst($member->membership_status ?? 'Anggota') }}
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

@if($members->hasPages())
    <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
        <div class="text-muted small">
            Menampilkan {{ $members->firstItem() }} - {{ $members->lastItem() }} dari total {{ $members->total() }} kader
        </div>
        <div>
            {{ $members->links() }}
        </div>
    </div>
@endif
