<!-- Desktop Table View (md and above) -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Tingkat</th>
                <th>Kode Lokasi</th>
                <th>Parent Code</th>
                <th>Nama Lokasi</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customLocations as $idx => $cl)
                <tr>
                    <td>{{ $customLocations->firstItem() + $idx }}</td>
                    <td>
                        <span class="badge bg-dark uppercase px-2.5 py-1">{{ strtoupper($cl->level) }}</span>
                    </td>
                    <td><code>{{ $cl->code }}</code></td>
                    <td><small class="text-muted">{{ $cl->parent_code ?? '-' }}</small></td>
                    <td><strong class="text-dark">{{ $cl->name }}</strong></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Edit Lokasi" @click="openEditModal({{ $cl->id }}, '{{ addslashes($cl->code) }}', '{{ addslashes($cl->name) }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;" title="Hapus Lokasi" @click="confirmDeleteLocation('{{ route('admin.locations.destroy', $cl->id) }}', '{{ addslashes($cl->name) }}', '{{ addslashes($cl->code) }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-geo-alt display-5 d-block mb-2 text-secondary opacity-50"></i>
                        Belum ada lokasi tambahan kustom. Semua lokasi menggunakan data standar BPS / Kemendagri.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View (sm and below) -->
<div class="d-block d-md-none space-y-3">
    @forelse($customLocations as $cl)
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <span class="badge bg-dark uppercase px-2.5 py-1">{{ strtoupper($cl->level) }}</span>
                <code class="px-2 py-0.5 rounded bg-light text-success border border-success-subtle small font-monospace">{{ $cl->code }}</code>
            </div>

            <div class="mb-3">
                <h6 class="fw-bold text-dark mb-1">{{ $cl->name }}</h6>
                <div class="text-muted small">
                    Parent Code: <strong>{{ $cl->parent_code ?? '-' }}</strong>
                </div>
            </div>

            <!-- Card View Actions at Bottom -->
            <div class="d-flex justify-content-end align-items-center gap-2 pt-2 border-top">
                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-semibold" @click="openEditModal({{ $cl->id }}, '{{ addslashes($cl->code) }}', '{{ addslashes($cl->name) }}')">
                    <i class="bi bi-pencil me-1"></i> Edit
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold" @click="confirmDeleteLocation('{{ route('admin.locations.destroy', $cl->id) }}', '{{ addslashes($cl->name) }}', '{{ addslashes($cl->code) }}')">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted bg-light rounded-4">
            <i class="bi bi-geo-alt display-5 d-block mb-2 text-secondary opacity-50"></i>
            Belum ada lokasi tambahan kustom.
        </div>
    @endforelse
</div>

<!-- Pagination Footer -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-2 border-top">
    <div class="text-muted small text-center text-md-start">
        Menampilkan <strong>{{ $customLocations->firstItem() ?? 0 }}</strong> sampai <strong>{{ $customLocations->lastItem() ?? 0 }}</strong> dari total <strong>{{ $customLocations->total() }}</strong> lokasi tambahan
    </div>
    <div>
        {{ $customLocations->links() }}
    </div>
</div>
