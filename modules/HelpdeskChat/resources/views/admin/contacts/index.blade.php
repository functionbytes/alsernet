@extends('layouts.admin')

@section('title', 'Contacts')

@section('content')

    @include('helpdeskchat::includes.card', ['title' => 'Contacts', 'icon' => 'fa-users'])

    <div class="widget-content">
        @include('helpdeskchat::components.alerts')

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <p class="text-muted mb-0">
                <i class="fa fa-info-circle me-1"></i>
                Manage your customer contacts
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fa fa-funnel me-2"></i>Advanced Filters
                    </button>
                    <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                        <span class="visually-hidden">Saved Filters</span>
                    </button>
                    <ul class="dropdown-menu" id="savedFiltersDropdown">
                        <li><h6 class="dropdown-header">Saved Filters</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="loadSavedFilters">
                            <i class="fa fa-arrow-clockwise me-1"></i> Load Filters...
                        </a></li>
                    </ul>
                </div>
                <a href="{{ route('admin.contacts.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus-circle me-2"></i>Add Contact
                </a>
                <a href="{{ route('admin.contacts.import-form') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-upload me-2"></i>Import
                </a>
                <a href="{{ route('admin.contacts.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fa fa-download me-2"></i>Export
                </a>
                <a href="{{ route('admin.contacts.merge.index') }}" class="btn btn-outline-warning">
                    <i class="fa fa-shuffle me-2"></i>Find Duplicates
                </a>
            </div>
        </div>

        <!-- Search -->
        <div class="card border mb-3">
            <div class="card-body">
            <form method="GET" action="{{ route('admin.contacts.index') }}" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="sort_by" class="form-select">
                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Date Created</option>
                        <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                        <option value="email" {{ request('sort_by') == 'email' ? 'selected' : '' }}>Email</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort_order" class="form-select">
                        <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Descending</option>
                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="card">
        <div class="table-responsive">
            <form id="merge-form" method="POST" action="{{ route('admin.contacts.merge-form') }}">
                @csrf
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px">
                                <input type="checkbox" id="select-all" class="form-check-input">
                            </th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Conversations</th>
                            <th>Created</th>
                            <th style="width: 150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $contact)
                            <tr>
                                <td>
                                    <input type="checkbox" name="contacts[]" value="{{ $contact->id }}" class="form-check-input contact-checkbox">
                                </td>
                                <td>
                                    <a href="{{ route('admin.contacts.show', $contact) }}" class="text-decoration-none">
                                        <strong>{{ $contact->name }}</strong>
                                    </a>
                                </td>
                                <td>{{ $contact->email ?? '-' }}</td>
                                <td>{{ $contact->phone_number ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $contact->conversations->count() }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $contact->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.contacts.edit', $contact) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fa fa-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No contacts found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>
        @if($contacts->hasPages())
            <div class="card-footer">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>

    <!-- Bulk Actions Bar (shown when contacts selected) -->
    <div id="bulk-actions-bar" class="position-fixed bottom-0 start-50 translate-middle-x bg-white shadow-lg border rounded-3 p-3 mb-3" style="display: none; z-index: 1050; min-width: 600px;">
        <div class="d-flex align-items-center justify-content-between">
            <div class="me-3">
                <strong id="selected-count">0</strong> contacts selected
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showAddLabelsModal()">
                    <i class="fa fa-tags"></i> Add Labels
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showRemoveLabelsModal()">
                    <i class="fa fa-tag"></i> Remove Labels
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportSelected()">
                    <i class="fa fa-download"></i> Export
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="document.getElementById('merge-form').submit()">
                    <i class="fa fa-shuffle"></i> Merge
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSelected()">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-close" onclick="clearSelection()"></button>
            </div>
        </div>
    </div>

<!-- Advanced Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Advanced Contact Filters</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="filter-rules">
                    <!-- Filter rules will be added here dynamically -->
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addFilterRule()">
                    <i class="fa fa-plus"></i> Add Filter
                </button>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <input type="text" id="filter-name" class="form-control form-control-sm" placeholder="Filter name (optional)" style="width: 200px;">
                </div>
                <button type="button" class="btn btn-outline-secondary" onclick="saveFilter()">
                    <i class="fa fa-save"></i> Save Filter
                </button>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">
                    <i class="fa fa-check-lg"></i> Apply Filters
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Labels Modal -->
<div class="modal fade" id="addLabelsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Labels to Selected Contacts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select id="labels-to-add" class="form-select" multiple size="8">
                    <!-- Labels will be loaded dynamically -->
                </select>
                <small class="text-muted">Hold Ctrl/Cmd to select multiple labels</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmAddLabels()">Add Labels</button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Labels Modal -->
<div class="modal fade" id="removeLabelsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Labels from Selected Contacts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select id="labels-to-remove" class="form-select" multiple size="8">
                    <!-- Labels will be loaded dynamically -->
                </select>
                <small class="text-muted">Hold Ctrl/Cmd to select multiple labels</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmRemoveLabels()">Remove Labels</button>
            </div>
        </div>
    </div>
</div>

<script>
// Bulk Actions
let filterMetadata = {};

$(document).ready(function() {
    const $selectAll = $('#select-all');
    const $checkboxes = $('.contact-checkbox');
    const $bulkActionsBar = $('#bulk-actions-bar');

    $selectAll.on('change', function() {
        $checkboxes.prop('checked', this.checked);
        updateBulkActions();
    });

    $checkboxes.on('change', updateBulkActions);

    function updateBulkActions() {
        const checkedCount = $('.contact-checkbox:checked').length;
        $('#selected-count').text(checkedCount);
        $bulkActionsBar.toggle(checkedCount > 0);
    }

    // Load filter metadata
    loadFilterMetadata();
    // Load saved filters
    loadSavedFilters();
    // Load labels for bulk actions
    loadLabels();
});

function getSelectedContactIds() {
    return $('.contact-checkbox:checked').map(function() {
        return this.value;
    }).get();
}

function clearSelection() {
    $('.contact-checkbox').prop('checked', false);
    $('#select-all').prop('checked', false);
    $('#bulk-actions-bar').hide();
}

// Bulk Actions
function showAddLabelsModal() {
    new bootstrap.Modal(document.getElementById('addLabelsModal')).show();
}

function showRemoveLabelsModal() {
    new bootstrap.Modal(document.getElementById('removeLabelsModal')).show();
}

function confirmAddLabels() {
    const selectedLabels = $('#labels-to-add').val() || [];
    if (selectedLabels.length === 0) {
        alert('Please select at least one label');
        return;
    }

    const contactIds = getSelectedContactIds();
    $.ajax({
        url: '{{ route("admin.contacts.bulk.add-labels") }}',
        type: 'POST',
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: JSON.stringify({
            contact_ids: contactIds,
            label_ids: selectedLabels
        }),
        success: function(data) {
            bootstrap.Modal.getInstance(document.getElementById('addLabelsModal')).hide();
            alert(data.message);
            clearSelection();
        },
        error: function() {
            alert('Error adding labels');
        }
    });
}

function confirmRemoveLabels() {
    const selectedLabels = $('#labels-to-remove').val() || [];
    if (selectedLabels.length === 0) {
        alert('Please select at least one label');
        return;
    }

    const contactIds = getSelectedContactIds();
    $.ajax({
        url: '{{ route("admin.contacts.bulk.remove-labels") }}',
        type: 'POST',
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: JSON.stringify({
            contact_ids: contactIds,
            label_ids: selectedLabels
        }),
        success: function(data) {
            bootstrap.Modal.getInstance(document.getElementById('removeLabelsModal')).hide();
            alert(data.message);
            clearSelection();
        },
        error: function() {
            alert('Error removing labels');
        }
    });
}

function deleteSelected() {
    const contactIds = getSelectedContactIds();
    if (!confirm(`Are you sure you want to delete ${contactIds.length} contacts? This cannot be undone.`)) {
        return;
    }

    $.ajax({
        url: '{{ route("admin.contacts.bulk.delete") }}',
        type: 'POST',
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: JSON.stringify({ contact_ids: contactIds }),
        success: function(data) {
            alert(data.message);
            window.location.reload();
        },
        error: function() {
            alert('Error deleting contacts');
        }
    });
}

function exportSelected() {
    const contactIds = getSelectedContactIds();
    $.ajax({
        url: '{{ route("admin.contacts.bulk.export") }}',
        type: 'POST',
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: JSON.stringify({ contact_ids: contactIds }),
        success: function(data) {
            alert(data.message);
            window.open(data.download_url, '_blank');
            clearSelection();
        },
        error: function() {
            alert('Error exporting contacts');
        }
    });
}

// Advanced Filters
let filterRuleCount = 0;

function loadFilterMetadata() {
    $.ajax({
        url: '{{ route("admin.contacts.filters.metadata") }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            filterMetadata = data;
        }
    });
}

function loadSavedFilters() {
    $.ajax({
        url: '{{ route("admin.contacts.filters.index") }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const $dropdown = $('#savedFiltersDropdown');
            $dropdown.find('.saved-filter-item').remove();

            if (data.filters && data.filters.length > 0) {
                data.filters.forEach(function(filter) {
                    const $li = $('<li class="saved-filter-item"></li>');
                    $li.html(`<a class="dropdown-item" href="#" onclick="applySavedFilter(${filter.id}, event)">
                        <i class="fa fa-filter"></i> ${filter.name}
                    </a>`);
                    $dropdown.append($li);
                });
            }
        }
    });
}

function applySavedFilter(filterId, event) {
    event.preventDefault();
    $.ajax({
        url: `{{ url('admin/contacts/filters') }}/${filterId}/apply`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Reload page with filter applied
            window.location.href = `{{ route('admin.contacts.index') }}?filter=${filterId}`;
        }
    });
}

function addFilterRule() {
    const $ruleDiv = $('<div class="row mb-2 filter-rule"></div>')
        .attr('id', `rule-${filterRuleCount}`)
        .html(`
            <div class="col-md-4">
                <select class="form-select" onchange="updateOperators(${filterRuleCount})">
                    <option value="">Select Field...</option>
                    ${filterMetadata.fields ? filterMetadata.fields.standard.map(f =>
                        `<option value="${f.key}" data-type="${f.type}">${f.label}</option>`
                    ).join('') : ''}
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select operator-select" id="operator-${filterRuleCount}">
                    <option value="">Select Operator...</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control value-input" placeholder="Value">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFilterRule(${filterRuleCount})">
                    <i class="fa fa-x"></i>
                </button>
            </div>
        `);
    $('#filter-rules').append($ruleDiv);
    filterRuleCount++;
}

function removeFilterRule(ruleId) {
    $(`#rule-${ruleId}`).remove();
}

function updateOperators(ruleId) {
    const $ruleDiv = $(`#rule-${ruleId}`);
    const $fieldSelect = $ruleDiv.find('select').first();
    const fieldType = $fieldSelect.find('option:selected').data('type') || 'text';

    const $operatorSelect = $(`#operator-${ruleId}`);
    $operatorSelect.empty();

    if (filterMetadata.operators) {
        const operators = filterMetadata.operators[fieldType] || [];
        operators.forEach(function(op) {
            $operatorSelect.append(`<option value="${op.key}">${op.label}</option>`);
        });
    }
}

function applyFilters() {
    const rules = $('.filter-rule').map(function() {
        const $ruleDiv = $(this);
        const field = $ruleDiv.find('select').first().val();
        const operator = $ruleDiv.find('.operator-select').val();
        const value = $ruleDiv.find('.value-input').val();
        return { field, operator, value };
    }).get().filter(r => r.field && r.operator);

    if (rules.length === 0) {
        alert('Please add at least one filter rule');
        return;
    }

    // Apply filters via AJAX and reload page with results
    console.log('Applying filters:', rules);
    bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
}

function saveFilter() {
    const name = $('#filter-name').val();
    if (!name) {
        alert('Please enter a filter name');
        return;
    }

    const rules = $('.filter-rule').map(function() {
        const $ruleDiv = $(this);
        const field = $ruleDiv.find('select').first().val();
        const operator = $ruleDiv.find('.operator-select').val();
        const value = $ruleDiv.find('.value-input').val();
        return { field, operator, value };
    }).get().filter(r => r.field && r.operator);

    $.ajax({
        url: '{{ route("admin.contacts.filters.store") }}',
        type: 'POST',
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        data: JSON.stringify({
            name: name,
            filters: rules,
            is_public: false
        }),
        success: function(data) {
            alert(data.message);
            $('#filter-name').val('');
            loadSavedFilters();
        },
        error: function() {
            alert('Error saving filter');
        }
    });
}

function loadLabels() {
    $.ajax({
        url: '{{ route("admin.labels.index") }}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const $addSelect = $('#labels-to-add');
            const $removeSelect = $('#labels-to-remove');

            const labels = data.data || data;
            labels.forEach(function(label) {
                const $option = $(`<option value="${label.id}" style="color: ${label.color}">${label.title}</option>`);
                $addSelect.append($option);
                $removeSelect.append($option);
            });
        },
        error: function(error) {
            console.error('Error loading labels:', error);
        }
    });
}
</script>
@endsection
