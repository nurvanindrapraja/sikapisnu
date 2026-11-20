@if($orders->isEmpty())
    <div class="text-center py-5">
        <i class="bi bi-inbox display-4 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark">Belum Ada Pesanan Masuk</h5>
        <p class="text-secondary small mb-0">Pesanan dari anggota akan muncul di sini untuk dikelola oleh Bendahara.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th>No. Pesanan & Tanggal</th>
                    <th>Pembeli / Anggota ISNU</th>
                    <th>Barang Dipesan</th>
                    <th class="text-end">Total Tagihan</th>
                    <th class="text-center">Bukti Transfer</th>
                    <th class="text-center">Status Pesanan</th>
                    <th class="text-center" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $ord)
                    <tr id="order-row-{{ $ord->id }}">
                        <td>
                            <a href="{{ route('admin.product_orders.show', $ord->id) }}" class="fw-bold text-dark text-decoration-none hover-success d-block">
                                {{ $ord->order_number }}
                            </a>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                {{ $ord->created_at->translatedFormat('d M Y, H:i') }}
                            </small>
                        </td>
                        <td>
                            <strong class="text-dark d-block">{{ $ord->user->name ?? $ord->recipient_name }}</strong>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <i class="bi bi-telephone me-1"></i> {{ $ord->recipient_phone }}
                            </small>
                        </td>
                        <td>
                            @foreach($ord->items as $it)
                                <div class="small text-dark text-truncate" style="max-width: 200px;">
                                    • {{ $it->product_name }} ({{ $it->quantity }}x)
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end fw-bold text-success">
                            {{ $ord->formatted_grand_total }}
                        </td>
                        <td class="text-center">
                            @if($ord->payment_proof_url)
                                <button type="button" 
                                        class="btn btn-sm btn-outline-success rounded-pill px-2 py-0 fw-semibold btn-view-proof"
                                        style="font-size: 0.75rem;"
                                        data-proof-url="{{ $ord->payment_proof_url }}"
                                        data-order-number="{{ $ord->order_number }}"
                                        data-recipient-name="{{ $ord->recipient_name }}"
                                        data-uploaded-at="{{ $ord->payment_uploaded_at?->translatedFormat('d M Y, H:i') ?? '-' }}"
                                        data-total="{{ $ord->formatted_grand_total }}">
                                    <i class="bi bi-image me-1"></i> Lihat Bukti
                                </button>
                            @else
                                <span class="badge bg-light text-muted border">Belum Upload</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $ord->status_badge_class }} rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                {{ $ord->status_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.product_orders.show', $ord->id) }}" class="btn btn-sm btn-isnu-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                                Kelola <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="p-3 border-top d-flex justify-content-center ajax-pagination">
            {{ $orders->links() }}
        </div>
    @endif
@endif
