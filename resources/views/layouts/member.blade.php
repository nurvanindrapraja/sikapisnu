<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Anggota') - SIKAP ISNU Kota Surabaya</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Google Fonts & Bootstrap 5 & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --isnu-green: #006837;
            --isnu-green-dark: #004d28;
            --isnu-gold: #d4af37;
        }

        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            min-height: 100vh;
        }

        /* Sidebar Styling (Matching Pengurus / Admin) */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #06402b;
            color: #e2e8f0;
            z-index: 1050;
            overflow-y: auto;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-brand {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-user-card {
            padding: 1rem 1.25rem;
            background: rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-menu {
            padding: 1rem 0 2rem;
            list-style: none;
            margin: 0;
        }

        .sidebar-header {
            padding: 0.75rem 1.25rem 0.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-link i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.08);
            border-left-color: var(--isnu-gold);
        }

        /* Main Content Layout */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .top-navbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Desktop Layout */
        @media (min-width: 992px) {
            .sidebar {
                transform: none !important;
            }
            .sidebar-backdrop {
                display: none !important;
            }
        }

        /* Mobile Responsive Layout */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
                padding: 1rem;
            }

            .top-navbar {
                margin: -1rem -1rem 1rem -1rem !important;
                padding: 0.75rem 1rem !important;
            }
        }

        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            z-index: 1040;
        }

        /* Common Custom Cards & Buttons */
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

        .btn-isnu-primary {
            background: linear-gradient(135deg, var(--isnu-green), var(--isnu-green-dark));
            color: #ffffff !important;
            font-weight: 600;
            border: none;
            box-shadow: 0 4px 14px rgba(0, 104, 55, 0.25);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-isnu-primary:hover, .btn-isnu-primary:focus {
            background: linear-gradient(135deg, var(--isnu-green-dark), #00361c);
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 104, 55, 0.35);
        }

        .btn-isnu-outline {
            border: 2px solid var(--isnu-green);
            color: var(--isnu-green);
            font-weight: 600;
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-isnu-outline:hover {
            background-color: var(--isnu-green);
            color: #ffffff !important;
        }

        /* Custom Green Pagination */
        .pagination .page-item.active .page-link {
            background-color: var(--isnu-green) !important;
            border-color: var(--isnu-green) !important;
            color: #ffffff !important;
        }

        .pagination .page-link {
            color: var(--isnu-green);
            border-radius: 0.375rem;
            margin: 0 2px;
            font-weight: 600;
        }

        .pagination .page-link:hover {
            color: var(--isnu-green-dark);
            background-color: #eaf3ed;
            border-color: #c2decb;
        }
    </style>
    @yield('styles')
</head>
<body x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop d-lg-none" 
         x-show="sidebarOpen" 
         x-transition.opacity 
         @click="sidebarOpen = false" 
         style="display: none;"></div>

    <!-- Left Sidebar Menu (Sesuai Tampilan Akun Pengurus) -->
    <aside class="sidebar" :class="{ 'show': sidebarOpen }">
        <!-- Sidebar Brand Header -->
        <div class="sidebar-brand d-flex align-items-center justify-content-between">
            <a href="{{ route('member.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none text-white">
                <div class="bg-white p-1 rounded-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="max-height: 34px; max-width: 34px; object-fit: contain;">
                </div>
                <div class="lh-sm">
                    <span class="fw-bold d-block text-white" style="font-size: 1.05rem; letter-spacing: -0.4px;">SIKAP ISNU</span>
                    <span class="text-white-50 fw-semibold d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;">PORTAL ANGGOTA</span>
                </div>
            </a>
            <button type="button" class="btn btn-sm btn-link text-white-50 d-lg-none p-0" @click="sidebarOpen = false">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>

        <!-- Sidebar User Profile Info Widget -->
        @php
            $member = auth()->user()->member;
            $photoUrl = $member?->photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=006837&color=ffffff&size=128';
            $statusLabel = match($member?->membership_status) {
                'terverifikasi' => 'Terverifikasi Sah',
                'pengurus' => 'Pengurus ISNU',
                'calon' => 'Calon Anggota',
                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                'perbaikan' => 'Perlu Perbaikan',
                'ditolak' => 'Ditolak',
                default => 'Anggota ISNU',
            };
            $statusBadgeClass = match($member?->membership_status) {
                'terverifikasi' => 'bg-success',
                'pengurus' => 'bg-warning text-dark',
                'calon' => 'bg-info text-dark',
                'menunggu_verifikasi' => 'bg-secondary',
                'perbaikan' => 'bg-danger',
                'ditolak' => 'bg-danger',
                default => 'bg-light text-dark',
            };
        @endphp
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center gap-2.5">
                <img src="{{ $photoUrl }}" alt="{{ auth()->user()->name }}" class="rounded-circle border border-2 border-success-subtle shadow-sm object-fit-cover flex-shrink-0" style="width: 44px; height: 44px;">
                <div class="overflow-hidden">
                    <strong class="d-block text-white text-truncate" style="font-size: 0.88rem;" title="{{ auth()->user()->name }}">
                        {{ auth()->user()->name }}
                    </strong>
                    <span class="badge {{ $statusBadgeClass }} rounded-pill px-2 py-0 fw-semibold" style="font-size: 0.68rem;">
                        {{ $statusLabel }}
                    </span>
                    @if($member?->member_number)
                        <small class="text-white-50 d-block font-monospace mt-0.5" style="font-size: 0.65rem;">
                            {{ $member->member_number }}
                        </small>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Menu Items -->
        <ul class="sidebar-menu">
            <li class="sidebar-header">Menu Utama</li>
            <li>
                <a href="{{ route('member.dashboard') }}" class="sidebar-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-person-badge-fill"></i> Profil Saya
                </a>
            </li>
            <li>
                <a href="{{ route('member.profile.edit') }}" class="sidebar-link {{ request()->routeIs('member.profile.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-pencil-square"></i> Ubah & Tambah Profil
                </a>
            </li>
            @if(in_array($member?->membership_status, ['terverifikasi', 'pengurus']))
            <li>
                <a href="{{ route('member.cv.download') }}" class="sidebar-link" @click="sidebarOpen = false">
                    <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Unduh CV (PDF)
                </a>
            </li>
            @endif

            <li class="sidebar-header">Toko & Merchandise</li>
            <li>
                <a href="{{ route('store.index') }}" class="sidebar-link {{ request()->routeIs('store.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-shop text-warning"></i> Toko Merchandise
                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-auto rounded-pill" style="font-size: 0.65rem;">Katalog</span>
                </a>
            </li>
            <li>
                <a href="{{ route('member.orders.index') }}" class="sidebar-link {{ request()->routeIs('member.orders.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-bag-check-fill"></i> Pesanan Saya
                    @php
                        $myOrdersCount = \App\Models\ProductOrder::where('user_id', auth()->id())->count();
                    @endphp
                    @if($myOrdersCount > 0)
                        <span class="badge bg-secondary ms-auto rounded-pill">{{ $myOrdersCount }}</span>
                    @endif
                </a>
            </li>

            <li class="sidebar-header">Jaringan & Direktori</li>
            <li>
                <a href="{{ route('daftar.anggota') }}" class="sidebar-link {{ request()->routeIs('daftar.anggota') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-people-fill"></i> Katalog Anggota
                </a>
            </li>

            <!-- Akses Tambahan untuk Pengurus NU -->
            @if(auth()->user()->canAccessLetterSubmission() || auth()->user()->canManageFinances() || auth()->user()->isAdmin())
            <li class="sidebar-header">Akses Pengurus</li>
            @if(auth()->user()->canAccessLetterSubmission())
            <li>
                <a href="{{ route('admin.letters.index') }}" class="sidebar-link {{ request()->routeIs('admin.letters.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-file-earmark-text-fill"></i> Konsep Surat & E-TTD
                </a>
            </li>
            @endif
            @if(auth()->user()->canManageFinances())
            <li>
                <a href="{{ route('admin.finances.index') }}" class="sidebar-link {{ request()->routeIs('admin.finances.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-cash-coin"></i> Keuangan Kas
                </a>
            </li>
            @endif
            @if(auth()->user()->isAdmin())
            <li>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link text-warning fw-bold" @click="sidebarOpen = false">
                    <i class="bi bi-speedometer2"></i> Panel Pengurus (Admin)
                </a>
            </li>
            @endif
            @endif

            <li class="sidebar-header">Situs & Akun</li>
            <li>
                <a href="{{ route('home') }}" target="_blank" class="sidebar-link text-warning" @click="sidebarOpen = false">
                    <i class="bi bi-box-arrow-up-right"></i> Lihat Situs Publik
                </a>
            </li>
            <li>
                <a href="#" class="sidebar-link text-danger" data-bs-toggle="modal" data-bs-target="#modalConfirmLogout" @click="sidebarOpen = false">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-2">
                <!-- Hamburger Toggle Button Mobile -->
                <button type="button" class="btn btn-light btn-sm rounded-3 d-lg-none p-2 shadow-sm border" @click="sidebarOpen = !sidebarOpen" title="Buka Menu">
                    <i class="bi bi-list fs-5 text-dark lh-1 d-block"></i>
                </button>

                <h5 class="fw-bold m-0 text-success">
                    <i class="bi bi-person-circle me-2 d-none d-md-inline"></i> @yield('header_title', 'Area Keanggotaan ISNU')
                </h5>
            </div>

            <div class="d-none d-lg-flex align-items-center gap-2.5">
                <a href="{{ route('store.index') }}" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-shop me-1"></i> Toko ISNU
                </a>
                <a href="{{ route('member.orders.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-bag-check-fill me-1"></i> Pesanan Saya
                </a>
                <div class="vr mx-1" style="height: 24px;"></div>
                <a href="{{ route('member.profile.edit') }}" class="text-decoration-none text-end" title="Profil Anggota">
                    <span class="fw-semibold d-block text-dark small">{{ auth()->user()->name }}</span>
                    <span class="badge {{ $statusBadgeClass }} px-2 py-0.5" style="font-size: 0.65rem;">
                        {{ $statusLabel }}
                    </span>
                </a>
                <a href="{{ route('member.profile.edit') }}" class="btn btn-light border btn-sm rounded-circle shadow-sm" title="Ubah Profil">
                    <i class="bi bi-person-gear text-success"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#modalConfirmLogout" title="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Alert Notifications -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Page Yield Content -->
        @yield('content')
    </main>

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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @include('partials.attachment_preview_modal')

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

    @yield('scripts')
    @stack('scripts')
</body>
</html>
