@extends('layouts.theme')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>🏥 System Health Check</h2>
                <button id="refresh-btn" class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise"></i> Refresh All
                </button>
            </div>

            <!-- Overall Status Card -->
            <div class="card mb-4" id="overall-status-card">
                <div class="card-body text-center">
                    <h3 id="overall-status-text">Checking...</h3>
                    <div class="spinner-border" role="status" id="loading-spinner">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Last checked: <span id="last-checked">Never</span></p>
                </div>
            </div>

            <div class="row">
                <!-- OAuth Connections -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">🔐 OAuth Connections</h5>
                        </div>
                        <div class="card-body" id="oauth-status">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Publishers -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">📤 Publishers</h5>
                        </div>
                        <div class="card-body" id="publishers-status">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Webhooks -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">🔗 Webhooks</h5>
                        </div>
                        <div class="card-body" id="webhooks-status">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Queue -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">⚙️ Queue Workers</h5>
                        </div>
                        <div class="card-body" id="queue-status">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduler -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">⏰ Scheduler</h5>
                        </div>
                        <div class="card-body" id="scheduler-status">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Database & Redis -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">💾 Database & Redis</h5>
                        </div>
                        <div class="card-body" id="infrastructure-status">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let healthData = null;

    // Load health data on page load
    loadHealthData();

    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', function() {
        loadHealthData();
    });

    // Auto-refresh every 60 seconds
    setInterval(loadHealthData, 60000);

    function loadHealthData() {
        // Show loading
        document.getElementById('loading-spinner').style.display = 'block';

        fetch('{{ route("admin.social.health.check") }}')
            .then(response => response.json())
            .then(data => {
                healthData = data;
                renderHealthData(data);
                document.getElementById('loading-spinner').style.display = 'none';
                document.getElementById('last-checked').textContent = new Date().toLocaleTimeString();
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('overall-status-text').textContent = '❌ Error loading health data';
                document.getElementById('loading-spinner').style.display = 'none';
            });
    }

    function renderHealthData(data) {
        // Overall status
        const statusCard = document.getElementById('overall-status-card');
        const statusText = document.getElementById('overall-status-text');

        if (data.overall_status === 'healthy') {
            statusCard.className = 'card mb-4 border-success';
            statusText.textContent = '✅ All Systems Operational';
            statusText.className = 'text-success';
        } else if (data.overall_status === 'degraded') {
            statusCard.className = 'card mb-4 border-warning';
            statusText.textContent = '⚠️ Some Issues Detected';
            statusText.className = 'text-warning';
        } else {
            statusCard.className = 'card mb-4 ';
            statusText.textContent = '❌ System Unhealthy';
            statusText.className = 'text-danger';
        }

        // OAuth Connections
        renderOAuthStatus(data.checks.oauth_connections);

        // Publishers
        renderPublishersStatus(data.checks.publishers);

        // Webhooks
        renderWebhooksStatus(data.checks.webhooks);

        // Queue
        renderQueueStatus(data.checks.queue);

        // Scheduler
        renderSchedulerStatus(data.checks.scheduler);

        // Infrastructure (Database + Redis)
        renderInfrastructureStatus(data.checks.database, data.checks.redis);
    }

    function renderOAuthStatus(oauth) {
        const container = document.getElementById('oauth-status');
        let html = `
            <p><strong>Total Accounts:</strong> ${oauth.total_accounts}</p>
            <p><strong>Healthy:</strong> ${oauth.healthy_accounts}</p>
            <div class="mt-3">
        `;

        oauth.accounts.forEach(account => {
            const statusBadge = getStatusBadge(account.status);
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                    <div>
                        <strong>${account.account}</strong>
                        <br><small class="text-muted">${account.network}</small>
                    </div>
                    <div class="text-end">
                        ${statusBadge}
                        ${account.expires_at ? `<br><small>Expires: ${account.days_until_expiry} days</small>` : ''}
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    function renderPublishersStatus(publishers) {
        const container = document.getElementById('publishers-status');
        let html = `
            <p><strong>Total Publishers:</strong> ${publishers.total}</p>
            <p><strong>Healthy:</strong> ${publishers.healthy}</p>
            <div class="mt-3">
        `;

        Object.entries(publishers.publishers).forEach(([network, data]) => {
            const statusBadge = getStatusBadge(data.status);
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                    <strong>${network.charAt(0).toUpperCase() + network.slice(1)}</strong>
                    ${statusBadge}
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    function renderWebhooksStatus(webhooks) {
        const container = document.getElementById('webhooks-status');
        let html = `
            <p><strong>Total Webhooks:</strong> ${webhooks.total}</p>
            <p><strong>Reachable:</strong> ${webhooks.reachable}</p>
            <div class="mt-3">
        `;

        Object.entries(webhooks.webhooks).forEach(([network, data]) => {
            const statusBadge = getStatusBadge(data.status);
            html += `
                <div class="mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${network.charAt(0).toUpperCase() + network.slice(1)}</strong>
                        ${statusBadge}
                    </div>
                    <small class="text-muted">${data.url}</small>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    function renderQueueStatus(queue) {
        const container = document.getElementById('queue-status');
        const statusBadge = getStatusBadge(queue.status);

        let html = `
            <div class="mb-3">
                ${statusBadge}
            </div>
            <p><strong>Pending Jobs:</strong> ${queue.pending_jobs}</p>
            <p><strong>Failed Jobs:</strong> ${queue.failed_jobs}</p>
            ${queue.last_job_created_at ? `<p><small class="text-muted">Last job: ${queue.last_job_created_at}</small></p>` : ''}
        `;

        if (queue.failed_jobs > 0) {
            html += `
                <div class="mt-3">
                    <button class="btn btn-sm btn-warning me-2" onclick="retryFailedJobs()">Retry Failed</button>
                    <button class="btn btn-sm btn-danger" onclick="clearFailedJobs()">Clear Failed</button>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    function renderSchedulerStatus(scheduler) {
        const container = document.getElementById('scheduler-status');
        const statusBadge = getStatusBadge(scheduler.status);

        let html = `
            <div class="mb-3">
                ${statusBadge}
            </div>
            <p>${scheduler.message}</p>
            ${scheduler.overdue_posts > 0 ? `<p class="text-danger"><strong>Overdue Posts:</strong> ${scheduler.overdue_posts}</p>` : ''}
            <p><small class="text-muted">Next run: ${scheduler.next_run}</small></p>
        `;

        container.innerHTML = html;
    }

    function renderInfrastructureStatus(database, redis) {
        const container = document.getElementById('infrastructure-status');

        const dbBadge = getStatusBadge(database.status);
        const redisBadge = getStatusBadge(redis.status);

        let html = `
            <div class="mb-3">
                <h6>Database</h6>
                ${dbBadge}
                <p class="mt-2">Total Posts: ${database.total_posts || 0}</p>
            </div>
            <hr>
            <div>
                <h6>Redis</h6>
                ${redisBadge}
                <p class="mt-2">Connection: ${redis.connection || 'unknown'}</p>
            </div>
        `;

        container.innerHTML = html;
    }

    function getStatusBadge(status) {
        const badges = {
            'healthy': '<span class="badge bg-success">✅ Healthy</span>',
            'degraded': '<span class="badge bg-warning">⚠️ Degraded</span>',
            'unhealthy': '<span class="badge bg-danger">❌ Unhealthy</span>',
            'error': '<span class="badge bg-danger">❌ Error</span>',
            'reachable': '<span class="badge bg-success">✅ Reachable</span>',
            'unreachable': '<span class="badge bg-danger">❌ Unreachable</span>',
            'invalid_token': '<span class="badge bg-danger">❌ Invalid Token</span>',
            'expired': '<span class="badge bg-danger">❌ Expired</span>',
            'expiring_soon': '<span class="badge bg-warning">⚠️ Expiring Soon</span>',
            'stale': '<span class="badge bg-warning">⚠️ Stale</span>',
            'backlogged': '<span class="badge bg-warning">⚠️ Backlogged</span>',
            'missing': '<span class="badge bg-danger">❌ Missing</span>',
        };

        return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
    }

    // Global functions for buttons
    window.retryFailedJobs = function() {
        if (confirm('Retry all failed jobs?')) {
            fetch('{{ route("admin.social.health.retry-failed-jobs") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                loadHealthData();
            });
        }
    };

    window.clearFailedJobs = function() {
        if (confirm('Clear all failed jobs? This cannot be undone.')) {
            fetch('{{ route("admin.social.health.clear-failed-jobs") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                loadHealthData();
            });
        }
    };
});
</script>
@endpush
@endsection
