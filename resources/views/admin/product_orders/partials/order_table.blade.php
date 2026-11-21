@if($orders->isEmpty())
    <div class="text-center py-5">
        <i class="bi bi-inbox display-4 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark">Belum Ada Pesanan Masuk</h5>
        <p class="text-secondary small mb-0">Pesanan dari anggota akan muncul di sini untuk dikelola oleh Bendahara.</p>
    </div>
@else
    <!-- Desktop Table View -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th>No. Pesanan & Tanggal</th>
                    <th>Pembeli / Anggota ISNU</th>
                    <th>Barang Dipesan</th>
                    <th class="text-end">Total Tagihan</th>
                    <th class="text-center">Bukti Transfer</th>
                    <th class="text-center">Status Pesanan</th>
                    <th class="text-center" style="width: {{ auth()->user()->isSuperAdmin() ? '160px' : '120px' }};">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $ord)
                    <tr id="order-row-{{ $ord->id }}" data-order-row="{{ $ord->id }}">
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
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('admin.product_orders.show', $ord->id) }}" class="btn btn-sm btn-isnu-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                                    Kelola <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                                @if(auth()->user()->isSuperAdmin())
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 btn-delete-order"
                                            data-id="{{ $ord->id }}"
                                            data-order-number="{{ $ord->order_number }}"
                                            data-recipient-name="{{ $ord->user->name ?? $ord->recipient_name }}"
                                            data-url="{{ route('admin.product_orders.destroy', $ord->id) }}"
                                            title="Hapus Pesanan">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="d-md-none p-3 d-flex flex-column gap-3">
        @foreach($orders as $ord)
            <div class="card border shadow-sm rounded-4" data-order-row="{{ $ord->id }}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <a href="{{ route('admin.product_orders.show', $ord->id) }}" class="fw-bold text-dark text-decoration-none hover-success d-block">
                                {{ $ord->order_number }}
                            </a>
                            <small class="text-muted" style="font-size: 0.74rem;">
                                {{ $ord->created_at->translatedFormat('d M Y, H:i') }}
                            </small>
                        </div>
                        <span class="badge {{ $ord->status_badge_class }} rounded-pill px-2 py-1 flex-shrink-0" style="font-size: 0.72rem;">
                            {{ $ord->status_label }}
                        </span>
                    </div>

                    <div class="bg-light rounded-3 p-2 mb-2 border">
                        <div class="fw-semibold text-dark small">{{ $ord->user->name ?? $ord->recipient_name }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-telephone me-1"></i>{{ $ord->recipient_phone }}
                        </div>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Barang Dipesan:</small>
                        @foreach($ord->items as $it)
                            <div class="small text-dark">
                                • {{ $it->product_name }} <span class="text-muted">({{ $it->quantity }}x)</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 pt-2 border-top">
                        <small class="text-muted">Total Tagihan</small>
                        <span class="fw-bold text-success">{{ $ord->formatted_grand_total }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                        @if($ord->payment_proof_url)
                            <button type="button"
                                    class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-semibold btn-view-proof"
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
                        <div class="d-inline-flex align-items-center gap-1">
                            <a href="{{ route('admin.product_orders.show', $ord->id) }}" class="btn btn-sm btn-isnu-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                                Kelola <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            @if(auth()->user()->isSuperAdmin())
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 btn-delete-order"
                                        data-id="{{ $ord->id }}"
                                        data-order-number="{{ $ord->order_number }}"
                                        data-recipient-name="{{ $ord->user->name ?? $ord->recipient_name }}"
                                        data-url="{{ route('admin.product_orders.destroy', $ord->id) }}"
                                        title="Hapus Pesanan">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($orders->hasPages())
        <div class="p-3 border-top d-flex justify-content-center ajax-pagination">
            {{ $orders->links() }}
        </div>
    @endif
@endif
