@extends('layouts.app')

@section('title', 'Atur Ulang Password')

@section('content')
<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="height: 60px;" class="mb-3">
                        <h4 class="fw-bold text-dark">Atur Ulang Password</h4>
                        <p class="text-secondary small">Silakan masukkan password baru yang kuat untuk akun SIKAP ISNU Anda.</p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 small mb-4" role="alert">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-4 p-3 mb-4 text-start" role="alert">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
                                <strong class="small text-danger">Pemberitahuan Sistem</strong>
                            </div>
                            <p class="small mb-0 text-dark">
                                {{ $errors->first() }}
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('password.update') }}" method="POST" x-data="{ loading: false, showPassword: false }" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">Alamat Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="nama@email.com" value="{{ old('email', $email) }}" required {{ !empty(old('email', $email)) ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input :type="showPassword ? 'text' : 'password'" name="password" class="form-control bg-light border-start-0 border-end-0" placeholder="Minimal 8 karakter" required autofocus>
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted" @click="showPassword = !showPassword" tabindex="-1">
                                    <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted">Minimal 8 karakter.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">Ulangi Password Baru</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                                <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" class="form-control bg-light border-start-0" placeholder="Ulangi password baru" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-isnu-primary w-100 py-2.5 fw-bold rounded-pill mb-3" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-check-circle me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Menyimpan Password...' : 'Simpan Password Baru'"></span>
                        </button>

                        <div class="text-center small">
                            <a href="{{ route('login') }}" class="text-decoration-none text-muted">
                                <i class="bi bi-arrow-left me-1"></i> Batal dan Kembali ke Halaman Masuk
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
