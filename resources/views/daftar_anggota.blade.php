@extends('layouts.app')

@section('title', 'Katalog Anggota & Potensi')

@section('styles')
<style>
    .card-member-item {
        border: 1px solid rgba(0, 104, 55, 0.08) !important;
        transition: transform 0.25s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.25s ease, border-color 0.25s ease;
    }
    .card-member-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 30px rgba(0, 104, 55, 0.12) !important;
        border-color: rgba(0, 104, 55, 0.35) !important;
    }
    .card-member-item:hover .btn-outline-success {
        background-color: var(--isnu-green);
        color: white;
    }
    .profile-section-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .profile-section-card:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }

    /* Custom Green Pagination */
    .pagination .page-item.active .page-link {
        background-color: var(--isnu-green, #006837) !important;
        border-color: var(--isnu-green, #006837) !important;
        color: #ffffff !important;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0, 104, 55, 0.25);
    }
    .pagination .page-link {
        color: var(--isnu-green, #006837);
        border-radius: 0.375rem;
        margin: 0 2px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .pagination .page-link:hover {
        color: var(--isnu-green-dark, #004d28);
        background-color: #eaf3ed;
        border-color: #c2decb;
    }
    .pagination .page-link:focus {
        box-shadow: 0 0 0 0.25rem rgba(0, 104, 55, 0.25);
    }
    .pagination .page-item.disabled .page-link {
        color: #94a3b8;
    }
</style>
@endsection

@section('content')
    <div x-data="{
        loading: false,
        search: '{{ request('search') }}',
        fetchData(url = null) {
            this.loading = true;
            const targetUrl = url || ('{{ route('daftar.anggota') }}?search=' + encodeURIComponent(this.search));
            fetch(targetUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                const container = document.getElementById('memberGridContainer');
                container.innerHTML = html;
                if (window.Alpine) {
                    window.Alpine.initTree(container);
                }
                this.loading = false;
                window.scrollTo({ top: 150, behavior: 'smooth' });
            })
            .catch(err => {
                console.error('Fetch error:', err);
                this.loading = false;
            });
        }
    }">
        <!-- Top Fixed Progress Bar -->
        <div class="progress rounded-0 position-fixed top-0 start-0 w-100" style="height: 4px; z-index: 99999;" x-show="loading" x-cloak x-transition>
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success w-100"></div>
        </div>

        <div class="bg-success text-white py-4">
            <div class="container">
                <h2 class="fw-bold m-0"><i class="bi bi-people-fill me-2"></i> Katalog Anggota ISNU Surabaya</h2>
                <p class="mb-0 text-white-50">Daftar sarjana dan kader ISNU Kota Surabaya yang telah terverifikasi</p>
            </div>
        </div>

        <div class="container py-4">
            <!-- Filter Bar -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form @submit.prevent="fetchData()" class="row g-3">
                        <div class="col-md-9 col-lg-10">
                            <label class="form-label fw-bold text-muted small">Cari Nama / Profesi / No. Anggota</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" x-model="search" class="form-control border-start-0"
                                    placeholder="Contoh: Dokter, Dosen, Jurnalis, Aktivis NGO / LSM, IT, Ahmad...">
                            </div>
                        </div>

                        <div class="col-md-3 col-lg-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-isnu-primary w-100 fw-bold py-2 shadow-sm" :disabled="loading">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="loading" x-cloak></span>
                                <i class="bi bi-funnel-fill me-1" x-show="!loading"></i>
                                <span x-text="loading ? 'Memproses...' : 'Filter Data'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Member Grid Container -->
            <div id="memberGridContainer" @click="if ($event.target.closest('.pagination a')) { $event.preventDefault(); fetchData($event.target.closest('.pagination a').href); }">
                @include('partials.daftar_anggota_list')
            </div>
        </div>
    </div>

    <!-- Modal Popup Profil Singkat Anggota ISNU -->
    <div class="modal fade" id="modalDetailAnggota" tabindex="-1" aria-labelledby="modalDetailAnggotaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <!-- Modal Header -->
                <div class="modal-header text-white p-3 px-4 border-0" style="background: linear-gradient(135deg, #004d28 0%, #006837 100%);">
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="modalDetailAnggotaLabel">
                        <i class="bi bi-person-vcard-fill fs-5 text-warning"></i> Profil Singkat Anggota ISNU Surabaya
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-3 p-md-4">
                    <!-- Member Identity Banner -->
                    <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3 p-3 bg-light rounded-4 border mb-3">
                        <img id="modalMemberPhoto" src="" alt="Foto Anggota"
                             class="rounded-circle object-fit-cover border border-3 border-success shadow-sm flex-shrink-0"
                             style="width: 78px; height: 78px;">
                        <div class="text-center text-sm-start flex-grow-1 overflow-hidden">
                            <h5 class="fw-bold text-dark mb-1 text-truncate" id="modalMemberName">Nama Lengkap</h5>
                            <div class="d-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-2 mb-2">
                                <span class="badge bg-success px-2.5 py-1 rounded-pill" id="modalMemberBadge">ANGGOTA</span>
                                <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill d-none" id="modalMemberPositionBadge"></span>
                                <span class="badge bg-white text-secondary border px-2.5 py-1 rounded-pill" id="modalMemberRegion">
                                    <i class="bi bi-geo-alt-fill text-success me-1"></i> <span id="modalMemberRegionText">-</span>
                                </span>
                            </div>
                            <div class="small font-monospace text-muted">
                                <i class="bi bi-card-heading text-success me-1"></i> No. Anggota: <span class="fw-bold text-dark" id="modalMemberNumber">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Profil Singkat Cards: Pendidikan Terakhir, Pekerjaan Terakhir, Keahlian / Sertifikasi Terakhir -->
                    <div class="d-flex flex-column gap-3">
                        <!-- 1. Pendidikan Terakhir -->
                        <div class="profile-section-card p-3 rounded-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="bg-success-subtle text-success rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-mortarboard-fill fs-5"></i>
                                </div>
                                <h6 class="fw-bold text-dark m-0">Pendidikan Terakhir</h6>
                            </div>
                            <div id="modalEduContent" class="ms-1 pt-1">
                                <!-- Populated dynamically -->
                            </div>
                        </div>

                        <!-- 2. Pekerjaan Terakhir -->
                        <div class="profile-section-card p-3 rounded-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="bg-primary-subtle text-primary rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-briefcase-fill fs-5"></i>
                                </div>
                                <h6 class="fw-bold text-dark m-0">Pekerjaan Terakhir</h6>
                            </div>
                            <div id="modalEmpContent" class="ms-1 pt-1">
                                <!-- Populated dynamically -->
                            </div>
                        </div>

                        <!-- 3. Keahlian / Sertifikasi Terakhir -->
                        <div class="profile-section-card p-3 rounded-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="bg-warning-subtle text-warning-emphasis rounded-3 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-patch-check-fill fs-5"></i>
                                </div>
                                <h6 class="fw-bold text-dark m-0">Keahlian / Sertifikasi Terakhir</h6>
                            </div>
                            <div id="modalCertContent" class="ms-1 pt-1">
                                <!-- Populated dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light p-3 border-0 justify-content-between">
                    <small class="text-muted d-none d-sm-inline">
                        <i class="bi bi-shield-check text-success me-1"></i> Data resmi terverifikasi PC ISNU Kota Surabaya
                    </small>
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold ms-auto" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function displayMemberDetailModal(data) {
        if (!data) return;

        // Foto Profil
        const photoEl = document.getElementById('modalMemberPhoto');
        if (photoEl) {
            photoEl.src = data.photo_url || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(data.full_name) + '&background=006837&color=ffffff&size=256');
            photoEl.alt = data.full_name;
            photoEl.onerror = function() {
                this.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.full_name) + '&background=006837&color=ffffff&size=256';
            };
        }

        // Nama & Nomor Anggota
        document.getElementById('modalMemberName').textContent = data.full_name || '-';
        document.getElementById('modalMemberNumber').textContent = data.member_number || '-';

        // Badge Status
        const badgeEl = document.getElementById('modalMemberBadge');
        if (badgeEl) {
            badgeEl.textContent = (data.membership_status || 'ANGGOTA').toUpperCase();
            if (data.is_pengurus) {
                badgeEl.className = 'badge bg-warning text-dark px-2.5 py-1 rounded-pill fw-semibold';
            } else {
                badgeEl.className = 'badge bg-success px-2.5 py-1 rounded-pill fw-semibold';
            }
        }

        // Jabatan Pengurus jika ada
        const posBadgeEl = document.getElementById('modalMemberPositionBadge');
        if (posBadgeEl) {
            if (data.position_title) {
                posBadgeEl.textContent = data.position_title;
                posBadgeEl.classList.remove('d-none');
            } else {
                posBadgeEl.classList.add('d-none');
            }
        }

        // Wilayah (MWC / Kecamatan)
        const regionEl = document.getElementById('modalMemberRegion');
        const regionTextEl = document.getElementById('modalMemberRegionText');
        if (regionEl && regionTextEl) {
            if (data.mwc_name) {
                regionTextEl.textContent = data.mwc_name;
                regionEl.classList.remove('d-none');
            } else {
                regionEl.classList.add('d-none');
            }
        }

        // 1. Pendidikan Terakhir
        const eduContainer = document.getElementById('modalEduContent');
        if (eduContainer) {
            const edu = data.latest_education;
            if (edu && edu.has_data) {
                let html = '<div class="d-flex flex-wrap align-items-baseline gap-2 mb-1">';
                if (edu.level) {
                    html += `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 fw-bold">${escapeHtml(edu.level)}</span>`;
                }
                html += `<strong class="text-dark fs-6">${escapeHtml(edu.major || 'Program Studi')}</strong>`;
                if (edu.degree) {
                    html += `<span class="badge bg-white text-secondary border ms-1">${escapeHtml(edu.degree)}</span>`;
                }
                html += '</div>';

                if (edu.institution) {
                    html += `<div class="text-secondary small mb-1"><i class="bi bi-building me-1.5 text-success"></i>${escapeHtml(edu.institution)}</div>`;
                }
                if (edu.year) {
                    html += `<div class="text-muted extra-small"><i class="bi bi-calendar-check me-1 text-secondary"></i>${escapeHtml(edu.year)}</div>`;
                }
                eduContainer.innerHTML = html;
            } else {
                eduContainer.innerHTML = '<p class="text-muted fst-italic small mb-0"><i class="bi bi-dash-circle me-1"></i>Belum ada data riwayat pendidikan yang dicantumkan.</p>';
            }
        }

        // 2. Pekerjaan Terakhir
        const empContainer = document.getElementById('modalEmpContent');
        if (empContainer) {
            const emp = data.latest_employment;
            if (emp && emp.has_data) {
                let html = '<div class="d-flex flex-wrap align-items-baseline gap-2 mb-1">';
                html += `<strong class="text-dark fs-6">${escapeHtml(emp.position || 'Pekerjaan')}</strong>`;
                if (emp.status) {
                    html += `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5">${escapeHtml(emp.status)}</span>`;
                }
                html += '</div>';

                if (emp.company) {
                    html += `<div class="text-secondary small mb-1"><i class="bi bi-buildings me-1.5 text-primary"></i>${escapeHtml(emp.company)}</div>`;
                }
                if (emp.year) {
                    html += `<div class="text-muted extra-small"><i class="bi bi-clock-history me-1 text-secondary"></i>${escapeHtml(emp.year)}</div>`;
                }
                empContainer.innerHTML = html;
            } else {
                empContainer.innerHTML = '<p class="text-muted fst-italic small mb-0"><i class="bi bi-dash-circle me-1"></i>Belum ada data riwayat pekerjaan yang dicantumkan.</p>';
            }
        }

        // 3. Keahlian / Sertifikasi Terakhir
        const certContainer = document.getElementById('modalCertContent');
        if (certContainer) {
            const cert = data.latest_certification;
            if (cert && cert.has_data) {
                let html = '<div class="d-flex flex-wrap align-items-baseline gap-2 mb-1">';
                html += `<strong class="text-dark fs-6">${escapeHtml(cert.name || 'Sertifikasi / Keahlian')}</strong>`;
                if (cert.field) {
                    html += `<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0.5">${escapeHtml(cert.field)}</span>`;
                }
                html += '</div>';

                if (cert.organizer) {
                    html += `<div class="text-secondary small mb-1"><i class="bi bi-award me-1.5 text-warning"></i>${escapeHtml(cert.organizer)}</div>`;
                }
                if (cert.year) {
                    html += `<div class="text-muted extra-small"><i class="bi bi-calendar-event me-1 text-secondary"></i>${escapeHtml(cert.year)}</div>`;
                }
                certContainer.innerHTML = html;
            } else {
                certContainer.innerHTML = '<p class="text-muted fst-italic small mb-0"><i class="bi bi-dash-circle me-1"></i>Belum ada data keahlian atau sertifikasi yang dicantumkan.</p>';
            }
        }

        // Tampilkan Modal via Bootstrap 5 Modal
        const modalEl = document.getElementById('modalDetailAnggota');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('memberGridContainer');
        if (grid) {
            grid.addEventListener('click', function(e) {
                const card = e.target.closest('[data-member-info]');
                if (card && !e.target.closest('.pagination')) {
                    try {
                        const raw = card.getAttribute('data-member-info');
                        const data = JSON.parse(raw);
                        displayMemberDetailModal(data);
                    } catch (err) {
                        console.error('Gagal membuka profil singkat anggota:', err);
                    }
                }
            });
        }
    });
</script>
@endsection