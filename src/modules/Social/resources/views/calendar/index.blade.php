@extends('layouts.theme')

@section('title', 'Content Calendar')

@section('content')

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filtros
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Social Account Filter -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cuenta Social</label>
                        <select class="form-select" id="filterSocialAccount">
                            <option value="">Todas las cuentas</option>
                            @foreach($socialAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->network->label() }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Campaign Filter -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Campaña</label>
                        <select class="form-select" id="filterCampaign">
                            <option value="">Todas las campañas</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <div class="form-check">
                            <input class="form-check-input status-filter" type="checkbox" value="draft" id="statusDraft" checked>
                            <label class="form-check-label" for="statusDraft">
                                <span class="badge bg-secondary">Draft</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input status-filter" type="checkbox" value="scheduled" id="statusScheduled" checked>
                            <label class="form-check-label" for="statusScheduled">
                                <span class="badge bg-primary">Scheduled</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input status-filter" type="checkbox" value="published" id="statusPublished" checked>
                            <label class="form-check-label" for="statusPublished">
                                <span class="badge bg-success">Published</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input status-filter" type="checkbox" value="failed" id="statusFailed">
                            <label class="form-check-label" for="statusFailed">
                                <span class="badge bg-danger">Failed</span>
                            </label>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100" id="applyFilters">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                </div>
            </div>

            <!-- Legend -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Leyenda</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 20px; height: 20px; background: #6c757d; border-radius: 4px;" class="me-2"></div>
                        <small>Draft</small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 20px; height: 20px; background: #0d6efd; border-radius: 4px;" class="me-2"></div>
                        <small>Scheduled</small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div style="width: 20px; height: 20px; background: #198754; border-radius: 4px;" class="me-2"></div>
                        <small>Published</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div style="width: 20px; height: 20px; background: #dc3545; border-radius: 4px;" class="me-2"></div>
                        <small>Failed</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

<!-- Post Preview Modal -->
<div class="modal fade" id="postPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt me-2"></i>Post Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="postPreviewContent">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="postPreviewActions">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
.fc-event { cursor: pointer; }
.fc-event:hover { opacity: 0.8; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
let calendar;
const calendarEl = document.getElementById('calendar');
const postModal = new bootstrap.Modal(document.getElementById('postPreviewModal'));

// Initialize Calendar
calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    editable: true,
    droppable: true,
    events: getEventsUrl(),
    eventClick: function(info) {
        showPostPreview(info.event);
    },
    eventDrop: function(info) {
        updateEventDate(info.event, info.event.start, info.event.allDay);
    },
    eventDidMount: function(info) {
        // Add tooltip
        info.el.title = info.event.extendedProps.content;
    }
});

calendar.render();

// Get Events URL with filters
function getEventsUrl() {
    const params = new URLSearchParams();

    const socialAccount = document.getElementById('filterSocialAccount').value;
    if (socialAccount) params.append('social_account_id', socialAccount);

    const campaign = document.getElementById('filterCampaign').value;
    if (campaign) params.append('campaign_id', campaign);

    const statuses = Array.from(document.querySelectorAll('.status-filter:checked'))
        .map(cb => cb.value);
    statuses.forEach(status => params.append('status[]', status));

    return '{{ route('admin.social.calendar.events') }}?' + params.toString();
}

// Apply Filters
document.getElementById('applyFilters').addEventListener('click', function() {
    calendar.removeAllEvents();
    calendar.addEventSource(getEventsUrl());
    calendar.refetchEvents();
});

// Show Post Preview
function showPostPreview(event) {
    const props = event.extendedProps;

    const content = `
        <div class="row">
            <div class="col-md-8">
                <h6 class="text-muted">Content</h6>
                <p class="mb-3">${props.content}</p>

                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted">Network:</small><br>
                        <strong>${props.network}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Account:</small><br>
                        <strong>${props.account_name}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Status:</small><br>
                        <span class="badge bg-${getStatusColor(props.status)}">${props.status}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Type:</small><br>
                        <strong>${props.type}</strong>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted">Performance</h6>
                ${props.reach ? `
                <div class="mb-2">
                    <small>Reach:</small> <strong>${props.reach.toLocaleString()}</strong>
                </div>
                <div class="mb-2">
                    <small>Likes:</small> <strong>${props.likes_count}</strong>
                </div>
                <div class="mb-2">
                    <small>Comments:</small> <strong>${props.comments_count}</strong>
                </div>
                <div class="mb-2">
                    <small>Shares:</small> <strong>${props.shares_count}</strong>
                </div>
                ` : '<p class="text-muted">No data yet</p>'}
            </div>
        </div>
    `;

    document.getElementById('postPreviewContent').innerHTML = content;

    // Actions
    const actions = `
        <a href="{{ route('admin.social.publishing.edit', ':id') }}".replace(':id', props.post_id) class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        ${props.status !== 'published' ? `
        <button class="btn btn-success" onclick="quickAction(${props.post_id}, 'publish_now')">
            <i class="fas fa-paper-plane"></i> Publish Now
        </button>
        ` : ''}
        <button class="btn btn-info" onclick="quickAction(${props.post_id}, 'duplicate')">
            <i class="fas fa-copy"></i> Duplicate
        </button>
        <button class="btn btn-danger" onclick="quickAction(${props.post_id}, 'delete')">
            <i class="fas fa-trash"></i> Delete
        </button>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
    `;

    document.getElementById('postPreviewActions').innerHTML = actions;
    postModal.show();
}

// Update Event Date (Drag & Drop)
async function updateEventDate(event, newDate, allDay) {
    try {
        const response = await fetch('{{ route('admin.social.calendar.update-event', ':id') }}'.replace(':id', event.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                start: newDate.toISOString(),
                all_day: allDay
            })
        });

        const data = await response.json();

        if (!data.success) {
            alert(data.message || 'Error al actualizar');
            calendar.refetchEvents();
        }
    } catch (error) {
        alert('Error: ' + error.message);
        calendar.refetchEvents();
    }
}

// Quick Actions
async function quickAction(postId, action) {
    if (action === 'delete' && !confirm('¿Eliminar este post?')) return;

    try {
        const response = await fetch('{{ route('admin.social.calendar.quick-action', ':id') }}'.replace(':id', postId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ action })
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            postModal.hide();

            if (action === 'duplicate' && data.redirect) {
                window.location.href = data.redirect;
            } else {
                calendar.refetchEvents();
            }
        } else {
            alert(data.message || 'Error');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Helper Functions
function getStatusColor(status) {
    const colors = {
        'draft': 'secondary',
        'scheduled': 'primary',
        'published': 'success',
        'failed': 'danger'
    };
    return colors[status] || 'secondary';
}
</script>
@endpush
