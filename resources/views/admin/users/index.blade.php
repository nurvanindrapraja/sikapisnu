@extends('layouts.admin')

@section('title', 'Kelola Akun User & Hak Akses - SIKAP ISNU')
@section('header_title', 'Kelola Akun User')

@section('content')
<div x-data="{
    search: '{{ request('search') }}',
    role: '{{ request('role') }}',
    status: '{{ request('status') }}',
    loading: false,
    submitting: false,
    
    // Form data state
    form: {
        id: null,
        name: '',
        email: '',
        phone: '',
        role: 'admin_pac',
        password: '',
        is_active: true
    },

    deleteUserObj: null,

    fetchUsers() {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.search) params.append('search', this.search);
        if (this.role) params.append('role', this.role);
        if (this.status) params.append('status', this.status);

        fetch(`{{ route('admin.users.index') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('userListContainer').innerHTML = html;
            this.loading = false;
        })
        .catch(err => {
            console.error(err);
            this.loading = false;
        });
    },

    openAddModal() {
        this.form = { id: null, name: '', email: '', phone: '', role: 'admin_pac', password: '', is_active: true };
        const modalEl = document.getElementById('modalUserForm');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    },

    openEditModal(user) {
        this.form = {
            id: user.id,
            name: user.name,
            email: user.email,
            phone: user.phone || '',
            role: user.role,
            password: '',
            is_active: Boolean(user.is_active)
        };
        const modalEl = document.getElementById('modalUserForm');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    },

    saveUser() {
        this.submitting = true;
        const isEdit = Boolean(this.form.id);
        const url = isEdit ? `/admin/users/${this.form.id}` : '{{ route('admin.users.store') }}';
        const method = isEdit ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(this.form)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) {
                const msg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Terjadi kesalahan validasi.');
                throw new Error(msg);
            }
            return data;
        })
        .then(data => {
            this.submitting = false;
            const modalEl = document.getElementById('modalUserForm');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { title: 'Berhasil!', message: data.message, type: 'success' } 
            }));

            this.fetchUsers();
        })
        .catch(err => {
            this.submitting = false;
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { title: 'Gagal!', message: err.message, type: 'error' } 
            }));
        });
    },

    toggleUserStatus(userId) {
        this.loading = true;
        fetch(`/admin/users/${userId}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal mengubah status.');
            return data;
        })
        .then(data => {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { title: 'Berhasil!', message: data.message, type: 'success' } 
            }));
            this.fetchUsers();
        })
        .catch(err => {
            this.loading = false;
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { title: 'Gagal!', message: err.message, type: 'error' } 
            }));
        });
    },

    openDeleteModal(user) {
        this.deleteUserObj = user;
        const modalEl = document.getElementById('modalDeleteUser');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    },

    confirmDelete() {
        if (!this.deleteUserObj) return;
        this.submitting = true;

        fetch(`/admin/users/${this.deleteUserObj.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menghapus user.');
            return data;
        })
        .then(data => {
            this.submitting = false;
            const modalEl = document.getElementById('modalDeleteUser');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { title: 'Berhasil!', message: data.message, type: 'success' } 
            }));

            this.deleteUserObj = null;
            this.fetchUsers();
        })
        .catch(err => {
            this.submitting = false;
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { title: 'Gagal!', message: err.message, type: 'error' } 
            }));
        });
    }
}">
    <!-- Header Title & Action Button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0"><i class="bi bi-person-gear text-success me-2"></i> Manajemen Akun User & Hak Akses</h4>
            <p class="text-secondary small m-0 mt-1">Kelola kredensial akun pengguna, penetapan role hak akses sistem, serta status aktifasi akun</p>
        </div>
        <button type="button" 
                class="btn btn-sm btn-isnu-primary px-3 py-2 rounded-pill shadow-sm" 
                @click="openAddModal()"
                data-bs-toggle="modal" 
                data-bs-target="#modalUserForm">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Akun User Baru
        </button>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <!-- Header Filter Bar -->
        <div class="card-header bg-white border-0 p-3 p-md-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm search-box">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" x-model="search" @input.debounce.400ms="fetchUsers()" class="form-control bg-light border-start-0" placeholder="Cari nama / email / no hp...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select x-model="role" @change="fetchUsers()" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua Role Hak Akses --</option>
                        <option value="admin_pac">Admin PAC</option>
                        <option value="admin_kota">Admin PC ISNU</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="member">Member / Anggota</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select x-model="status" @change="fetchUsers()" class="form-select form-select-sm bg-light">
                        <option value="">-- Semua Status Akun --</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 text-end">
                    <button type="button" @click="search=''; role=''; status=''; fetchUsers()" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>

            <!-- AJAX Progress Loading Bar -->
            <div x-show="loading || submitting" class="progress mt-3" style="height: 4px;" x-cloak>
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
            </div>
        </div>

        <!-- Table & List Container -->
        <div class="card-body p-0" id="userListContainer">
            @include('admin.users.partials.user_list')
        </div>
    </div>

    <!-- Popup Window Form Modal (Tambah / Edit User) -->
    <div class="modal fade" id="modalUserForm" tabindex="-1" aria-labelledby="modalUserFormLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-success text-white p-3 px-4">
                    <h5 class="modal-title fw-bold" id="modalUserFormLabel">
                        <i class="bi bi-person-badge me-2"></i>
                        <span x-text="form.id ? 'Edit Data User' : 'Tambah Akun User Baru'"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" :disabled="submitting"></button>
                </div>
                <form @submit.prevent="saveUser()">
                    <div class="modal-body p-4 bg-light">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" x-model="form.name" class="form-control" placeholder="Masukkan nama lengkap" required :disabled="submitting">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" x-model="form.email" class="form-control" placeholder="contoh: user@isnusurabaya.or.id" required :disabled="submitting">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Nomor HP / WhatsApp</label>
                            <input type="text" x-model="form.phone" class="form-control" placeholder="081234567890" :disabled="submitting">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Role Hak Akses <span class="text-danger">*</span></label>
                            <select x-model="form.role" class="form-select" required :disabled="submitting">
                                <option value="admin_pac">Admin PAC</option>
                                <option value="admin_kota">Admin PC ISNU</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="member">Member / Anggota</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">
                                Password 
                                <template x-if="!form.id"><span class="text-danger">*</span></template>
                                <template x-if="form.id"><small class="text-muted fw-normal">(Kosongkan jika tidak ingin mengubah)</small></template>
                            </label>
                            <input type="password" x-model="form.password" class="form-control" placeholder="Minimum 8 karakter" :required="!form.id" :disabled="submitting">
                        </div>

                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="switchUserActive" x-model="form.is_active" :disabled="submitting">
                            <label class="form-check-label fw-semibold text-dark" for="switchUserActive">Status Akun Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top-0 p-3">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal" :disabled="submitting">Batal</button>
                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-bold" :disabled="submitting">
                            <span x-show="submitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-cloak></span>
                            <i x-show="!submitting" class="bi bi-check-circle me-1"></i>
                            <span x-text="submitting ? 'Memproses...' : (form.id ? 'Simpan Perubahan' : 'Buat User Baru')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-danger text-white p-3 px-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Hapus User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" :disabled="submitting"></button>
                </div>
                <div class="modal-body p-4 text-center bg-light">
                    <i class="bi bi-trash3-fill text-danger display-4 d-block mb-2"></i>
                    <p class="mb-1 text-dark fw-medium">Apakah Anda yakin ingin menghapus akun user ini?</p>
                    <strong class="d-block text-danger font-monospace" x-text="deleteUserObj ? deleteUserObj.name + ' (' + deleteUserObj.email + ')' : ''"></strong>
                    <small class="text-muted d-block mt-2 fs-xs">Tindakan ini tidak dapat dibatalkan.</small>
                </div>
                <div class="modal-footer bg-white border-top-0 p-3 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal" :disabled="submitting">Batal</button>
                    <button type="button" @click="confirmDelete()" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold" :disabled="submitting">
                        <span x-show="submitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-cloak></span>
                        <i x-show="!submitting" class="bi bi-trash-fill me-1"></i>
                        <span x-text="submitting ? 'Hapus...' : 'Ya, Hapus'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
