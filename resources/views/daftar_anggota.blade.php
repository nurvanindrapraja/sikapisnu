@extends('layouts.app')

@section('title', 'Katalog Anggota & Potensi')

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
                document.getElementById('memberGridContainer').innerHTML = html;
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
@endsection