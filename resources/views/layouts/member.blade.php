<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Member') - SIKAP ISNU Surabaya</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --isnu-green: #006837;
            --isnu-green-dark: #004d28;
            --isnu-gold: #d4af37;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
        }

        .member-header {
            background: linear-gradient(135deg, #004d28 0%, #006837 100%);
            color: white;
            padding: 2.5rem 0 4rem;
            position: relative;
        }

        .member-container {
            margin-top: -2.5rem;
            margin-bottom: 4rem;
        }

        .card-custom {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            background: white;
        }

        .btn-isnu {
            background: var(--isnu-green);
            color: white;
            font-weight: 600;
        }

        .btn-isnu:hover {
            background: var(--isnu-green-dark);
            color: white;
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Top Header -->
    <header class="member-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none">
                    <div class="bg-white p-1 rounded-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="max-height: 38px; max-width: 38px; object-fit: contain;">
                    </div>
                    <div class="lh-sm">
                        <span class="fw-bold d-block text-white mb-0" style="font-size: 1.1rem; letter-spacing: -0.5px; line-height: 1;">SIKAP ISNU</span>
                        <span class="text-white-50 fw-semibold d-block" style="font-size: 0.68rem; margin-top: 1px;">PC ISNU KOTA SURABAYA</span>
                    </div>
                </a>
            <!-- Desktop Header Right Items -->
            <div class="d-none d-md-flex align-items-center gap-3">
                @if(in_array(auth()->user()->member?->membership_status, ['terverifikasi', 'pengurus']))
                <a href="{{ route('member.cv.download') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download CV (PDF)
                </a>
                @endif
                <a href="{{ route('daftar.anggota') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-people-fill me-1"></i> Katalog Anggota
                </a>
                <a href="{{ route('member.profile.edit') }}" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold text-success shadow-sm">
                    <i class="bi bi-person-circle me-1"></i> Profil & Akun
                </a>
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalConfirmLogout">
                    <i class="bi bi-box-arrow-right me-1"></i> Keluar
                </button>
            </div>

            <!-- Mobile Hamburger Button & Submenu Dropdown -->
            <div class="d-md-none position-relative" x-data="{ open: false }">
                <button type="button" class="btn btn-outline-light btn-sm rounded-3 p-2 d-flex align-items-center justify-content-center" @click="open = !open" style="width: 40px; height: 40px;" aria-label="Toggle Navigation">
                    <i class="bi" :class="open ? 'bi-x-lg fs-5' : 'bi-list fs-4'"></i>
                </button>

                <!-- Mobile Submenu Dropdown Panel -->
                <div x-show="open" 
                     @click.outside="open = false" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="position-absolute end-0 mt-2 bg-white rounded-4 shadow-lg p-2 border" 
                     style="width: 230px; z-index: 1050; display: none;">
                    <div class="px-3 py-2 border-bottom mb-1">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Masuk sebagai</small>
                        <strong class="text-dark d-block text-truncate" style="font-size: 0.88rem;">{{ auth()->user()->name }}</strong>
                    </div>
                    <a href="{{ route('member.profile.edit') }}" class="dropdown-item rounded-3 py-2 px-3 fw-semibold text-dark d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-person-circle text-success fs-5"></i> Profil & Ubah Password
                    </a>
                    @if(in_array(auth()->user()->member?->membership_status, ['terverifikasi', 'pengurus']))
                    <a href="{{ route('member.cv.download') }}" class="dropdown-item rounded-3 py-2 px-3 fw-semibold text-dark d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i> Download CV (PDF)
                    </a>
                    @endif
                    <a href="{{ route('daftar.anggota') }}" class="dropdown-item rounded-3 py-2 px-3 fw-semibold text-dark d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-people-fill text-success fs-5"></i> Katalog Anggota
                    </a>
                    <button type="button" class="dropdown-item rounded-3 py-2 px-3 fw-semibold text-danger d-flex align-items-center gap-2 w-100 text-start" data-bs-toggle="modal" data-bs-target="#modalConfirmLogout" @click="open = false">
                        <i class="bi bi-box-arrow-right fs-5"></i> Keluar
                    </button>
                </div>
            </div>
            </div>
        </div>
    </header>

    <div class="container member-container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Modal Konfirmasi Logout Member -->
    <div class="modal fade" id="modalConfirmLogout" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-box-arrow-right me-2"></i> Konfirmasi Keluar
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="bi bi-door-open-fill display-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Apakah Anda yakin ingin keluar?</h5>
                    <p class="text-muted mb-0">
                        Sesi Anda akan diakhiri dan Anda perlu masuk (login) kembali untuk mengakses akun SIKAP ISNU.
                    </p>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <button type="submit" class="btn btn-danger fw-bold rounded-pill px-4" :disabled="loading">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                            <i class="bi bi-box-arrow-right me-1" x-show="!loading"></i>
                            <span x-text="loading ? 'Keluar...' : 'Ya, Keluar'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal Popup Download Kartu Anggota -->
    <div class="modal fade" id="cardDownloadModal" tabindex="-1" aria-labelledby="cardDownloadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-success text-white p-3 border-0">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="cardDownloadModalLabel">
                        <i class="bi bi-person-vcard-fill fs-5"></i> Download Kartu Anggota Digital (PDF)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3 p-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-file-earmark-pdf display-6 text-danger"></i>
                    </div>
                    
                    <h6 class="fw-bold text-dark mb-1" id="cardMemberName">Nama Anggota</h6>
                    <small class="font-monospace text-muted d-block mb-3" id="cardMemberNumber">ISNU-SBY-26-XXXXXX</small>

                    <p class="text-secondary small mb-4">
                        Klik tombol di bawah ini untuk mengunduh Kartu Anggota Digital dalam format PDF resmi:
                    </p>

                    <div class="d-grid gap-2 col-11 mx-auto">
                        <!-- Tombol PDF -->
                        <a id="btnDownloadPdf" href="#" target="_blank" class="btn btn-success btn-lg rounded-pill fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2 shadow-sm" onclick="const mEl = document.getElementById('cardDownloadModal'); if (mEl) { const m = bootstrap.Modal.getInstance(mEl); if (m) m.hide(); }">
                            <i class="bi bi-file-earmark-pdf-fill fs-5 text-warning"></i>
                            <span>Download Dokumen Kartu (.PDF)</span>
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2.5 border-0 justify-content-center">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openCardDownloadModal(baseUrl, name, number) {
            document.getElementById('cardMemberName').innerText = name || 'Kartu Anggota Digital';
            document.getElementById('cardMemberNumber').innerText = number ? number : 'SIKAP ISNU Kota Surabaya';
            
            document.getElementById('btnDownloadPdf').setAttribute('href', baseUrl);
            
            const modalEl = document.getElementById('cardDownloadModal');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @yield('scripts')
</body>
</html>
