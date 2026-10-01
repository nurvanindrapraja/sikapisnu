<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">No</th>
                <th>Nama & Kegiatan ISNU</th>
                <th>Metode & Lokasi</th>
                <th>Waktu Pelaksanaan</th>
                <th>Masa Aktif Presensi</th>
                <th class="text-center">Kehadiran</th>
                <th class="text-end" style="width: 140px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $index => $evt)
                @php
                    $presenceStatus = $evt->presenceStatus();
                @endphp
                <tr>
                    <td>{{ $events->firstItem() + $index }}</td>
                    <td>
                        <strong class="d-block text-dark fs-6">{{ $evt->title }}</strong>
                        <small class="text-secondary d-block">{{ Str::limit($evt->description, 50) ?: 'Tidak ada deskripsi' }}</small>
                        @if($evt->status === 'completed')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill mt-1">
                                <i class="bi bi-check-circle-fill me-1"></i> Terlaksana / Selesai
                            </span>
                        @elseif($evt->status === 'cancelled')
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 rounded-pill mt-1">
                                Dibatalkan
                            </span>
                        @else
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 rounded-pill mt-1">
                                <i class="bi bi-calendar-event me-1"></i> Direncana / Mendatang
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($evt->method === 'daring')
                            <span class="badge bg-purple-subtle text-purple border border-purple-subtle px-2.5 py-1 rounded-pill d-inline-block mb-1">
                                <i class="bi bi-camera-video-fill me-1"></i> Daring (Online)
                            </span>
                            @if($evt->meeting_link)
                                <a href="{{ $evt->meeting_link }}" target="_blank" class="d-block small text-truncate text-primary" style="max-width: 180px;">
                                    <i class="bi bi-link-45deg me-1"></i> {{ $evt->meeting_link }}
                                </a>
                            @endif
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill d-inline-block mb-1">
                                <i class="bi bi-geo-alt-fill me-1"></i> Luring (Offline)
                            </span>
                            <small class="d-block text-dark font-semibold">{{ $evt->location }}</small>
                        @endif
                    </td>
                    <td>
                        <strong class="d-block text-dark">{{ $evt->event_date->translatedFormat('d M Y') }}</strong>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i> {{ $evt->start_time }} - {{ $evt->end_time }} WIB</small>
                    </td>
                    <td>
                        @if($presenceStatus === 'active')
                            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill d-inline-block mb-1 shadow-sm">
                                <i class="bi bi-broadcast me-1 animate-pulse"></i> Presensi Aktif
                            </span>
                        @elseif($presenceStatus === 'not_started')
                            <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill d-inline-block mb-1">
                                <i class="bi bi-hourglass-split me-1"></i> Belum Dimulai
                            </span>
                        @else
                            <span class="badge bg-secondary text-white px-2.5 py-1 rounded-pill d-inline-block mb-1">
                                <i class="bi bi-clock-history me-1"></i> Presensi Berakhir
                            </span>
                        @endif
                        <small class="d-block text-muted" style="font-size: 0.75rem;">
                            {{ $evt->presence_start_at->format('d/m H:i') }} - {{ $evt->presence_end_at->format('d/m H:i') }}
                        </small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-dark text-white rounded-pill px-3 py-1.5 fs-6">
                            {{ $evt->presences_count }} Orang
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('admin.events.show', $evt->id) }}" class="btn btn-sm btn-success rounded-pill px-2.5 py-1 fw-semibold" title="Detail & Kelola Presensi">
                                <i class="bi bi-eye-fill me-1"></i> Detail
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1 d-flex align-items-center justify-content-center" 
                                    style="width: 30px; height: 30px;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalDeleteEvent{{ $evt->id }}" 
                                    title="Hapus Kegiatan">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>

                        <!-- Modal Hapus Event -->
                        <div class="modal fade text-start" id="modalDeleteEvent{{ $evt->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <div class="modal-body text-center p-4">
                                        <div class="text-danger mb-3">
                                            <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-2">Hapus Kegiatan ISNU?</h6>
                                        <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus kegiatan <strong>"{{ $evt->title }}"</strong> beserta seluruh data presensinya?</p>
                                        <form action="{{ route('admin.events.destroy', $evt->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <div class="d-flex gap-2 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">Ya, Hapus</button>
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
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                        Belum ada kegiatan ISNU yang direncanakan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 d-flex justify-content-between align-items-center px-3">
    <div class="small text-muted">
        Menampilkan {{ $events->firstItem() ?? 0 }} - {{ $events->lastItem() ?? 0 }} dari total {{ $events->total() }} Kegiatan
    </div>
    <div>
        {{ $events->links() }}
    </div>
</div>
