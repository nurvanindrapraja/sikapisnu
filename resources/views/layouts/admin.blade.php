<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - SIKAP ISNU Kota Surabaya</title>
    
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
            --sidebar-width: 260px;
            --isnu-green: #006837;
            --isnu-green-dark: #004d28;
            --isnu-gold: #d4af37;
        }

        @keyframes toastSlideIn {
            from {
                transform: translateX(120%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .toast-slide-in {
            animation: toastSlideIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
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

        .bg-isnu {
            background: linear-gradient(135deg, var(--isnu-green), var(--isnu-green-dark));
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            min-height: 100vh;
        }

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
            padding: 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu {
            padding: 1rem 0;
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
            font-size: 0.9rem;
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

        .stat-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
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

        .pagination .page-link:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 104, 55, 0.25);
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

    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'show': sidebarOpen }">
        <div class="sidebar-brand d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-white p-1 rounded-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="max-height: 34px; max-width: 34px; object-fit: contain;">
                </div>
                <div class="lh-sm">
                    <span class="fw-bold d-block text-white mb-0" style="font-size: 1rem; letter-spacing: -0.5px; line-height: 1;">SIKAP ISNU</span>
                    <span class="text-white-50 fw-semibold d-block" style="font-size: 0.65rem; margin-top: 1px;">PC ISNU KOTA SURABAYA</span>
                </div>
            </div>
            <!-- Close Button Mobile -->
            <button type="button" class="btn btn-sm text-white-50 d-lg-none p-1 border-0 fs-4 lh-1" @click="sidebarOpen = false">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-header">Utama</li>
            <li>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>

            <li class="sidebar-header">Verifikasi & Keanggotaan</li>
            <li>
                <a href="{{ route('admin.verifikasi.index') }}" class="sidebar-link {{ request()->routeIs('admin.verifikasi.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-person-check-fill"></i> Verifikasi Pendaftar
                    @php $pendingCount = \App\Models\Member::where('membership_status', 'menunggu_verifikasi')->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="badge bg-danger ms-auto rounded-pill">{{ $pendingCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.anggota.index') }}" class="sidebar-link {{ request()->routeIs('admin.anggota.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-people-fill"></i> Database Potensi Kader
                </a>
            </li>
            <li>
                <a href="{{ route('admin.pengurus.index') }}" class="sidebar-link {{ request()->routeIs('admin.pengurus.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-person-workspace"></i> Pengurus ISNU
                </a>
            </li>
            <li>
                <a href="{{ route('admin.sampah.index') }}" class="sidebar-link {{ request()->routeIs('admin.sampah.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-trash3-fill"></i> Data Sampah
                    @php $trashedCount = \App\Models\Member::onlyTrashed()->count(); @endphp
                    @if($trashedCount > 0)
                        <span class="badge bg-secondary ms-auto rounded-pill">{{ $trashedCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('admin.card_orders.index') }}" class="sidebar-link {{ request()->routeIs('admin.card_orders.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-card-heading"></i> Kartu Fisik
                    @php $pendingOrdersCount = \App\Models\CardOrder::where('status', 'pending')->count(); @endphp
                    @if($pendingOrdersCount > 0)
                        <span class="badge bg-warning text-dark ms-auto rounded-pill">{{ $pendingOrdersCount }}</span>
                    @endif
                </a>
            </li>

            <li class="sidebar-header">Master & Organisasi</li>
            <li>
                <a href="{{ route('admin.mwc.index') }}" class="sidebar-link {{ request()->routeIs('admin.mwc.*') || request()->routeIs('admin.pac.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-diagram-3-fill"></i> Master PAC ISNU
                </a>
            </li>
            <li>
                <a href="{{ route('admin.sections.index') }}" class="sidebar-link {{ request()->routeIs('admin.sections.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-diagram-2-fill"></i> Master Seksi
                </a>
            </li>
            <li>
                <a href="{{ route('admin.events.index') }}" class="sidebar-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-calendar-event-fill"></i> Kegiatan & Presensi
                </a>
            </li>
            <li>
                <a href="{{ route('admin.presensi.rekap') }}" class="sidebar-link {{ request()->routeIs('admin.presensi.rekap') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-graph-up-arrow"></i> Rekap Presensi Kader
                </a>
            </li>
            <li>
                <a href="{{ route('admin.locations.index') }}" class="sidebar-link {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-geo-alt-fill"></i> Master Lokasi
                </a>
            </li>

            <li class="sidebar-header">Laporan & Keamanan</li>
            <li>
                <a href="{{ route('admin.laporan.index') }}" class="sidebar-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i> Export Laporan
                </a>
            </li>
            <li>
                <a href="{{ route('admin.audit.index') }}" class="sidebar-link {{ request()->routeIs('admin.audit.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-shield-lock-fill"></i> Audit Log System
                </a>
            </li>
            @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isAdminKota()))
            <li>
                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-person-gear"></i> Kelola Akun User
                </a>
            </li>
            @endif

            <li class="sidebar-header mt-3">Situs & Akun</li>
            <li>
                <a href="{{ route('admin.profile.index') }}" class="sidebar-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <i class="bi bi-person-circle"></i> Profil & Ubah Password
                </a>
            </li>
            <li>
                <a href="{{ route('home') }}" target="_blank" class="sidebar-link text-warning" @click="sidebarOpen = false">
                    <i class="bi bi-box-arrow-up-right"></i> Lihat Situs Publik
                </a>
            </li>
            <li>
                <a href="#" class="sidebar-link text-danger" data-bs-toggle="modal" data-bs-target="#modalConfirmLogoutAdmin" @click="sidebarOpen = false">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-2">
                <!-- Hamburger Toggle Button Mobile -->
                <button type="button" class="btn btn-light btn-sm rounded-3 d-lg-none p-2 shadow-sm border" @click="sidebarOpen = !sidebarOpen" title="Buka Menu">
                    <i class="bi bi-list fs-5 text-dark lh-1 d-block"></i>
                </button>

                <h5 class="fw-bold m-0 text-success">
                    <i class="bi bi-shield-check me-2 d-none d-md-inline"></i> @yield('header_title', 'Dashboard Administrator')
                </h5>
            </div>

            <div class="d-none d-lg-flex align-items-center gap-3">
                <a href="{{ route('admin.profile.index') }}" class="text-decoration-none text-end" title="Pengaturan Profil Admin">
                    <span class="fw-semibold d-block text-dark small">{{ auth()->user()->name }}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.65rem;">
                        {{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}
                    </span>
                </a>
                <a href="{{ route('admin.profile.index') }}" class="btn btn-outline-success btn-sm rounded-circle" title="Profil & Ubah Password">
                    <i class="bi bi-person-gear"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#modalConfirmLogoutAdmin" title="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Toast Slideshow Notification Container Top Right -->
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999; max-width: 380px; width: 100%; pointer-events: none;" x-data="{
            toasts: [],
            addToast(title, message, type = 'success') {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, title, message, type });
                setTimeout(() => {
                    this.removeToast(id);
                }, 4000);
            },
            removeToast(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @show-toast.window="addToast($event.detail.title, $event.detail.message, $event.detail.type)"
        x-init="
        @if (session('success'))
            addToast('Berhasil!', '{{ session('success') }}');
        @endif
        @if (session('error'))
            addToast('Gagal!', '{{ session('error') }}', 'error');
        @endif
        @if (session('info'))
            addToast('Informasi', '{{ session('info') }}', 'info');
        @endif
        ">
            <template x-for="t in toasts" :key="t.id">
                <div class="toast show align-items-center text-white border-0 mb-2 shadow-lg rounded-3 toast-slide-in"
                     :class="t.type === 'error' ? 'bg-danger' : (t.type === 'info' ? 'bg-info text-dark' : 'bg-success')"
                     style="pointer-events: auto;"
                     role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-start gap-2">
                            <i class="bi fs-5" :class="t.type === 'error' ? 'bi-exclamation-triangle-fill' : (t.type === 'info' ? 'bi-info-circle-fill' : 'bi-check-circle-fill')"></i>
                            <div>
                                <strong class="d-block" :class="t.type === 'info' ? 'text-dark' : 'text-white'" x-text="t.title"></strong>
                                <span x-text="t.message" class="small"></span>
                            </div>
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" :class="t.type === 'info' ? '' : 'btn-close-white'" @click="removeToast(t.id)"></button>
                    </div>
                </div>
            </template>
        </div>

        @yield('content')
    </main>

    <!-- Modal Konfirmasi Logout Admin -->
    <div class="modal fade" id="modalConfirmLogoutAdmin" tabindex="-1" aria-hidden="true">
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
                        Sesi Admin Anda akan diakhiri dan Anda perlu masuk kembali untuk mengakses panel Administrator.
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
