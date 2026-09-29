@extends('layouts.admin')

@section('title', 'Audit Log System')
@section('header_title', 'Rekaman Aktivitas Keamanan & Verifikasi')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <!-- Form Search -->
        <form id="audit-search-form" action="{{ route('admin.audit.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control" placeholder="Cari aktivitas, user, email, atau IP address..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100 fw-bold">
                    <i class="bi bi-search me-1"></i> Cari Audit Log
                </button>
            </div>
        </form>

        <!-- Dynamic AJAX Table & Pagination Container -->
        <div id="audit-table-container">
            @include('admin.audit.partials.log_table')
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('audit-table-container');
    const searchForm = document.getElementById('audit-search-form');

    function loadAuditData(url) {
        const progressBar = document.getElementById('audit-progress-bar');
        const wrapper = document.getElementById('audit-table-wrapper');
        
        if (progressBar) progressBar.style.display = 'flex';
        if (wrapper) wrapper.style.opacity = '0.4';

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
            window.history.pushState({}, '', url);
        })
        .catch(err => {
            console.error('Gagal memuat data audit log:', err);
            if (progressBar) progressBar.style.display = 'none';
            if (wrapper) wrapper.style.opacity = '1';
        });
    }

    // Intercept Pagination Link Clicks
    container.addEventListener('click', function (e) {
        const link = e.target.closest('.pagination a');
        if (link) {
            e.preventDefault();
            loadAuditData(link.href);
        }
    });

    // Intercept Search Form Submit
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const action = searchForm.getAttribute('action');
            const params = new URLSearchParams(new FormData(searchForm)).toString();
            const url = action + (params ? '?' + params : '');
            loadAuditData(url);
        });
    }
});
</script>
@endsection
