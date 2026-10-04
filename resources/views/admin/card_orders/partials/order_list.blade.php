<!-- Desktop Table View -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Pemesan</th>
                <th>Nomor Anggota</th>
                <th>Tgl Pesan</th>
                <th>Alamat Pengiriman & HP</th>
                <th>Status Pemesanan</th>
                <th class="text-end">Update Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $index => $o)
                <tr>
                    <td>{{ $orders->firstItem() + $index }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $o->member->photo_url }}" alt="" class="rounded-circle object-fit-cover" style="width: 38px; height: 38px;">
                            <div>
                                <strong class="d-block text-dark">{{ $o->member->full_name }}</strong>
                                <small class="text-muted">{{ $o->member->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td><code>{{ $o->member->member_number ?? '-' }}</code></td>
                    <td>
                        <div class="extra-small lh-sm">
                            <div class="text-dark"><i class="bi bi-calendar-event me-1 text-primary"></i> <strong>Pesan:</strong> {{ $o->ordered_at ? $o->ordered_at->translatedFormat('d M Y H:i') : $o->created_at->translatedFormat('d M Y H:i') }}</div>
                            <div class="{{ $o->printed_at ? 'text-dark' : 'text-muted' }}"><i class="bi bi-printer me-1 text-info"></i> <strong>Jadi:</strong> {{ $o->printed_at ? $o->printed_at->translatedFormat('d M Y H:i') : '-' }}</div>
                            <div class="{{ ($o->shipped_at ?? $o->delivered_at) ? 'text-dark' : 'text-muted' }}"><i class="bi bi-truck me-1 text-warning"></i> <strong>Dikirim:</strong> {{ ($o->shipped_at ?? $o->delivered_at) ? ($o->shipped_at ?? $o->delivered_at)->translatedFormat('d M Y H:i') : '-' }}</div>
                            <div class="{{ $o->received_at ? 'text-success fw-bold' : 'text-muted' }}"><i class="bi bi-box-seam me-1 text-success"></i> <strong>Diterima:</strong> {{ $o->received_at ? $o->received_at->translatedFormat('d M Y H:i') : '-' }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="small">
                            <strong class="text-dark d-block"><i class="bi bi-geo-alt me-1 text-danger"></i> {{ $o->shipping_address ?? $o->member->address }}</strong>
                            <span class="text-muted"><i class="bi bi-telephone me-1"></i> {{ $o->phone ?? $o->member->phone }}</span>
                            @if($o->notes)
                                <div class="text-secondary fst-italic">Catatan: "{{ $o->notes }}"</div>
                            @endif
                            @if($o->payment_proof)
                                <div class="mt-1">
                                    <button type="button" @click="openProofModal('{{ asset('storage/' . $o->payment_proof) }}', '{{ addslashes($o->member->full_name) }}')" class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-2.5 extra-small fw-semibold">
                                        <i class="bi bi-receipt me-1"></i> Bukti Transfer
                                    </button>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($o->status === 'pending')
                            <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill"><i class="bi bi-hourglass-split me-1"></i> Dalam Pemesanan</span>
                        @elseif($o->status === 'printed')
                            <span class="badge bg-info text-white px-3 py-1.5 rounded-pill"><i class="bi bi-printer-fill me-1"></i> Sudah Jadi</span>
                        @elseif($o->status === 'shipped' || $o->status === 'delivered')
                            <span class="badge bg-primary text-white px-3 py-1.5 rounded-pill"><i class="bi bi-truck me-1"></i> Kartu Dikirim</span>
                        @elseif($o->status === 'received')
                            <span class="badge bg-success px-3 py-1.5 rounded-pill"><i class="bi bi-box-seam-fill me-1"></i> Kartu Diterima</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex align-items-center justify-content-end gap-1.5">
                            <form action="{{ route('admin.card_orders.update_status', $o->id) }}" method="POST" class="d-inline-flex" @submit.prevent="updateStatusAjax($event, '{{ route('admin.card_orders.update_status', $o->id) }}')">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-select form-select-sm rounded-pill border-success" style="width: 165px;" onchange="this.form.dispatchEvent(new Event('submit'))">
                                    <option value="pending" {{ $o->status === 'pending' ? 'selected' : '' }}>Dalam Pemesanan</option>
                                    <option value="printed" {{ $o->status === 'printed' ? 'selected' : '' }}>Sudah Jadi</option>
                                    <option value="shipped" {{ $o->status === 'shipped' || $o->status === 'delivered' ? 'selected' : '' }}>Kartu Dikirim</option>
                                    <option value="received" {{ $o->status === 'received' ? 'selected' : '' }}>Kartu Diterima</option>
                                </select>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Hapus Pemesanan" @click="confirmDelete('{{ route('admin.card_orders.destroy', $o->id) }}', '{{ addslashes($o->member->full_name) }}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-card-heading display-4 d-block mb-2 text-secondary opacity-50"></i>
                        Belum ada data pemesanan kartu fisik.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="d-block d-md-none space-y-3">
    @forelse($orders as $o)
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white position-relative">
            <!-- Badge Status di Pojok Kanan Atas -->
            <div class="position-absolute top-0 end-0 m-3" style="z-index: 1;">
                @if($o->status === 'pending')
                    <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill">Dalam Pemesanan</span>
                @elseif($o->status === 'printed')
                    <span class="badge bg-info text-white px-2.5 py-1 rounded-pill">Sudah Jadi</span>
                @elseif($o->status === 'shipped' || $o->status === 'delivered')
                    <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill">Kartu Dikirim</span>
                @elseif($o->status === 'received')
                    <span class="badge bg-success px-2.5 py-1 rounded-pill">Kartu Diterima</span>
                @endif
            </div>

            <div class="d-flex align-items-start gap-3 mb-3" style="padding-right: 120px;">
                <img src="{{ $o->member->photo_url }}" alt="" class="rounded-circle object-fit-cover border border-2 border-success flex-shrink-0" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px; border-radius: 50%;">
                <div class="flex-grow-1 min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                    <h6 class="fw-bold text-dark mb-1 text-wrap text-break lh-sm">{{ $o->member->full_name }}</h6>
                    <small class="text-muted d-block font-monospace text-wrap text-break">{{ $o->member->member_number ?? '-' }}</small>
                </div>
            </div>

            <div class="bg-light p-2.5 rounded-3 mb-3 small">
                <div class="extra-small mb-2 p-2 bg-white rounded-2 border">
                    <div class="d-flex justify-content-between mb-0.5"><span class="text-muted">Tgl Pesan:</span> <span class="text-dark fw-semibold">{{ $o->ordered_at ? $o->ordered_at->translatedFormat('d M Y H:i') : '-' }}</span></div>
                    <div class="d-flex justify-content-between mb-0.5"><span class="text-muted">Tgl Jadi:</span> <span class="text-dark fw-semibold">{{ $o->printed_at ? $o->printed_at->translatedFormat('d M Y H:i') : '-' }}</span></div>
                    <div class="d-flex justify-content-between mb-0.5"><span class="text-muted">Tgl Dikirim:</span> <span class="text-dark fw-semibold">{{ ($o->shipped_at ?? $o->delivered_at) ? ($o->shipped_at ?? $o->delivered_at)->translatedFormat('d M Y H:i') : '-' }}</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Tgl Diterima:</span> <span class="text-success fw-bold">{{ $o->received_at ? $o->received_at->translatedFormat('d M Y H:i') : '-' }}</span></div>
                </div>
                <div class="mb-1">
                    <span class="text-muted d-block">Alamat Pengiriman:</span>
                    <strong class="text-dark d-block text-break">{{ $o->shipping_address ?? $o->member->address }}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted">No. HP / WA:</span>
                        <span class="text-dark fw-semibold">{{ $o->phone ?? $o->member->phone }}</span>
                    </div>
                    @if($o->payment_proof)
                        <button type="button" @click="openProofModal('{{ asset('storage/' . $o->payment_proof) }}', '{{ addslashes($o->member->full_name) }}')" class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-2.5 extra-small fw-semibold">
                            <i class="bi bi-receipt me-1"></i> Bukti Transfer
                        </button>
                    @endif
                </div>
            </div>

            <label class="form-label fw-bold small text-muted mb-1">Ubah Status Pemesanan:</label>
            <div class="d-flex align-items-center gap-2">
                <div class="flex-grow-1">
                    <form action="{{ route('admin.card_orders.update_status', $o->id) }}" method="POST" @submit.prevent="updateStatusAjax($event, '{{ route('admin.card_orders.update_status', $o->id) }}')">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-select form-select-sm rounded-pill border-success" onchange="this.form.dispatchEvent(new Event('submit'))">
                            <option value="pending" {{ $o->status === 'pending' ? 'selected' : '' }}>Dalam Pemesanan</option>
                            <option value="printed" {{ $o->status === 'printed' ? 'selected' : '' }}>Sudah Jadi</option>
                            <option value="shipped" {{ $o->status === 'shipped' || $o->status === 'delivered' ? 'selected' : '' }}>Kartu Dikirim</option>
                            <option value="received" {{ $o->status === 'received' ? 'selected' : '' }}>Kartu Diterima</option>
                        </select>
                    </form>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1" title="Hapus Pemesanan" @click="confirmDelete('{{ route('admin.card_orders.destroy', $o->id) }}', '{{ addslashes($o->member->full_name) }}')">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
        </div>
    @empty
        <div class="text-center py-4 text-muted bg-light rounded-4">
            <i class="bi bi-card-heading display-5 d-block mb-2 text-secondary opacity-50"></i>
            Belum ada data pemesanan kartu fisik.
        </div>
    @endforelse
</div>

<!-- Pagination Footer -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-2 border-top">
    <div class="text-muted small text-center text-md-start">
        Menampilkan <strong>{{ $orders->firstItem() ?? 0 }}</strong> sampai <strong>{{ $orders->lastItem() ?? 0 }}</strong> dari total <strong>{{ $orders->total() }}</strong> pemesanan
    </div>
    <div>
        {{ $orders->links() }}
    </div>
</div>
