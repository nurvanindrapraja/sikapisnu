@extends('layouts.admin')

@section('title', $event->title . ' - Detail Kegiatan ISNU')
@section('header_title', 'Detail Kegiatan & Presensi')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Kegiatan
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4 mb-4">
    <!-- Event Details Card -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill mb-2">
                            <i class="bi bi-calendar-event me-1"></i> {{ strtoupper($event->status) }}
                        </span>
                        <h4 class="fw-bold text-dark m-0">{{ $event->title }}</h4>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalEditEvent">
                        <i class="bi bi-pencil-square me-1"></i> Edit Kegiatan
                    </button>
                </div>

                <p class="text-secondary mb-4">{{ $event->description ?: 'Tidak ada deskripsi singkat.' }}</p>

                <div class="row g-3 bg-light p-3 rounded-4 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white p-2 rounded-circle shadow-sm text-success"><i class="bi bi-geo-alt-fill fs-5"></i></div>
                            <div>
                                <small class="text-muted d-block">Metode & Lokasi</small>
                                <strong class="text-dark">
                                    {{ ucfirst($event->method) }} - {{ $event->location }}
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white p-2 rounded-circle shadow-sm text-success"><i class="bi bi-clock-fill fs-5"></i></div>
                            <div>
                                <small class="text-muted d-block">Waktu Pelaksanaan</small>
                                <strong class="text-dark">
                                    {{ $event->event_date->translatedFormat('l, d F Y') }} ({{ $event->start_time }} - {{ $event->end_time }} WIB)
                                </strong>
                            </div>
                        </div>
                    </div>
                    @if($event->method === 'daring' && $event->meeting_link)
                        <div class="col-12 border-top pt-2 mt-2">
                            <small class="text-muted d-block mb-1"><i class="bi bi-camera-video me-1"></i> Link Meeting Virtual / Daring:</small>
                            <a href="{{ $event->meeting_link }}" target="_blank" class="fw-bold text-primary text-break">
                                {{ $event->meeting_link }} <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- LPJ & Documentation Section -->
                <div class="border-top pt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark m-0"><i class="bi bi-folder-symlink-fill text-success me-2"></i> Laporan (LPJ) & Foto Dokumentasi</h6>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalUploadReport">
                            <i class="bi bi-cloud-upload-fill me-1"></i> Upload LPJ / Foto
                        </button>
                    </div>

                    <!-- LPJ PDF File -->
                    <div class="mb-3">
                        @if($event->lpj_file)
                            <div class="d-flex align-items-center justify-content-between bg-success-subtle p-3 rounded-3 border border-success-subtle">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-3"></i>
                                    <div>
                                        <strong class="d-block text-dark">Laporan Pertanggungjawaban (LPJ).pdf</strong>
                                        <small class="text-success">File LPJ Resmi Tersimpan</small>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/'.$event->lpj_file) }}" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                                    <i class="bi bi-download me-1"></i> Unduh LPJ PDF
                                </a>
                            </div>
                        @else
                            <div class="bg-light p-3 rounded-3 text-center text-muted small border">
                                <i class="bi bi-file-earmark-x d-block fs-4 mb-1 text-secondary"></i>
                                Belum ada dokumen LPJ (PDF) yang diunggah.
                            </div>
                        @endif
                    </div>

                    <!-- Documentation Photos Gallery -->
                    <div>
                        <label class="form-label fw-semibold small text-muted mb-2">Foto Dokumentasi Kegiatan:</label>
                        @if($event->documentation_photos && count($event->documentation_photos) > 0)
                            <div class="row g-2">
                                @foreach($event->documentation_photos as $index => $photo)
                                    <div class="col-4 col-md-3 position-relative">
                                        <a href="{{ asset('storage/'.$photo) }}" target="_blank">
                                            <img src="{{ asset('storage/'.$photo) }}" alt="Dokumentasi" class="img-fluid rounded-3 object-fit-cover shadow-sm w-100" style="height: 100px;">
                                        </a>
                                        <form action="{{ route('admin.events.delete_photo', [$event->id, $index]) }}" method="POST" class="position-absolute top-0 end-0 p-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px;" title="Hapus foto ini">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small italic m-0">Belum ada foto dokumentasi yang diunggah.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Presence Link & Unique Share Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-isnu text-white">
            <div class="card-body p-4 d-flex flex-column justify-content-between" x-data="{
                link: '{{ route('event.presence.show', $event->unique_code) }}',
                copied: false,
                copyLink() {
                    navigator.clipboard.writeText(this.link);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2500);
                }
            }">
                <div>
                    <span class="badge bg-white text-dark px-3 py-1 rounded-pill fw-bold mb-3">
                        <i class="bi bi-qr-code-scan me-1"></i> LINK UNIK PRESENSI
                    </span>

                    <h5 class="fw-bold text-white mb-2">Presensi Kehadiran</h5>
                    <p class="text-white-50 small mb-4">Bagikan link presensi ini kepada pengurus atau anggota yang hadir.</p>

                    <!-- Status Masa Aktif -->
                    @php $status = $event->presenceStatus(); @endphp
                    <div class="p-3 rounded-3 mb-4 bg-white text-dark shadow-sm">
                        <small class="text-muted d-block fw-semibold mb-1">Status Keaktifan Presensi:</small>
                        @if($status === 'active')
                            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill mb-2 fw-bold">
                                <i class="bi bi-broadcast me-1"></i> SEDANG AKTIF
                            </span>
                        @elseif($status === 'not_started')
                            <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill mb-2 fw-bold">
                                <i class="bi bi-hourglass-split me-1"></i> BELUM DIMULAI
                            </span>
                        @else
                            <span class="badge bg-secondary text-white px-2.5 py-1 rounded-pill mb-2 fw-bold">
                                <i class="bi bi-clock-history me-1"></i> BERAKHIR
                            </span>
                        @endif

                        <div class="small text-secondary mt-1">
                            <div><strong>Mulai:</strong> {{ $event->presence_start_at->translatedFormat('d M Y H:i') }} WIB</div>
                            <div><strong>Sampai:</strong> {{ $event->presence_end_at->translatedFormat('d M Y H:i') }} WIB</div>
                        </div>
                    </div>

                    <div class="bg-black bg-opacity-25 p-3 rounded-3 mb-3 text-break">
                        <small class="text-white-50 d-block mb-1">URL Presensi:</small>
                        <code class="text-warning small" x-text="link"></code>
                    </div>
                </div>

                <div>
                    <button type="button" @click="copyLink()" class="btn btn-warning w-100 fw-bold rounded-pill py-2 shadow-sm text-dark">
                        <i class="bi" :class="copied ? 'bi-check2-all' : 'bi-clipboard-fill'"></i>
                        <span x-text="copied ? 'Link Berhasil Disalin!' : 'Salin Link Presensi'"></span>
                    </button>
                    <a :href="link" target="_blank" class="btn btn-outline-light w-100 rounded-pill py-2 mt-2 font-semibold">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Presensi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendees Presence Table Card -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 p-3 p-md-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-people-fill text-success me-2"></i> Daftar Kehadiran Presensi ({{ $presences->total() }} Orang)</h5>
            <small class="text-muted">Data anggota/pengurus yang telah melakukan presensi kehadiran pada kegiatan ini</small>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Peserta</th>
                        <th>NIK / No. Anggota</th>
                        <th>No. WhatsApp / HP</th>
                        <th>Institusi / PAC</th>
                        <th>Waktu Presensi</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presences as $index => $p)
                        <tr>
                            <td>{{ $presences->firstItem() + $index }}</td>
                            <td>
                                <strong class="d-block text-dark">{{ $p->name }}</strong>
                                @if($p->member)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill">Anggota SIKAP</span>
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-0.5 rounded-pill">Tamu / Umum</span>
                                @endif
                            </td>
                            <td><code>{{ $p->nik_or_member_number ?: '-' }}</code></td>
                            <td>{{ $p->phone ?: '-' }}</td>
                            <td><span class="fw-semibold text-dark">{{ $p->institution_or_pac ?: '-' }}</span></td>
                            <td>
                                <strong class="d-block text-dark">{{ $p->attended_at->translatedFormat('d M Y') }}</strong>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i> {{ $p->attended_at->format('H:i:s') }} WIB</small>
                            </td>
                            <td><code>{{ $p->ip_address ?: '-' }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                                Belum ada data presensi yang masuk.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-between align-items-center px-3 pb-3">
            <div class="small text-muted">
                Menampilkan {{ $presences->firstItem() ?? 0 }} - {{ $presences->lastItem() ?? 0 }} dari total {{ $presences->total() }} Peserta Presensi
            </div>
            <div>
                {{ $presences->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Event -->
<div class="modal fade" id="modalEditEvent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark border-0 p-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square me-1"></i> Edit Data Kegiatan & Masa Presensi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.events.update', $event->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" x-data="{ method: '{{ $event->method }}' }">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ $event->title }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deskripsi Kegiatan</label>
                            <textarea name="description" class="form-control" rows="2">{{ $event->description }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Metode <span class="text-danger">*</span></label>
                            <select name="method" class="form-select" x-model="method" required>
                                <option value="luring">Luring (Offline)</option>
                                <option value="daring">Daring (Online)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control" value="{{ $event->location }}" required>
                        </div>
                        <div class="col-12" x-show="method === 'daring'">
                            <label class="form-label fw-semibold">Link Meeting Daring</label>
                            <input type="url" name="meeting_link" class="form-control" value="{{ $event->meeting_link }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control" value="{{ $event->event_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" value="{{ $event->start_time }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" value="{{ $event->end_time }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Waktu Mulai Presensi <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="presence_start_at" class="form-control" value="{{ $event->presence_start_at->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Waktu Berakhir Presensi <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="presence_end_at" class="form-control" value="{{ $event->presence_end_at->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status Kegiatan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="planned" {{ $event->status === 'planned' ? 'selected' : '' }}>Direncana (Mendatang)</option>
                                <option value="completed" {{ $event->status === 'completed' ? 'selected' : '' }}>Terlaksana (Selesai)</option>
                                <option value="cancelled" {{ $event->status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Upload LPJ & Photos -->
<div class="modal fade" id="modalUploadReport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success text-white border-0 p-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-cloud-upload-fill me-1"></i> Upload LPJ & Dokumentasi</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.events.upload_report', $event->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File LPJ / Laporan (PDF)</label>
                        <input type="file" name="lpj_file" class="form-control" accept=".pdf">
                        <div class="form-text small">Format file PDF, maksimal 10MB.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Foto Dokumentasi Kegiatan</label>
                        <input type="file" name="photos[]" class="form-control" accept="image/jpeg,image/png,image/jpg" multiple>
                        <div class="form-text small">Bisa memilih beberapa foto sekaligus (JPG/PNG, max 5MB/foto).</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold rounded-pill px-4">Upload Dokumen</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
