<div id="audit-table-wrapper" style="position: relative;">
    <!-- Loading Progress Bar -->
    <div id="audit-progress-bar" class="progress position-absolute top-0 start-0 end-0 rounded-top-4" style="height: 4px; display: none; z-index: 10;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%;"></div>
    </div>

    <!-- Desktop Table View -->
    <div class="d-none d-md-block table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Waktu (WIB)</th>
                    <th>User / Pelaku</th>
                    <th>Tindakan (Action)</th>
                    <th>Deskripsi Aktivitas</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td><small class="text-muted fw-bold">{{ $log->created_at->translatedFormat('d M Y H:i:s') }}</small></td>
                        <td>
                            <strong>{{ $log->user ? $log->user->name : 'Sistem/Publik' }}</strong>
                            @if($log->user)
                                <small class="text-muted d-block">{{ $log->user->email }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">{{ $log->action }}</span></td>
                        <td>{{ $log->description ?? '-' }}</td>
                        <td><code>{{ $log->ip_address ?? '-' }}</code></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada catatan aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="d-md-none d-flex flex-column gap-3">
        @forelse($logs as $log)
            <div class="card border shadow-sm rounded-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="fw-bold text-dark lh-sm">{{ $log->user ? $log->user->name : 'Sistem/Publik' }}</div>
                            @if($log->user)
                                <div class="small text-muted text-truncate">{{ $log->user->email }}</div>
                            @endif
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 flex-shrink-0" style="font-size: 0.72rem;">{{ $log->action }}</span>
                    </div>

                    <div class="small text-dark mb-2" style="word-break: break-word;">
                        {{ $log->description ?? '-' }}
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top small text-muted">
                        <span><i class="bi bi-clock me-1"></i>{{ $log->created_at->translatedFormat('d M Y H:i:s') }}</span>
                        <code>{{ $log->ip_address ?? '-' }}</code>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-4 text-muted bg-light rounded-4 border">
                Belum ada catatan aktivitas.
            </div>
        @endforelse
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
        <div class="text-muted small">
            Menampilkan <strong>{{ $logs->firstItem() ?? 0 }}</strong> sampai <strong>{{ $logs->lastItem() ?? 0 }}</strong> dari total <strong>{{ $logs->total() }}</strong> aktivitas
        </div>
        <div>
            {{ $logs->links() }}
        </div>
    </div>
</div>
