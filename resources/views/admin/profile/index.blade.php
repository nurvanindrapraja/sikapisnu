@extends('layouts.admin')

@section('title', 'Profil & Pengaturan Akun')
@section('header_title', 'Profil & Pengaturan Akun Admin')

@section('content')
<div class="container-fluid py-2">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Mohon perbaiki kesalahan berikut:</strong>
            <ul class="mb-0 mt-1 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Sidebar Summary Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4">
                <div class="mb-3 position-relative d-inline-block mx-auto">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 90px; height: 90px; font-size: 2.5rem;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">{{ $user->name }}</h5>
                <p class="text-muted small mb-3">{{ $user->email }}</p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 fs-6 rounded-pill">
                        <i class="bi bi-shield-check me-1"></i> {{ strtoupper(str_replace('_', ' ', $user->role)) }}
                    </span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1.5 fs-6 rounded-pill">
                        <i class="bi bi-circle-fill text-success me-1"></i> Aktif
                    </span>
                </div>

                <hr class="my-3 opacity-25">

                <div class="text-start small text-secondary space-y-2">
                    <div class="d-flex justify-content-between py-1">
                        <span>No. WhatsApp:</span>
                        <strong class="text-dark">{{ $user->phone ?? '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span>Terdaftar Sejak:</span>
                        <strong class="text-dark">{{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms Column -->
        <div class="col-lg-8">
            <!-- Form 1: Edit Informasi Profil -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-3 mb-3">
                    <i class="bi bi-person-lines-fill text-success me-2"></i> Informasi Akun Administrator
                </h6>

                <form action="{{ route('admin.profile.update') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Lengkap Administrator <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success rounded-pill fw-bold px-4 py-2 shadow-sm" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-save me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Menyimpan...' : 'Simpan Informasi Akun'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Form 2: Ubah Password -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h6 class="fw-bold text-dark border-bottom pb-3 mb-3">
                    <i class="bi bi-key-fill text-warning me-2"></i> Ubah Password Akun Administrator
                </h6>
                <p class="small text-muted mb-4">
                    Gunakan password yang kuat (minimal 8 karakter) untuk melindungi hak akses Administrator aplikasi SIKAP ISNU.
                </p>

                <form action="{{ route('admin.profile.change_password') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Password Saat Ini <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" placeholder="Masukkan password saat ini" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password baru" required>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark px-4 py-2 shadow-sm" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-shield-check me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Memperbarui...' : 'Simpan Password Baru'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
