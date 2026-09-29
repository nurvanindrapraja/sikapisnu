<div id="audit-table-wrapper" style="position: relative;">
    <!-- Loading Progress Bar -->
    <div id="audit-progress-bar" class="progress position-absolute top-0 start-0 end-0 rounded-top-4" style="height: 4px; display: none; z-index: 10;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%;"></div>
    </div>

    <div class="table-responsive">
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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-4 pt-3 border-top">
        <div class="text-muted small">
            Menampilkan <strong>{{ $logs->firstItem() ?? 0 }}</strong> sampai <strong>{{ $logs->lastItem() ?? 0 }}</strong> dari total <strong>{{ $logs->total() }}</strong> aktivitas
        </div>
        <div>
            {{ $logs->links() }}
        </div>
    </div>
</div>
