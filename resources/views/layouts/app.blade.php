<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIKAP ISNU Kota Surabaya') - Sistem Informasi Keanggotaan & Potensi</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    
    <!-- Google Fonts & Bootstrap 5 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Modern CSS -->
    <style>
        :root {
            --isnu-green: #006837;
            --isnu-green-dark: #004d28;
            --isnu-green-light: #0d8a4d;
            --isnu-gold: #d4af37;
            --isnu-gold-light: #f3e5ab;
            --isnu-dark: #121824;
            --isnu-card-bg: #ffffff;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar Styling */
        .navbar-isnu {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 20px rgba(0, 104, 55, 0.06);
            border-bottom: 1px solid rgba(0, 104, 55, 0.1);
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .navbar-brand img {
            height: 42px;
            object-fit: contain;
        }

        .nav-link {
            font-weight: 600;
            color: #475569;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--isnu-green);
        }

        .btn-isnu-primary {
            background: linear-gradient(135deg, var(--isnu-green), var(--isnu-green-dark));
            color: white;
            font-weight: 600;
            border: none;
            padding: 0.5rem 1.3rem;
            border-radius: 50rem;
            box-shadow: 0 4px 14px rgba(0, 104, 55, 0.25);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-isnu-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 104, 55, 0.35);
            color: white;
        }

        .btn-isnu-outline {
            border: 2px solid var(--isnu-green);
            color: var(--isnu-green);
            font-weight: 600;
            padding: 0.45rem 1.3rem;
            border-radius: 50rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-isnu-outline:hover {
            background-color: var(--isnu-green);
            color: white;
        }

        /* Mobile Collapsed Navbar Styling */
        @media (max-width: 991.98px) {
            .navbar-isnu .navbar-collapse {
                background: transparent;
                padding: 1rem 0 0.5rem;
                box-shadow: none;
                border: none;
            }
            .navbar-isnu .navbar-nav {
                text-align: center;
                width: 100%;
            }
            .navbar-isnu .nav-item {
                border-bottom: 1px solid rgba(0, 104, 55, 0.12);
                padding: 0.6rem 0;
            }
            .navbar-isnu .nav-item:last-child {
                border-bottom: 1px solid rgba(0, 104, 55, 0.12);
            }
            .navbar-isnu .nav-link {
                padding: 0.4rem 0;
                font-size: 1.05rem;
                font-weight: 700;
            }
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #003e21 0%, #006837 50%, #0d8a4d 100%);
            color: white;
            padding: 3rem 0 4.5rem;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 767.98px) {
            .hero-section {
                padding: 2rem 0 3.5rem;
            }
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        /* Footer Styling */
        footer {
            margin-top: auto;
            background-color: var(--isnu-dark);
            color: #94a3b8;
            padding: 3rem 0 1.5rem;
        }

        footer h5 {
            color: white;
            font-weight: 700;
        }

        footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
        }

        footer a:hover {
            color: var(--isnu-gold);
        }
    </style>

    @yield('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top navbar-isnu">
        <div class="container px-3 px-sm-4">
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="{{ route('home') }}">
                <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU Kota Surabaya">
                <div class="lh-sm">
                    <span class="fw-extrabold d-block text-dark mb-0" style="font-size: 1.05rem; letter-spacing: -0.4px; line-height: 1; font-weight: 800;">SIKAP ISNU</span>
                    <span class="text-muted fw-bold d-block" style="font-size: 0.65rem; margin-top: 2px; letter-spacing: 0.2px;">PC ISNU KOTA SURABAYA</span>
                </div>
            </a>

            <button class="navbar-toggler border-0 p-1 ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1 text-center">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tentang') ? 'active' : '' }}" href="{{ route('tentang') }}">Tentang ISNU</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('daftar.anggota') ? 'active' : '' }}" href="{{ route('daftar.anggota') }}">Katalog Anggota</a>
                    </li>
                </ul>

                <div class="d-flex flex-column flex-lg-row align-items-center gap-2 pt-3 pt-lg-0">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-isnu-primary w-100 w-lg-auto px-4 py-2 text-center">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        @else
                            <a href="{{ route('member.dashboard') }}" class="btn btn-isnu-primary w-100 w-lg-auto px-4 py-2 text-center">
                                <i class="bi bi-person-badge me-1"></i> Dashboard
                            </a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST" class="w-100 w-lg-auto">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100 w-lg-auto rounded-pill px-4 py-2 fw-semibold d-flex align-items-center justify-content-center gap-1.5">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    @else
                        <div class="d-flex flex-column flex-sm-row align-items-center gap-2 w-100 w-lg-auto">
                            <a href="{{ route('login') }}" class="btn btn-isnu-outline w-100 w-lg-auto text-center px-4 py-2">Masuk</a>
                            <a href="{{ route('register') }}" class="btn btn-isnu-primary w-100 w-lg-auto text-center px-4 py-2">Daftar</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer>
        <div class="container px-3 px-sm-4">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="{{ asset('images/logo_isnu.png') }}" alt="Logo ISNU" style="height: 40px;">
                        <h5 class="m-0">SIKAP ISNU Surabaya</h5>
                    </div>
                    <p class="small text-secondary">
                        Sistem Informasi Keanggotaan dan Potensi Kader Ikatan Sarjana Nahdlatul Ulama (ISNU) Kota Surabaya. Basis data terintegrasi untuk konsolidasi dan optimalisasi potensi intelektual sarjana NU.
                    </p>
                </div>
                <div class="col-lg-3 offset-lg-1 col-md-6">
                    <h5>Navigasi</h5>
                    <ul class="list-unstyled small d-flex flex-column gap-2 mt-3">
                        <li><a href="{{ route('home') }}"><i class="bi bi-chevron-right text-success me-1"></i> Beranda</a></li>
                        <li><a href="{{ route('tentang') }}"><i class="bi bi-chevron-right text-success me-1"></i> Profil ISNU Surabaya</a></li>
                        <li><a href="{{ route('daftar.anggota') }}"><i class="bi bi-chevron-right text-success me-1"></i> Direktori Kader</a></li>
                        <li><a href="{{ route('register') }}"><i class="bi bi-chevron-right text-success me-1"></i> Pendaftaran Anggota Baru</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>Kontak Sekretariat</h5>
                    <div class="small text-secondary mt-3 d-flex flex-column gap-2">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt-fill text-warning mt-1 flex-shrink-0"></i>
                            <span>Jl. Bubutan Gg. VI No.2, Alun-alun Contong, Kec. Bubutan, Kota Surabaya, Jawa Timur 60174</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope-fill text-warning flex-shrink-0"></i>
                            <a href="mailto:isnukotasurabaya@gmail.com">isnukotasurabaya@gmail.com</a>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-globe text-warning flex-shrink-0"></i>
                            <a href="https://isnusurabaya.or.id" target="_blank" rel="noopener">https://isnusurabaya.or.id</a>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-instagram text-warning flex-shrink-0"></i>
                            <a href="https://instagram.com/isnusurabaya" target="_blank" rel="noopener">isnusurabaya</a>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center small text-secondary">
                <p class="mb-0">&copy; {{ date('Y') }} PC ISNU Kota Surabaya. All Rights Reserved.</p>
                <p class="mb-0">Powered by <strong>SIKAP ISNU System</strong></p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @yield('scripts')
</body>
</html>
