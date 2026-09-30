<div class="row g-4">
    @forelse($members as $m)
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ $m->photo_url }}" alt="{{ $m->full_name }}"
                            class="rounded-circle object-fit-cover border border-2 border-success"
                            style="width: 58px; height: 58px;">
                        <div class="overflow-hidden">
                            <h6 class="fw-bold text-dark m-0 text-truncate" title="{{ $m->full_name }}">
                                {{ $m->full_name }}</h6>
                            <span
                                class="badge {{ $m->membership_status === 'pengurus' ? 'bg-warning text-dark' : 'bg-success' }} small mt-1">
                                {{ strtoupper($m->membership_status) }}
                            </span>
                        </div>
                    </div>

                    <div class="small text-secondary space-y-1">
                        <div><i class="bi bi-card-text me-1 text-success"></i> {{ $m->member_number ?? '-' }}</div>
                        <div><i class="bi bi-briefcase me-1 text-success"></i> {{ $m->occupation }}</div>
                        @if($m->mwc)
                            <div><i class="bi bi-geo-alt me-1 text-success"></i> {{ $m->mwc->name }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5">
            <i class="bi bi-search text-muted display-1"></i>
            <h5 class="fw-bold text-muted mt-3">Tidak ada data anggota yang cocok.</h5>
        </div>
    @endforelse
</div>

<div class="mt-4 d-flex justify-content-center">
    {{ $members->links() }}
</div>
