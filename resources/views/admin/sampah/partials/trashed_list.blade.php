<!-- Desktop Table View (md and above) -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nomor Anggota</th>
                <th>Nama & Profil</th>
                <th>NIK</th>
                <th>Waktu Dihapus</th>
                <th class="text-end">Aksi Pemulihan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($trashedMembers as $index => $m)
                <tr>
                    <td>{{ $trashedMembers->firstItem() + $index }}</td>
                    <td><strong class="font-monospace text-muted">{{ $m->member_number ?? '-' }}</strong></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover grayscale" style="width: 38px; height: 38px; filter: grayscale(100%);">
                            <div>
                                <strong class="d-block text-dark">{{ $m->full_name }}</strong>
                                <small class="text-muted">{{ $m->email }} | {{ $m->phone }}</small>
                            </div>
                        </div>
                    </td>
                    <td><code>{{ $m->nik ?? '-' }}</code></td>
                    <td>
                        <small class="text-danger fw-semibold">
                            <i class="bi bi-clock-history me-1"></i> {{ $m->deleted_at->translatedFormat('d M Y H:i') }}
                        </small>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <!-- Tombol Restore / Pulihkan -->
                            <button type="button" class="btn btn-sm btn-success rounded-circle p-2" style="width: 34px; height: 34px;" title="Pulihkan Data Anggota" @click="confirmRestore('{{ route('admin.sampah.restore', $m->id) }}', '{{ addslashes($m->full_name) }}')">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>

                            <!-- Tombol Hapus Permanen -->
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2" style="width: 34px; height: 34px;" title="Hapus Permanen" @click="confirmForceDelete('{{ route('admin.sampah.force_delete', $m->id) }}', '{{ addslashes($m->full_name) }}')">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-trash text-secondary display-4 d-block mb-2 opacity-50"></i>
                        Data sampah kosong. Tidak ada data anggota yang dihapus.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View (sm and below) -->
<div class="d-block d-md-none space-y-3">
    @forelse($trashedMembers as $m)
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
            <div class="d-flex align-items-center gap-3 mb-2">
                <img src="{{ $m->photo_url }}" alt="" class="rounded-circle object-fit-cover grayscale" style="width: 44px; height: 44px; filter: grayscale(100%);">
                <div class="flex-grow-1 min-w-0">
                    <h6 class="fw-bold text-dark mb-0 text-truncate">{{ $m->full_name }}</h6>
                    <small class="text-muted d-block text-truncate">{{ $m->email }}</small>
                </div>
            </div>

            <div class="bg-light p-2.5 rounded-3 mb-3 small">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">No. Anggota:</span>
                    <strong class="font-monospace text-dark">{{ $m->member_number ?? '-' }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">NIK:</span>
                    <code>{{ $m->nik ?? '-' }}</code>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Waktu Dihapus:</span>
                    <small class="text-danger fw-semibold">{{ $m->deleted_at->translatedFormat('d M Y H:i') }}</small>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-success w-100 rounded-pill fw-bold" @click="confirmRestore('{{ route('admin.sampah.restore', $m->id) }}', '{{ addslashes($m->full_name) }}')">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger w-100 rounded-pill fw-semibold" @click="confirmForceDelete('{{ route('admin.sampah.force_delete', $m->id) }}', '{{ addslashes($m->full_name) }}')">
                    <i class="bi bi-x-circle me-1"></i> Hapus Permanen
                </button>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted bg-light rounded-4">
            <i class="bi bi-trash display-5 d-block mb-2 text-secondary opacity-50"></i>
            Data sampah kosong.
        </div>
    @endforelse
</div>

<!-- Pagination Footer -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-2 border-top">
    <div class="text-muted small text-center text-md-start">
        Menampilkan <strong>{{ $trashedMembers->firstItem() ?? 0 }}</strong> sampai <strong>{{ $trashedMembers->lastItem() ?? 0 }}</strong> dari total <strong>{{ $trashedMembers->total() }}</strong> data terhapus
    </div>
    <div>
        {{ $trashedMembers->links() }}
    </div>
</div>
