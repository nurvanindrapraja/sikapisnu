@if($products->isEmpty())
    <div class="text-center py-5">
        <i class="bi bi-box-seam display-4 text-muted opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark">Belum Ada Data Produk</h5>
        <p class="text-secondary small mb-3">Tambahkan produk baru ke dalam katalog merchandise ISNU.</p>
        <button type="button" class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalCreateProduct">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Baru
        </button>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th style="width: 70px;" class="text-center">Foto</th>
                    <th>Nama Produk & Kategori</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Rating</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $prod)
                    <tr id="product-row-{{ $prod->id }}">
                        <td class="text-center">
                            <div class="rounded-3 bg-light border p-1 mx-auto" style="width: 52px; height: 52px; overflow: hidden;">
                                @if($prod->image_url)
                                    <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="w-100 h-100 object-fit-cover rounded-2">
                                @else
                                    <i class="bi bi-box-seam text-success fs-3 lh-lg"></i>
                                @endif
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('store.show', $prod->slug) }}" target="_blank" class="fw-bold text-dark text-decoration-none hover-success d-block">
                                {{ $prod->name }}
                            </a>
                            <div class="d-flex gap-2 mt-1">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0" style="font-size: 0.72rem;">
                                    {{ $prod->category_label }}
                                </span>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ $prod->weight_grams }} gram</small>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            {{ $prod->formatted_price }}
                        </td>
                        <td class="text-center">
                            @if($prod->stock <= 0)
                                <span class="badge bg-danger rounded-pill px-2">Habis</span>
                            @elseif($prod->stock < 5)
                                <span class="badge bg-warning text-dark rounded-pill px-2">{{ $prod->stock }} unit</span>
                            @else
                                <span class="badge bg-success rounded-pill px-2">{{ $prod->stock }} unit</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                {{ number_format($prod->average_rating, 1) }}
                                <small class="text-muted">({{ $prod->reviews_count }})</small>
                            </span>
                        </td>
                        <td class="text-center">
                            <button type="button" 
                                    class="btn btn-sm rounded-pill px-2 py-0 fw-semibold btn-toggle-status {{ $prod->is_active ? 'btn-success' : 'btn-secondary' }}" 
                                    style="font-size: 0.75rem;" 
                                    data-id="{{ $prod->id }}"
                                    data-url="{{ route('admin.products.toggle_status', $prod->id) }}"
                                    title="Klik untuk ubah status aktif/nonaktif">
                                <span class="btn-text">{{ $prod->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </button>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1 btn-edit-product" 
                                        data-id="{{ $prod->id }}"
                                        data-url="{{ route('admin.products.edit', $prod->id) }}"
                                        data-update-url="{{ route('admin.products.update', $prod->id) }}"
                                        title="Edit Produk">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger rounded-3 px-2 py-1 btn-delete-product" 
                                        data-id="{{ $prod->id }}"
                                        data-name="{{ $prod->name }}"
                                        data-url="{{ route('admin.products.destroy', $prod->id) }}"
                                        title="Hapus Produk">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
        <div class="p-3 border-top d-flex justify-content-center ajax-pagination">
            {{ $products->links() }}
        </div>
    @endif
@endif
