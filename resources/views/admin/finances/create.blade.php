@extends('layouts.admin')

@section('title', 'Catat Transaksi Keuangan')

@section('content')
<div class="container-fluid px-0" x-data="createFinanceForm('{{ old('type', $defaultType) }}')">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.finances.index') }}" class="text-decoration-none">Keuangan & Kas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Catat Transaksi Baru</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-0">Catat Transaksi Kas Baru</h4>
            <p class="text-muted small mb-0">Catat arus kas masuk (pemasukan) atau kas keluar (pengeluaran) organisasi.</p>
        </div>
        <div>
            <a href="{{ route('admin.finances.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Kas
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat kesalahan pengisian form:</h6>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('admin.finances.store') }}" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true">
                @csrf

                <!-- 1. Pemilihan Jenis Transaksi -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark small text-uppercase">Tipe Transaksi <span class="text-danger">*</span></label>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="card h-100 p-3 border-2 rounded-4 cursor-pointer text-center transition-all"
                                   :class="selectedType === 'in' ? 'border-success bg-success-subtle shadow-sm' : 'border-light-subtle bg-light'"
                                   style="cursor: pointer;">
                                <input type="radio" name="type" value="in" class="d-none" x-model="selectedType" @change="onTypeChange('in')">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-arrow-down-left-circle-fill fs-3 text-success"></i>
                                    <div class="text-start">
                                        <div class="fw-bold text-dark">UANG MASUK (PENERIMAAN)</div>
                                        <small class="text-muted">Iuran, donasi, sponsorship, kas masuk</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="card h-100 p-3 border-2 rounded-4 cursor-pointer text-center transition-all"
                                   :class="selectedType === 'out' ? 'border-danger bg-danger-subtle shadow-sm' : 'border-light-subtle bg-light'"
                                   style="cursor: pointer;">
                                <input type="radio" name="type" value="out" class="d-none" x-model="selectedType" @change="onTypeChange('out')">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-arrow-up-right-circle-fill fs-3 text-danger"></i>
                                    <div class="text-start">
                                        <div class="fw-bold text-dark">UANG KELUAR (PENGELUARAN)</div>
                                        <small class="text-muted">Operasional, konsumsi, kegiatan, ATK</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- 2. Tanggal & Kategori -->
                    <div class="col-md-6">
                        <label for="transaction_date" class="form-label fw-bold text-dark small text-uppercase">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rounded-3 @error('transaction_date') is-invalid @enderror"
                               id="transaction_date" name="transaction_date"
                               value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                        @error('transaction_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="category" class="form-label fw-bold text-dark small text-uppercase">Kategori Transaksi <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3 @error('category') is-invalid @enderror" id="category" name="category" required x-model="selectedCategory">
                            <option value="">-- Pilih Kategori --</option>
                            <template x-for="cat in availableCategories" :key="cat">
                                <option :value="cat" x-text="cat" :selected="selectedCategory === cat"></option>
                            </template>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 3. Nominal Uang -->
                    <div class="col-md-6">
                        <label for="amount" class="form-label fw-bold text-dark small text-uppercase">Nominal (Rupiah) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold bg-light">Rp</span>
                            <input type="number" step="any" min="1"
                                   class="form-control rounded-end-3 fs-5 fw-bold @error('amount') is-invalid @enderror"
                                   id="amount" name="amount"
                                   x-model="rawAmount"
                                   placeholder="Contoh: 500000"
                                   required>
                        </div>
                        <div class="form-text small text-muted" x-show="rawAmount > 0">
                            Terbilang: <span class="fw-bold text-primary" x-text="formatRupiah(rawAmount)"></span>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 4. Metode Pembayaran -->
                    <div class="col-md-6">
                        <label for="payment_method" class="form-label fw-bold text-dark small text-uppercase">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3 @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                            @foreach(\App\Models\FinancialTransaction::PAYMENT_METHODS as $pm)
                                <option value="{{ $pm }}" {{ old('payment_method', 'Tunai / Kas') === $pm ? 'selected' : '' }}>
                                    {{ $pm }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 5. Judul / Peruntukan Transaksi -->
                    <div class="col-12">
                        <label for="title" class="form-label fw-bold text-dark small text-uppercase">Judul / Peruntukan Transaksi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3 @error('title') is-invalid @enderror"
                               id="title" name="title"
                               value="{{ old('title') }}"
                               placeholder="Contoh: Iuran Wajib Kas Pengurus Bulan November 2026 / Pembelian Konsumsi Rapat Kerja PC"
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 6. Keterangan Lengkap -->
                    <div class="col-12">
                        <label for="description" class="form-label fw-bold text-dark small text-uppercase">Keterangan / Rincian Tambahan</label>
                        <textarea class="form-control rounded-3 @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3"
                                  placeholder="Catatan rincian nama penyetor, tujuan detail penggunaan dana, nomor rekening, atau keterangan lainnya...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 7. Tingkatan & PAC -->
                    <div class="col-md-6">
                        <label for="level" class="form-label fw-bold text-dark small text-uppercase">Tingkatan Kas <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3 @error('level') is-invalid @enderror" id="level" name="level" x-model="selectedLevel" required>
                            <option value="PC ISNU">Tingkat Cabang (PC ISNU Kota Surabaya)</option>
                            <option value="PAC ISNU">Tingkat Anak Cabang (PAC ISNU Kecamatan)</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6" x-show="selectedLevel === 'PAC ISNU'" x-cloak>
                        <label for="pac_id" class="form-label fw-bold text-dark small text-uppercase">Pilih PAC ISNU <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3 @error('pac_id') is-invalid @enderror" id="pac_id" name="pac_id" :required="selectedLevel === 'PAC ISNU'">
                            <option value="">-- Pilih Wilayah PAC --</option>
                            @foreach($pacs as $pac)
                                <option value="{{ $pac->id }}" {{ old('pac_id') == $pac->id ? 'selected' : '' }}>
                                    PAC {{ $pac->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('pac_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 8. Upload Bukti Transaksi -->
                    <div class="col-12">
                        <label for="proof_file" class="form-label fw-bold text-dark small text-uppercase">Bukti Transaksi (Kwitansi / Nota / Struk / Bukti Transfer)</label>
                        <input type="file" class="form-control rounded-3 @error('proof_file') is-invalid @enderror"
                               id="proof_file" name="proof_file" accept="image/jpeg,image/png,application/pdf">
                        <div class="form-text small text-muted">
                            Format yang didukung: JPG, PNG, atau PDF. Ukuran berkas maksimal 5 MB.
                        </div>
                        @error('proof_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-5 pt-3 border-top">
                    <a href="{{ route('admin.finances.index') }}" class="btn btn-light rounded-pill px-4">
                        Batal
                    </a>
                    <button type="submit" class="btn text-white rounded-pill px-4 fw-semibold shadow-sm"
                            :class="selectedType === 'in' ? 'btn-success' : 'btn-danger'"
                            :disabled="isSubmitting">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" x-show="isSubmitting" x-cloak></span>
                        <i class="bi bi-save me-1" x-show="!isSubmitting"></i>
                        <span x-text="isSubmitting ? 'Menyimpan...' : (selectedType === 'in' ? 'Simpan Uang Masuk' : 'Simpan Uang Keluar')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function createFinanceForm(initType) {
    const categoriesIn = @json(\App\Models\FinancialTransaction::CATEGORIES_IN);
    const categoriesOut = @json(\App\Models\FinancialTransaction::CATEGORIES_OUT);

    return {
        selectedType: initType || 'in',
        selectedCategory: '{{ old('category', '') }}',
        selectedLevel: '{{ old('level', 'PC ISNU') }}',
        rawAmount: '{{ old('amount', '') }}',
        isSubmitting: false,

        get availableCategories() {
            return this.selectedType === 'in' ? categoriesIn : categoriesOut;
        },

        onTypeChange(type) {
            this.selectedType = type;
            // jika kategori sekarang tidak ada di opsi baru, reset
            const allowed = this.availableCategories;
            if (!allowed.includes(this.selectedCategory)) {
                this.selectedCategory = allowed[0] || '';
            }
        },

        formatRupiah(num) {
            if (!num || isNaN(num)) return 'Rp 0';
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }
    }
}
</script>
@endsection
