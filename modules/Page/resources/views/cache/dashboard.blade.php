@extends('layouts.theme')

@section('title', 'Page Cache Dashboard')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header p-4 border-bottom">
                    <h5 class="mb-1 fw-bold">Page Cache Dashboard</h5>
                    <p class="small mb-0 text-muted">Monitor cache performance and statistics</p>
                </div>

                <div id="cache-stats" class="card-body">
                    <div class="text-center py-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Controls --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Cache Controls</h6>
                    <button class="btn btn-primary w-100 mb-2" id="warm-btn">
                        <i class="fas fa-fire me-1"></i> Warm Cache
                    </button>
                    <button class="btn btn-warning w-100" id="clear-btn">
                        <i class="fas fa-trash me-1"></i> Clear Cache
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Recent Activity</h6>
                    <div id="audits-list" style="max-height: 200px; overflow-y: auto;">
                        <small class="text-muted">Loading...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const apiUrl = '{{ route('api.v1.cache.stats') }}';

    function loadStats() {
        $.get(apiUrl, function(stats) {
            const html = `
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body">
                                <h6 class="text-muted small mb-2">Hit Ratio</h6>
                                <h3 class="mb-1 fw-bold">${stats.hit_ratio}%</h3>
                                <small class="text-muted">${stats.total_requests} requests</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success h-100">
                            <div class="card-body">
                                <h6 class="text-muted small mb-2">Cache Hits</h6>
                                <h3 class="mb-1 fw-bold">${stats.total_hits}</h3>
                                <small class="text-muted">from total requests</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-info h-100">
                            <div class="card-body">
                                <h6 class="text-muted small mb-2">Cached Pages</h6>
                                <h3 class="mb-1 fw-bold">${stats.cached_pages_count}</h3>
                                <small class="text-muted">total pages</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body">
                                <h6 class="text-muted small mb-2">Cache Size</h6>
                                <h3 class="mb-1 fw-bold">${formatBytes(stats.size_bytes)}</h3>
                                <small class="text-muted">total size</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#cache-stats').html(html);
        });
    }

    function loadAudits() {
        $.get('{{ route('api.v1.cache.audits') }}', function(audits) {
            const html = audits.map(a => `
                <div class="mb-2 pb-2 border-bottom">
                    <small class="text-muted">${a.created_at}</small><br>
                    <strong>${a.action}</strong> ${a.slug ? ' - ' + a.slug : ''}
                </div>
            `).join('');
            $('#audits-list').html(html || '<small class="text-muted">No activity</small>');
        });
    }

    $('#warm-btn').click(function() {
        $(this).prop('disabled', true);
        $.post('{{ route('api.v1.cache.warm') }}', function() {
            toastr.success('Cache warming started');
            loadStats();
            setTimeout(() => $('#warm-btn').prop('disabled', false), 2000);
        });
    });

    $('#clear-btn').click(function() {
        if (confirm('Clear all page cache?')) {
            $(this).prop('disabled', true);
            $.post('{{ route('api.v1.cache.clear') }}', function() {
                toastr.success('Cache cleared');
                loadStats();
                setTimeout(() => $('#clear-btn').prop('disabled', false), 2000);
            });
        }
    });

    function formatBytes(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = Math.max(bytes, 0);
        const pow = Math.min(
            Math.floor(size ? Math.log(size) / Math.log(1024) : 0),
            units.length - 1
        );
        size /= Math.pow(1024, pow);
        return Math.round(size * 100) / 100 + ' ' + units[pow];
    }

    loadStats();
    loadAudits();
    setInterval(loadStats, 10000);
    setInterval(loadAudits, 10000);
});
</script>
@endpush
