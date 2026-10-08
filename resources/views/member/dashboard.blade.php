@extends('layouts.member')

@section('title', 'Dashboard Member')

@section('content')
    <div class="row g-2 g-lg-4">
        <!-- Left Column: Digital Card & Verification Status -->
        <div class="col-lg-5">
            <div class="card card-custom p-3 p-md-4 mb-2 mb-lg-4 text-center">
                <h5 class="fw-bold text-dark mb-3">Status Keanggotaan</h5>

                @if($member->membership_status === 'menunggu_verifikasi')
                    <div class="alert alert-warning border-0 mb-3 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-clock-history fs-4"></i>
                            <strong class="fs-6">Menunggu Verifikasi Admin</strong>
                        </div>
                        <p class="small mb-0">Pendaftaran Anda telah diterima dan saat ini sedang ditinjau oleh Sekretariat PC
                            ISNU Surabaya.</p>
                    </div>
                @elseif($member->membership_status === 'calon')
                    <div class="alert alert-info border-0 mb-3 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-person-check-fill fs-4 text-info"></i>
                            <strong class="fs-6">Akun Terverifikasi (Calon Anggota)</strong>
                        </div>
                        <p class="small mb-0">Akun Anda telah diverifikasi dan aktif. Anda kini sudah dapat masuk ke aplikasi SIKAP ISNU. Kartu Anggota Digital akan diterbitkan setelah verifikasi keanggotaan selesai oleh Admin.</p>
                    </div>
                @elseif($member->membership_status === 'perbaikan')
                    <div class="alert alert-danger border-0 mb-3 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            <strong class="fs-6">Perlu Perbaikan Data</strong>
                        </div>
                        <p class="small mb-2">Catatan Admin: <em>"{{ $member->rejection_note }}"</em></p>
                        <a href="{{ route('member.profile.edit') }}"
                            class="btn btn-danger btn-sm rounded-pill font-weight-bold">
                            <i class="bi bi-pencil-square me-1"></i> Perbaiki Profil Sekarang
                        </a>
                    </div>
                @elseif(in_array($member->membership_status, ['terverifikasi', 'pengurus']))
                    <div class="alert alert-success border-0 mb-3 rounded-3 text-start">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-patch-check-fill fs-4 text-success"></i>
                            <strong class="fs-6">Anggota Terverifikasi Sah</strong>
                        </div>
                        <p class="small mb-0">Selamat! Keanggotaan Anda telah aktif secara resmi di PC ISNU Kota Surabaya.</p>
                    </div>
                @endif

                <!-- Digital Card Display -->
                @if(in_array($member->membership_status, ['terverifikasi', 'pengurus']) && $member->activeCard)
                    <div class="my-3">
                        <x-digital_card :member="$member" :card="$member->activeCard" />
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-isnu w-100 py-2.5 rounded-pill shadow-sm fw-bold" onclick="openCardDownloadModal('{{ route('member.card.download') }}', '{{ addslashes($member->full_name) }}', '{{ $member->member_number ?? '' }}')">
                            <i class="bi bi-person-vcard-fill me-1"></i> Unduh Kartu Digital (PNG / PDF)
                        </button>
                    </div>
                @else
                    <div class="my-3 p-4 bg-light rounded-4 text-center border border-dashed text-muted">
                        <div class="mb-3">
                            <i class="bi bi-card-heading text-secondary" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="fw-bold text-dark">Kartu Anggota Digital Belum Diterbitkan</h6>
                        <p class="small text-muted mb-0">
                            Kartu Anggota Digital akan diterbitkan secara otomatis setelah pendaftaran Anda diverifikasi dan
                            disetujui oleh Admin PC ISNU Kota Surabaya.
                        </p>
                    </div>
                @endif

                <!-- Pemesanan Kartu Fisik Section -->
                @php 
                    $latestOrder = $member->cardOrders ? $member->cardOrders->last() : null;
                    $hasActiveOrder = $latestOrder && $latestOrder->status !== 'received';
                @endphp
                <div class="mt-3 pt-3 border-top" x-data="cardOrderApp()">
                    <div class="d-flex flex-column gap-2">
                        @if($latestOrder)
                            <div class="p-3 rounded-4 text-start small border shadow-sm
                                @if($latestOrder->status === 'pending') bg-warning-subtle text-dark border-warning-subtle
                                @elseif($latestOrder->status === 'printed') bg-info-subtle text-dark border-info-subtle
                                @elseif($latestOrder->status === 'shipped' || $latestOrder->status === 'delivered') bg-primary-subtle text-dark border-primary-subtle
                                @else bg-success-subtle text-dark border-success-subtle @endif">
                                
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary-subtle">
                                    <strong class="d-flex align-items-center gap-1 text-dark fw-bold">
                                        <i class="bi bi-card-heading text-success fs-5"></i> Status Pemesanan:
                                    </strong>
                                    <span class="badge 
                                        @if($latestOrder->status === 'pending') bg-warning text-dark 
                                        @elseif($latestOrder->status === 'printed') bg-info text-white 
                                        @elseif($latestOrder->status === 'shipped' || $latestOrder->status === 'delivered') bg-primary text-white 
                                        @else bg-success text-white @endif px-2.5 py-1 rounded-pill fw-bold">
                                        @if($latestOrder->status === 'pending') DALAM PEMESANAN
                                        @elseif($latestOrder->status === 'printed') SUDAH JADI (DICETAK)
                                        @elseif($latestOrder->status === 'shipped' || $latestOrder->status === 'delivered') KARTU DIKIRIM
                                        @else KARTU DITERIMA @endif
                                    </span>
                                </div>

                                <!-- Timeline / Riwayat Pemesanan Timestamps -->
                                <div class="timeline-details bg-white p-2.5 rounded-3 border mb-2 extra-small">
                                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                        <span class="text-muted"><i class="bi bi-calendar-check me-1 text-primary"></i> Waktu Pemesanan:</span>
                                        <span class="fw-semibold text-dark">{{ $latestOrder->ordered_at ? $latestOrder->ordered_at->translatedFormat('d M Y, H:i') : $latestOrder->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                        <span class="text-muted"><i class="bi bi-printer me-1 text-info"></i> Waktu Kartu Jadi:</span>
                                        <span class="fw-semibold {{ $latestOrder->printed_at ? 'text-dark' : 'text-muted' }}">
                                            {{ $latestOrder->printed_at ? $latestOrder->printed_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Belum dicetak' }}
                                        </span>
                                    </div>
                                    @php $shippedTime = $latestOrder->shipped_at ?? ($latestOrder->status === 'delivered' ? $latestOrder->delivered_at : null); @endphp
                                    <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                        <span class="text-muted"><i class="bi bi-truck me-1 text-warning"></i> Waktu Kartu Dikirim:</span>
                                        <span class="fw-semibold {{ $shippedTime ? 'text-dark' : 'text-muted' }}">
                                            {{ $shippedTime ? $shippedTime->translatedFormat('d M Y, H:i') . ' WIB' : 'Belum dikirim' }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted"><i class="bi bi-box-seam me-1 text-success"></i> Waktu Kartu Diterima:</span>
                                        <span class="fw-semibold {{ $latestOrder->received_at ? 'text-success fw-bold' : 'text-muted' }}">
                                            {{ $latestOrder->received_at ? $latestOrder->received_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Belum diterima' }}
                                        </span>
                                    </div>
                                    @if($latestOrder->payment_proof)
                                        <div class="pt-2 mt-1 border-top text-center">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 rounded-pill extra-small fw-semibold d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#modalViewPaymentProof">
                                                <i class="bi bi-receipt text-primary"></i> Lihat Bukti Transfer Saya
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- Tombol Menerima Kartu (Hanya muncul ketika status kartu 'printed' (sudah jadi) atau 'shipped'/'delivered' (kartu dikirim)) -->
                                @if(in_array($latestOrder->status, ['printed', 'shipped', 'delivered']))
                                    <button type="button" class="btn btn-success btn-sm w-100 rounded-pill fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-1"
                                            data-bs-toggle="modal" data-bs-target="#modalConfirmReceiveCard">
                                        <i class="bi bi-box-seam-fill me-1"></i> Saya Sudah Menerima Kartu
                                    </button>
                                @endif
                            </div>
                        @endif

                        <!-- Tombol Pesan Kartu Anggota Fisik:
                             Selama proses pemesanan berlangsung ($hasActiveOrder), hilangkan tombol Pesan Kartu Anggota Fisik.
                             Jika kartu sudah diterima / belum pernah pesan, tampilkan tombol untuk memesan kartu. -->
                        @if(in_array($member->membership_status, ['terverifikasi', 'pengurus']) && ! $hasActiveOrder)
                            <button type="button" class="btn btn-outline-success w-100 py-2.5 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPesanKartuFisik">
                                <i class="bi bi-card-checklist me-1"></i> {{ $latestOrder ? 'Pesan Ulang Kartu Anggota Fisik' : 'Pesan Kartu Anggota Fisik' }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Profile Overview & Potensi Summary -->
        <div class="col-lg-7">
            <div class="card card-custom p-3 p-md-4 mb-2 mb-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-person-lines-fill text-success me-2"></i> Biodata
                        Anggota</h5>
                    <a href="{{ route('member.profile.edit') }}" class="btn btn-outline-success btn-sm rounded-pill">
                        <i class="bi bi-pencil-square me-1"></i> Edit Profil
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted w-35" style="font-size: 0.88rem;">Nama Lengkap</td>
                                <td class="fw-bold text-dark">{{ $member->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 0.88rem;">NIK</td>
                                <td class="fw-semibold text-dark">{{ $member->nik ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 0.88rem;">Nomor Anggota</td>
                                <td class="fw-bold font-monospace text-success">
                                    {{ $member->member_number ?? 'Belum diterbitkan' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 0.88rem;">Status Organisasi</td>
                                <td>
                                    <span
                                        class="badge {{ $member->membership_status === 'pengurus' ? 'bg-warning text-dark' : 'bg-success' }} px-3 py-1 text-uppercase">
                                        {{ $member->membership_status }}
                                    </span>
                                </td>
                            </tr>
                            @if($member->activePosition)
                                <tr>
                                    <td class="text-muted" style="font-size: 0.88rem;">Jabatan Pengurus</td>
                                    <td class="fw-bold text-warning">{{ $member->activePosition->position_title }} (Periode
                                        {{ $member->activePosition->period }})</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="text-muted" style="font-size: 0.88rem;">Pekerjaan / Profesi</td>
                                <td class="fw-semibold">{{ $member->occupation }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 0.88rem;">Kecamatan</td>
                                <td class="fw-semibold text-dark">
                                    {{ $member->kecamatan ?? ($member->mwc ? $member->mwc->name : '-') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 0.88rem;">Kontak HP & Email</td>
                                <td>{{ $member->phone }} | {{ $member->email }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 0.88rem;">Alamat Rumah</td>
                                <td>
                                    {{ $member->address }}, Kel. {{ $member->kelurahan }}, Kec. {{ $member->kecamatan }}
                                    {{ $member->city ? ', ' . $member->city : '' }}
                                    {{ ($member->province && $member->province !== 'JAWA TIMUR') ? ', ' . $member->province : '' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Potensi & Rekam Jejak Summary -->
            <div class="card card-custom p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h5 class="fw-bold text-dark m-0"><i class="bi bi-journal-bookmark-fill text-success me-2"></i> Rekam Jejak Kader</h5>
                    <a href="{{ route('member.profile.edit') }}" class="btn btn-sm btn-outline-success rounded-pill">
                        <i class="bi bi-pencil-square me-1"></i> Edit Potensi
                    </a>
                </div>

                <div class="d-flex flex-column gap-3">
                    <!-- 1. Riwayat Pendidikan -->
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-mortarboard-fill text-success me-2"></i> Riwayat Pendidikan</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5">{{ $member->educations->count() }}</span>
                        </h6>
                        @forelse($member->educations as $edu)
                            <div class="py-1 border-bottom border-light-subtle small">
                                <strong class="text-success">{{ $edu->level }} {{ $edu->major }}</strong> - {{ $edu->institution_name }}
                                @if($edu->degree) <span class="badge bg-white text-dark border ms-1">{{ $edu->degree }}</span> @endif
                                @if($edu->start_year || $edu->end_year)
                                    <div class="text-muted extra-small"><i class="bi bi-calendar-range me-1"></i>{{ $edu->start_year ?? '?' }} - {{ $edu->end_year ?? 'Sekarang' }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0 fst-italic">Belum ada riwayat pendidikan yang diinput.</p>
                        @endforelse
                    </div>

                    <!-- 2. Riwayat Organisasi -->
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-people-fill text-success me-2"></i> Riwayat Organisasi</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5">{{ $member->organizations->count() }}</span>
                        </h6>
                        @forelse($member->organizations as $org)
                            <div class="py-1 border-bottom border-light-subtle small">
                                <strong class="text-dark">{{ $org->organization_name }}</strong>
                                @if($org->position) - <span class="fw-semibold text-secondary">{{ $org->position }}</span> @endif
                                @if($org->period) <span class="text-muted ms-1">({{ $org->period }})</span> @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0 fst-italic">Belum ada riwayat organisasi yang diinput.</p>
                        @endforelse
                    </div>

                    <!-- 3. Riwayat Pekerjaan -->
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-briefcase-fill text-success me-2"></i> Riwayat Pekerjaan</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5">{{ $member->employments->count() }}</span>
                        </h6>
                        @forelse($member->employments as $emp)
                            <div class="py-1 border-bottom border-light-subtle small">
                                <strong class="text-dark">{{ $emp->company_name }}</strong> - <span class="fw-semibold text-secondary">{{ $emp->position }}</span>
                                @if($emp->start_year || $emp->end_year)
                                    <div class="text-muted extra-small"><i class="bi bi-calendar-range me-1"></i>{{ $emp->start_year ?? '?' }} - {{ $emp->end_year ?? 'Sekarang' }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0 fst-italic">Belum ada riwayat pekerjaan yang diinput.</p>
                        @endforelse
                    </div>

                    <!-- 4. Kaderisasi NU -->
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-award-fill text-success me-2"></i> Kaderisasi NU</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5">{{ $member->nuTrainings->count() }}</span>
                        </h6>
                        @forelse($member->nuTrainings as $nu)
                            <div class="py-1 border-bottom border-light-subtle small">
                                <span class="badge bg-success me-1">{{ $nu->training_type }}</span>
                                @if($nu->organizer) {{ $nu->organizer }} @endif
                                @if($nu->year) <span class="text-muted">({{ $nu->year }})</span> @endif
                                @if($nu->certificate_number)
                                    <div class="text-muted extra-small">No. Sertifikat: {{ $nu->certificate_number }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0 fst-italic">Belum ada kaderisasi NU yang diinput.</p>
                        @endforelse
                    </div>

                    <!-- 5. Sertifikasi Keahlian -->
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-patch-check-fill text-success me-2"></i> Sertifikasi Keahlian</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5">{{ $member->certifications->count() }}</span>
                        </h6>
                        @forelse($member->certifications as $cert)
                            <div class="py-1 border-bottom border-light-subtle small">
                                <strong class="text-dark">{{ $cert->certification_name }}</strong>
                                @if($cert->field) <span class="badge bg-info-subtle text-info-emphasis border ms-1">{{ $cert->field }}</span> @endif
                                @if($cert->issue_year) <span class="text-muted ms-1">- {{ $cert->issue_year }}</span> @endif
                                @if($cert->certificate_number)
                                    <div class="text-muted extra-small">No. Sertifikat: {{ $cert->certificate_number }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0 fst-italic">Belum ada sertifikasi keahlian yang diinput.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pemesanan Kartu Anggota Fisik & Result Popup (Requirement 8.1 / AJAX Order) -->
    <div x-data="cardOrderApp()">
        <div class="modal fade" id="modalPesanKartuFisik" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form action="{{ route('member.card_order.store') }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitOrder($event)" class="modal-content border-0 shadow-lg rounded-4">
                    @csrf
                    <div class="modal-header bg-success text-white border-0 rounded-top-4">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-card-heading me-2"></i> Pemesanan Kartu Anggota Fisik
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" :disabled="loading"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Informasi Biaya & Transfer -->
                        <div class="alert alert-success border-0 bg-success-subtle text-dark rounded-3 p-3 mb-3 small">
                            <div class="fw-bold mb-1 text-success d-flex align-items-center gap-1.5">
                                <i class="bi bi-info-circle-fill fs-6"></i> Informasi Ketentuan & Biaya Cetak Kartu
                            </div>
                            <p class="mb-2 text-muted">
                                Pemesanan Kartu Anggota ISNU Fisik dikenakan biaya sebesar <strong class="text-dark">Rp 100.000,-</strong> untuk ganti biaya cetak kartu PVC Card berkualitas dengan QR Code verifikasi resmi.
                            </p>
                            <div class="p-2.5 bg-white rounded-3 border">
                                <span class="text-muted d-block extra-small mb-1">Silakan transfer pembayaran ke rekening Bendahara:</span>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="text-dark font-monospace d-block" style="font-size: 0.95rem; letter-spacing: 0.5px;">2117301763</strong>
                                        <small class="text-muted fw-semibold d-block">Bank BNI a.n. <strong>Mohammad Taufiq</strong></small>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">Rp 100.000</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Alamat Lengkap Pengiriman / Pengambilan <span class="text-danger">*</span></label>
                            <textarea name="shipping_address" class="form-control" rows="3" required placeholder="Masukkan alamat lengkap rumah / lokasi penyerahan kartu..." :disabled="loading">{{ $member->address }}, Kel. {{ $member->kelurahan }}, Kec. {{ $member->kecamatan }}, {{ $member->city }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">No. HP / WhatsApp Konfirmasi <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ $member->phone }}" required placeholder="Contoh: 08123456789" :disabled="loading">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Catatan Tambahan (Opsional)</label>
                            <input type="text" name="notes" class="form-control" placeholder="Contoh: Titip di PAC ISNU Kecamatan Genteng" :disabled="loading">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Upload Bukti Transfer Pembayaran <span class="text-danger">*</span></label>
                            <input type="file" name="payment_proof" class="form-control" accept="image/jpeg,image/png,image/jpg,application/pdf" required :disabled="loading">
                            <small class="text-muted extra-small d-block mt-1">Format file yang didukung: JPG, PNG, PDF (Maksimal 3MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" :disabled="loading">Batal</button>
                        <button type="submit" class="btn btn-success fw-bold rounded-pill px-4" :disabled="loading">
                            <template x-if="loading">
                                <span class="d-inline-flex align-items-center">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    <span>Memproses...</span>
                                </span>
                            </template>
                            <template x-if="!loading">
                                <span><i class="bi bi-send-fill me-1"></i> Kirim Pemesanan</span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Popup Konfirmasi Penerimaan Kartu (Popup Window) -->
        <div class="modal fade" id="modalConfirmReceiveCard" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 text-center">
                    <div class="modal-header bg-success text-white border-0 rounded-top-4">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-box-seam-fill me-2"></i> Konfirmasi Penerimaan Kartu
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" :disabled="loadingReceive"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <div class="text-success mb-3">
                            <i class="bi bi-box-seam display-3"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Konfirmasi Penerimaan Kartu Fisik</h5>
                        <p class="text-muted mb-0">
                            Apakah Anda yakin telah menerima kartu anggota fisik ini secara langsung?
                        </p>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal" :disabled="loadingReceive">Batal</button>
                        @if($latestOrder)
                            <button type="button" @click="confirmReceiveOrder('{{ route('member.card_order.receive', $latestOrder->id) }}')" 
                                    class="btn btn-success fw-bold rounded-pill px-4" :disabled="loadingReceive">
                                <template x-if="loadingReceive">
                                    <span class="d-inline-flex align-items-center">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        <span>Memproses...</span>
                                    </span>
                                </template>
                                <template x-if="!loadingReceive">
                                    <span><i class="bi bi-check-circle-fill me-1"></i> Ya, Kartu Sudah Diterima</span>
                                </template>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Popup View Bukti Transfer Member -->
        @if($latestOrder && $latestOrder->payment_proof)
            @php $isPdf = \Illuminate\Support\Str::endsWith(strtolower($latestOrder->payment_proof), '.pdf'); @endphp
            <div class="modal fade" id="modalViewPaymentProof" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4 text-center">
                        <div class="modal-header bg-success text-white border-0 rounded-top-4">
                            <h5 class="modal-title fw-bold">
                                <i class="bi bi-receipt me-2"></i> Bukti Transfer Pembayaran Kartu Fisik
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-center">
                            @if($isPdf)
                                <iframe src="{{ asset('storage/' . $latestOrder->payment_proof) }}" class="w-100 rounded-3 border" style="height: 480px;"></iframe>
                            @else
                                <div class="text-center bg-light p-2 rounded-3 border">
                                    <img src="{{ asset('storage/' . $latestOrder->payment_proof) }}" class="img-fluid rounded-3 shadow-sm" style="max-height: 480px; object-fit: contain;" alt="Bukti Transfer">
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer border-0 bg-light rounded-bottom-4 justify-content-between">
                            <a href="{{ asset('storage/' . $latestOrder->payment_proof) }}" download target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                                <i class="bi bi-download me-1"></i> Unduh File
                            </a>
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal Popup Result Pemesanan (Informasi Berhasil / Gagal) -->
        <div class="modal fade" id="modalResultOrderInfo" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
                    <div class="modal-body p-0">
                        <template x-if="isSuccess">
                            <div class="text-success mb-3">
                                <i class="bi bi-check-circle-fill display-3"></i>
                            </div>
                        </template>
                        <template x-if="!isSuccess">
                            <div class="text-danger mb-3">
                                <i class="bi bi-x-circle-fill display-3"></i>
                            </div>
                        </template>
                        
                        <h5 class="fw-bold mb-2" :class="isSuccess ? 'text-success' : 'text-danger'" x-text="resultTitle"></h5>
                        <p class="text-muted small mb-3" x-text="resultMessage"></p>
                    </div>
                    <div class="modal-footer border-0 justify-content-center p-0">
                        <button type="button" class="btn rounded-pill px-4 fw-bold" :class="isSuccess ? 'btn-success' : 'btn-danger'" data-bs-dismiss="modal" @click="if (isSuccess) window.location.reload();">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>


    </div>
@endsection

@section('scripts')
<script>
    function cardOrderApp() {
        return {
            loading: false,
            loadingReceive: false,
            isSuccess: false,
            resultTitle: '',
            resultMessage: '',
            async submitOrder(e) {
                this.loading = true;
                const form = e.target;
                const formData = new FormData(form);
                
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    // Hide form modal
                    const orderModalEl = document.getElementById('modalPesanKartuFisik');
                    if (orderModalEl) {
                        const orderModal = bootstrap.Modal.getInstance(orderModalEl) || new bootstrap.Modal(orderModalEl);
                        orderModal.hide();
                    }
                    
                    if (response.ok && data.success) {
                        this.isSuccess = true;
                        this.resultTitle = 'Pemesanan Berhasil!';
                        this.resultMessage = data.message || 'Pemesanan Kartu Anggota ISNU Fisik berhasil dikirim.';
                    } else {
                        this.isSuccess = false;
                        this.resultTitle = 'Pemesanan Gagal';
                        this.resultMessage = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Terjadi kesalahan saat memproses pemesanan.');
                    }
                } catch (err) {
                    const orderModalEl = document.getElementById('modalPesanKartuFisik');
                    if (orderModalEl) {
                        const orderModal = bootstrap.Modal.getInstance(orderModalEl) || new bootstrap.Modal(orderModalEl);
                        orderModal.hide();
                    }
                    
                    this.isSuccess = false;
                    this.resultTitle = 'Pemesanan Gagal';
                    this.resultMessage = 'Terjadi kesalahan jaringan atau server. Silakan coba beberapa saat lagi.';
                } finally {
                    this.loading = false;
                    // Show result modal popup
                    const resultModalEl = document.getElementById('modalResultOrderInfo');
                    if (resultModalEl) {
                        const resultModal = bootstrap.Modal.getInstance(resultModalEl) || new bootstrap.Modal(resultModalEl);
                        resultModal.show();
                    }
                }
            },
            async confirmReceiveOrder(receiveUrl) {
                this.loadingReceive = true;
                
                // Hide confirm modal window
                const confirmModalEl = document.getElementById('modalConfirmReceiveCard');
                if (confirmModalEl) {
                    const confirmModal = bootstrap.Modal.getInstance(confirmModalEl) || new bootstrap.Modal(confirmModalEl);
                    confirmModal.hide();
                }

                try {
                    const response = await fetch(receiveUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.isSuccess = true;
                        this.resultTitle = 'Kartu Telah Diterima!';
                        this.resultMessage = data.message || 'Penerimaan kartu fisik berhasil dikonfirmasi.';
                    } else {
                        this.isSuccess = false;
                        this.resultTitle = 'Gagal Mengonfirmasi';
                        this.resultMessage = data.message || 'Terjadi kesalahan saat konfirmasi penerimaan kartu.';
                    }
                } catch (err) {
                    this.isSuccess = false;
                    this.resultTitle = 'Gagal Mengonfirmasi';
                    this.resultMessage = 'Terjadi kesalahan jaringan atau server. Silakan coba lagi.';
                } finally {
                    this.loadingReceive = false;
                    const resultModalEl = document.getElementById('modalResultOrderInfo');
                    if (resultModalEl) {
                        const resultModal = bootstrap.Modal.getInstance(resultModalEl) || new bootstrap.Modal(resultModalEl);
                        resultModal.show();
                    }
                }
            }
        }
    }
</script>
@endsection