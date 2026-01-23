@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fa fa-people-fill"></i> Contact Merge</h2>
            <p class="text-muted">Find and merge duplicate contacts to keep your database clean</p>
        </div>
    </div>

    <!-- Search Criteria Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa fa-search"></i> Find Duplicates</h5>
        </div>
        <div class="card-body">
            <form id="find-duplicates-form">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Detection Criteria:</label>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check-email" checked>
                            <label class="form-check-label" for="check-email">
                                <i class="fa fa-envelope"></i> Match by Email
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check-phone" checked>
                            <label class="form-check-label" for="check-phone">
                                <i class="fa fa-telephone"></i> Match by Phone
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check-name">
                            <label class="form-check-label" for="check-name">
                                <i class="fa fa-person"></i> Match by Name Similarity
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i> Find Duplicates
                        </button>
                        <span id="loading-spinner" class="ms-2" style="display: none;">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span class="ms-1">Searching...</span>
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Card -->
    <div id="results-container" style="display: none;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list-ul"></i> Duplicate Groups</h5>
                <span id="groups-count-badge" class="badge bg-primary">0 groups</span>
            </div>
            <div class="card-body">
                <div id="duplicate-groups"></div>
            </div>
        </div>
    </div>

    <!-- No Results Message -->
    <div id="no-results" class="alert alert-success" style="display: none;">
        <i class="fa fa-check-circle"></i> No duplicate contacts found! Your contact database is clean.
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-eye"></i> Merge Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirm-merge-btn" class="btn btn-danger">
                    <i class="fa fa-check-circle"></i> Confirm Merge
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    var duplicateGroups = [];
    var currentMergeData = null;

    // Find duplicates form submission
    $('#find-duplicates-form').on('submit', function(e) {
        e.preventDefault();
        findDuplicates();
    });

    // Find duplicates via AJAX
    function findDuplicates() {
        var criteria = {
            skip_email: !$('#check-email').is(':checked'),
            skip_phone: !$('#check-phone').is(':checked'),
            include_name_similarity: $('#check-name').is(':checked')
        };

        $('#loading-spinner').show();
        $('#results-container').hide();
        $('#no-results').hide();

        $.ajax({
            url: '{{ route('admin.contacts.merge.find-duplicates') }}',
            method: 'POST',
            data: criteria,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#loading-spinner').hide();

                if (response.success) {
                    duplicateGroups = response.groups;

                    if (duplicateGroups.length === 0) {
                        $('#no-results').show();
                    } else {
                        displayDuplicateGroups(duplicateGroups);
                        $('#results-container').show();
                        $('#groups-count-badge').text(duplicateGroups.length + ' groups');
                    }
                }
            },
            error: function(xhr) {
                $('#loading-spinner').hide();
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Failed to find duplicates';
                alert('Error: ' + message);
            }
        });
    }

    // Display duplicate groups
    function displayDuplicateGroups(groups) {
        var html = '';

        $.each(groups, function(index, group) {
            html += '<div class="card mb-3 border-warning">';
            html += '<div class="card-header bg-warning bg-opacity-10">';
            html += '<h6 class="mb-0">';
            html += '<i class="fa fa-exclamation-triangle text-warning"></i> ';
            html += 'Duplicate Groups #' + (index + 1) + ' - ';
            html += '<span class="badge bg-secondary">' + group.type + '</span> ';
            html += '<span class="text-muted">' + group.value + '</span>';
            html += '</h6>';
            html += '</div>';
            html += '<div class="card-body">';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-hover">';
            html += '<thead>';
            html += '<tr>';
            html += '<th width="50">Primary</th>';
            html += '<th>Name</th>';
            html += '<th>Email</th>';
            html += '<th>Phone</th>';
            html += '<th>Conversations</th>';
            html += '<th>Labels</th>';
            html += '<th>Last Activity</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';

            $.each(group.contacts, function(contactIndex, contact) {
                html += '<tr>';
                html += '<td>';
                html += '<input type="radio" name="primary_' + index + '" ';
                html += 'value="' + contact.id + '" ';
                html += 'data-group-index="' + index + '" ';
                html += 'class="form-check-input primary-contact-radio"';
                if (contactIndex === 0) html += ' checked';
                html += '>';
                html += '</td>';
                html += '<td><strong>' + contact.name + '</strong></td>';
                html += '<td>' + (contact.email || '<em class="text-muted">N/A</em>') + '</td>';
                html += '<td>' + (contact.phone_number || '<em class="text-muted">N/A</em>') + '</td>';
                html += '<td><span class="badge bg-info">' + contact.conversations_count + '</span></td>';
                html += '<td><span class="badge bg-secondary">' + contact.labels_count + '</span></td>';
                html += '<td>' + (contact.last_activity_at || '<em class="text-muted">Never</em>') + '</td>';
                html += '</tr>';
            });

            html += '</tbody>';
            html += '</table>';
            html += '</div>';
            html += '<div class="text-end mt-2">';
            html += '<button class="btn btn-sm btn-primary preview-merge-btn" data-group-index="' + index + '">';
            html += '<i class="fa fa-eye"></i> Preview Merge';
            html += '</button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });

        $('#duplicate-groups').html(html);
    }

    // Preview merge button click
    $(document).on('click', '.preview-merge-btn', function() {
        var groupIndex = $(this).data('group-index');
        var group = duplicateGroups[groupIndex];
        var primaryContactId = $('input[name="primary_' + groupIndex + '"]:checked').val();

        if (!primaryContactId) {
            alert('Please select a primary contact');
            return;
        }

        var duplicateContactIds = [];
        $.each(group.contacts, function(i, contact) {
            if (contact.id != primaryContactId) {
                duplicateContactIds.push(contact.id);
            }
        });

        previewMerge(primaryContactId, duplicateContactIds);
    });

    // Preview merge via AJAX
    function previewMerge(primaryContactId, duplicateContactIds) {
        currentMergeData = {
            primary_contact_id: parseInt(primaryContactId),
            duplicate_contact_ids: duplicateContactIds.map(function(id) { return parseInt(id); })
        };

        $.ajax({
            url: '{{ route('admin.contacts.merge.preview') }}',
            method: 'POST',
            data: currentMergeData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    displayPreview(response.preview);
                    $('#previewModal').modal('show');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Failed to preview merge';
                alert('Error: ' + message);
            }
        });
    }

    // Display preview
    function displayPreview(preview) {
        var html = '';

        html += '<div class="alert alert-warning">';
        html += '<i class="fa fa-exclamation-triangle"></i> ';
        html += '<strong>Warning:</strong> This action cannot be undone. The duplicate contacts will be permanently deleted after merging.';
        html += '</div>';

        html += '<h6 class="mt-3">Primary Contact (will be kept):</h6>';
        html += '<div class="card mb-3">';
        html += '<div class="card-body">';
        html += '<p class="mb-1"><strong>Name:</strong> ' + preview.primary.name + '</p>';
        html += '<p class="mb-1"><strong>Email:</strong> ' + (preview.primary.email || 'N/A') + '</p>';
        html += '<p class="mb-1"><strong>Phone:</strong> ' + (preview.primary.phone || 'N/A') + '</p>';
        html += '<p class="mb-1"><strong>Conversations:</strong> ' + preview.primary.conversations_count + '</p>';
        html += '<p class="mb-0"><strong>Labels:</strong> ' + preview.primary.labels_count + '</p>';
        html += '</div>';
        html += '</div>';

        html += '<h6>Contacts to be merged (will be deleted):</h6>';
        $.each(preview.duplicates, function(i, dup) {
            html += '<div class="card mb-2 border-danger">';
            html += '<div class="card-body py-2">';
            html += '<small>';
            html += '<strong>' + dup.name + '</strong> - ';
            html += (dup.email || 'No email') + ' - ';
            html += (dup.phone || 'No phone') + ' - ';
            html += dup.conversations_count + ' conversation, ';
            html += dup.labels_count + ' labels';
            html += '</small>';
            html += '</div>';
            html += '</div>';
        });

        html += '<h6 class="mt-3">After Merge:</h6>';
        html += '<ul class="list-group">';
        html += '<li class="list-group-item">Total Conversations: <strong>' + preview.after_merge.total_conversations + '</strong></li>';
        html += '<li class="list-group-item">Total Labels: <strong>' + preview.after_merge.total_labels + '</strong></li>';

        if (preview.after_merge.fields_to_update.length > 0) {
            html += '<li class="list-group-item">Fields to be updated: <strong>' + preview.after_merge.fields_to_update.join(', ') + '</strong></li>';
        }

        html += '</ul>';

        $('#preview-content').html(html);
    }

    // Confirm merge button
    $('#confirm-merge-btn').on('click', function() {
        if (!currentMergeData) {
            alert('No merge data available');
            return;
        }

        executeMerge(currentMergeData);
    });

    // Execute merge via AJAX
    function executeMerge(mergeData) {
        $('#confirm-merge-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Merging...');

        $.ajax({
            url: '{{ route('admin.contacts.merge.execute') }}',
            method: 'POST',
            data: mergeData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#previewModal').modal('hide');
                    alert('Success: ' + response.message);

                    // Refresh duplicate search
                    findDuplicates();
                }
                $('#confirm-merge-btn').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Confirm Merge');
            },
            error: function(xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Failed to execute merge';
                alert('Error: ' + message);
                $('#confirm-merge-btn').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Confirm Merge');
            }
        });
    }

    // Reset modal state when closed
    $('#previewModal').on('hidden.bs.modal', function() {
        currentMergeData = null;
        $('#preview-content').html('');
    });
});
</script>
@endpush
@endsection
