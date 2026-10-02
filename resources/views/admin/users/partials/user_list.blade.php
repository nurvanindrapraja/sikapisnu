<!-- Desktop Table View -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th>Nama User / Kontak</th>
                <th>Email</th>
                <th>Role Hak Akses</th>
                <th>Status Akun</th>
                <th>Terdaftar Pada</th>
                <th class="text-center" style="width: 140px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $index => $u)
                <tr>
                    <td>{{ $users->firstItem() + $index }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $u->name }}</div>
                        <small class="text-muted"><i class="bi bi-telephone me-1"></i> {{ $u->phone ?: '-' }}</small>
                    </td>
                    <td>
                        <div class="text-dark font-monospace small">{{ $u->email }}</div>
                        @if($u->member)
                            <small class="text-success"><i class="bi bi-link-45deg"></i> Tertaut Anggota (#{{ $u->member->id }})</small>
                        @endif
                    </td>
                    <td>
                        @php
                            $roleName = match($u->role) {
                                'super_admin' => 'Super Admin',
                                'admin_kota' => 'Admin PC ISNU',
                                'admin_pac' => 'Admin PAC',
                                default => 'Anggota (Member)',
                            };
                            $roleBadge = match($u->role) {
                                'super_admin' => 'bg-danger text-white',
                                'admin_kota' => 'bg-success text-white',
                                'admin_pac' => 'bg-info text-dark',
                                default => 'bg-secondary-subtle text-secondary border',
                            };
                        @endphp
                        <span class="badge {{ $roleBadge }} px-2.5 py-1 rounded-pill fw-semibold">
                            {{ $roleName }}
                        </span>
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-check-circle-fill me-1"></i> Aktif
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                <i class="bi bi-x-circle-fill me-1"></i> Nonaktif
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="small text-dark">{{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}</div>
                        <small class="text-muted fs-xs">{{ $u->created_at ? $u->created_at->format('H:i') . ' WIB' : '' }}</small>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <!-- Toggle Active Button -->
                            <button type="button" 
                                    class="btn btn-sm {{ $u->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                    style="width: 34px; height: 34px;"
                                    @click="toggleUserStatus({{ $u->id }})"
                                    :disabled="loading"
                                    data-bs-toggle="tooltip"
                                    title="{{ $u->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                <i class="bi {{ $u->is_active ? 'bi-dash-circle-fill' : 'bi-check-circle-fill' }}"></i>
                            </button>

                            <!-- Edit Button -->
                            <button type="button" 
                                    class="btn btn-sm btn-outline-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                    style="width: 34px; height: 34px;"
                                    @click="openEditModal({{ json_encode($u) }})"
                                    :disabled="loading"
                                    data-bs-toggle="tooltip"
                                    title="Edit User">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <!-- Delete Button -->
                            @if($u->id !== auth()->id())
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger rounded-circle p-2 d-inline-flex align-items-center justify-content-center"
                                        style="width: 34px; height: 34px;"
                                        @click="openDeleteModal({{ json_encode($u) }})"
                                        :disabled="loading"
                                        data-bs-toggle="tooltip"
                                        title="Hapus User">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-person-x display-6 d-block mb-2 text-secondary"></i>
                            <p class="mb-0 fw-medium">Tidak ada data user yang ditemukan.</p>
                            <small class="text-muted">Coba ubah kata kunci pencarian atau filter Anda.</small>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="d-block d-md-none p-3">
    @forelse($users as $index => $u)
        @php
            $roleName = match($u->role) {
                'super_admin' => 'Super Admin',
                'admin_kota' => 'Admin PC ISNU',
                'admin_pac' => 'Admin PAC',
                default => 'Anggota (Member)',
            };
            $roleBadge = match($u->role) {
                'super_admin' => 'bg-danger text-white',
                'admin_kota' => 'bg-success text-white',
                'admin_pac' => 'bg-info text-dark',
                default => 'bg-secondary-subtle text-secondary border',
            };
        @endphp
        <div class="card border shadow-sm rounded-3 mb-3 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-light text-secondary border me-1">#{{ $users->firstItem() + $index }}</span>
                        <span class="badge {{ $roleBadge }} px-2 py-0.5 rounded-pill">
                            {{ $roleName }}
                        </span>
                    </div>
                    <div>
                        @if($u->is_active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">
                                <i class="bi bi-check-circle-fill me-1"></i> Aktif
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5">
                                <i class="bi bi-x-circle-fill me-1"></i> Nonaktif
                            </span>
                        @endif
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-1">{{ $u->name }}</h6>
                <div class="font-monospace small text-primary mb-2">{{ $u->email }}</div>

                <div class="row g-2 pt-2 border-top fs-xs">
                    <div class="col-6">
                        <span class="text-muted d-block">Telepon / No. HP:</span>
                        <strong class="text-dark">{{ $u->phone ?: '-' }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Terdaftar Pada:</span>
                        <strong class="text-dark">{{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}</strong>
                    </div>
                </div>

                <!-- Action buttons for mobile -->
                <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                    <button type="button" 
                            class="btn btn-sm {{ $u->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-3 py-1 text-xs"
                            @click="toggleUserStatus({{ $u->id }})"
                            :disabled="loading">
                        <i class="bi {{ $u->is_active ? 'bi-dash-circle' : 'bi-check-circle' }} me-1"></i>
                        {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 text-xs"
                            @click="openEditModal({{ json_encode($u) }})"
                            :disabled="loading">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>
                    @if($u->id !== auth()->id())
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 text-xs"
                                @click="openDeleteModal({{ json_encode($u) }})"
                                :disabled="loading">
                            <i class="bi bi-trash me-1"></i> Hapus
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <i class="bi bi-person-x display-6 d-block mb-2 text-secondary"></i>
            <p class="mb-0 fw-medium">Tidak ada data user yang ditemukan.</p>
            <small class="text-muted">Coba ubah kata kunci pencarian atau filter Anda.</small>
        </div>
    @endforelse
</div>

@if($users->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 border-top bg-light gap-2">
        <div class="text-muted small text-center text-md-start">
            Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari total {{ $users->total() }} user
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
@endif
