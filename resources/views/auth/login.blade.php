@extends('layouts.app')

@section('title', 'Masuk Sistem')

@section('content')
<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="height: 60px;" class="mb-3">
                        <h4 class="fw-bold text-dark">Masuk SIKAP ISNU</h4>
                        <p class="text-secondary small">Masukkan Email / No. HP dan Password Anda</p>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 mb-4 text-start">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                                <strong class="fs-6 text-success">Informasi Pendaftaran</strong>
                            </div>
                            <p class="small mb-0 text-dark">
                                {{ session('success') }}
                            </p>
                        </div>
                    @endif

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
                                <strong class="small text-danger">Pemberitahuan System</strong>
                            </div>
                            <p class="small mb-0 text-dark">
                                {{ $errors->first() }}
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">Email atau Nomor HP</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" name="email" class="form-control bg-light border-start-0" placeholder="email@contoh.com atau 0812..." value="{{ old('email') }}" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4 small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label text-muted" for="remember">Ingat Saya</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-isnu-primary w-100 py-2.5 fw-bold rounded-pill mb-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                        </button>

                        <div class="text-center small text-muted">
                            Belum punya akun anggota? <a href="{{ route('register') }}" class="text-success fw-bold text-decoration-none">Daftar Anggota</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
