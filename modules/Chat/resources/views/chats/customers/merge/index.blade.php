@extends('layouts.theme')

@section('title', 'Combinar Clientes')

@section('content')

@include('core::components.card', ['title' => 'Combinar Clientes', 'icon' => 'fa-users'])

<div class="widget-content searchable-container list">

    @include('core::components.alerts')

    <!-- Main Card -->
    <div class="card">
        <!-- Header Section -->
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Detectar y combinar duplicados</h5>
                    <p class="small mb-0 text-muted">Encuentra contactos duplicados y fusiónalos para mantener tu base de datos limpia</p>
                </div>
                <a href="{{ route('chat.customers.index') }}" class="btn btn-light">
                    <i class="fa fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>

        <!-- Search Criteria Card -->
        <div class="card-body border-bottom">
            <h6 class="mb-3 fw-bold">
                <i class="fa fa-search me-1"></i> Criterios de búsqueda
            </h6>
            <p class="text-muted small">Selecciona los criterios para detectar contactos duplicados automáticamente</p>

            <form id="find-duplicates-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="check-email" checked>
                            <label class="form-check-label" for="check-email">
                                <i class="fa fa-envelope text-primary me-1"></i> Mismo correo electrónico
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="check-phone" checked>
                            <label class="form-check-label" for="check-phone">
                                <i class="fa fa-phone text-success me-1"></i> Mismo número de teléfono
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="check-name">
                            <label class="form-check-label" for="check-name">
                                <i class="fa fa-user text-warning me-1"></i> Nombres similares
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search me-1"></i> Buscar duplicados
                        </button>
                        <span id="loading-spinner" class="ms-2" style="display: none;">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span class="ms-1">Buscando...</span>
                        </span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Container -->
        <div id="results-container" style="display: none;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-list me-1"></i> Grupos de duplicados encontrados
                    </h6>
                    <span id="groups-count-badge" class="badge bg-primary">0 grupos</span>
                </div>
                <div id="duplicate-groups"></div>
            </div>
        </div>

        <!-- No Results Message -->
        <div id="no-results" class="card-body text-center py-5" style="display: none;">
            <div class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                <i class="fa fa-check-circle fs-1 text-success"></i>
            </div>
            <h5 class="mb-2">¡Base de datos limpia!</h5>
            <p class="text-muted mb-0">No se encontraron contactos duplicados según los criterios seleccionados.</p>
        </div>
    </div>

</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-eye me-1"></i> Vista previa de la combinación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancelar
                </button>
                <button type="button" id="confirm-merge-btn" class="btn btn-danger">
                    <i class="fa fa-check me-1"></i> Confirmar combinación
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
            skip_email: !$('#check-email').is(':checked') ? 1 : 0,
            skip_phone: !$('#check-phone').is(':checked') ? 1 : 0,
            include_name_similarity: $('#check-name').is(':checked') ? 1 : 0
        };

        $('#loading-spinner').show();
        $('#results-container').hide();
        $('#no-results').hide();

        $.ajax({
            url: '{{ route('chat.customers.merge.find-duplicates') }}',
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
                        $('#groups-count-badge').text(duplicateGroups.length + ' grupos');
                    }
                }
            },
            error: function(xhr) {
                $('#loading-spinner').hide();
                var message = xhr.responseJSON?.message || 'Error al buscar duplicados';
                toastr.error(message);
            }
        });
    }

    // Display duplicate groups
    function displayDuplicateGroups(groups) {
        var html = '';

        $.each(groups, function(index, group) {
            html += '<div class="card mb-3 border-warning">';
            html += '<div class="card-header bg-warning-subtle">';
            html += '<div class="d-flex justify-content-between align-items-center">';
            html += '<h6 class="mb-0">';
            html += '<i class="fa fa-exclamation-triangle text-warning me-1"></i> ';
            html += 'Grupo #' + (index + 1) + ' ';
            html += '<span class="badge bg-secondary ms-1">' + group.type + '</span> ';
            html += '<span class="text-muted small">(' + group.value + ')</span>';
            html += '</h6>';
            html += '<span class="badge bg-primary">' + group.contacts.length + ' contactos</span>';
            html += '</div>';
            html += '</div>';
            html += '<div class="card-body">';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-hover align-middle mb-0">';
            html += '<thead class="table-light">';
            html += '<tr>';
            html += '<th width="80">Principal</th>';
            html += '<th>Nombre</th>';
            html += '<th>Correo</th>';
            html += '<th>Teléfono</th>';
            html += '<th class="text-center">Conversaciones</th>';
            html += '<th class="text-center">Etiquetas</th>';
            html += '<th>Última actividad</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';

            $.each(group.contacts, function(contactIndex, contact) {
                html += '<tr>';
                html += '<td>';
                html += '<div class="form-check">';
                html += '<input type="radio" name="primary_' + index + '" ';
                html += 'id="primary_' + index + '_' + contactIndex + '" ';
                html += 'value="' + contact.id + '" ';
                html += 'data-group-index="' + index + '" ';
                html += 'class="form-check-input primary-contact-radio"';
                if (contactIndex === 0) html += ' checked';
                html += '>';
                html += '<label class="form-check-label" for="primary_' + index + '_' + contactIndex + '"></label>';
                html += '</div>';
                html += '</td>';
                html += '<td><strong>' + contact.name + '</strong></td>';
                html += '<td>' + (contact.email || '<em class="text-muted">Sin email</em>') + '</td>';
                html += '<td>' + (contact.phone_number || '<em class="text-muted">Sin teléfono</em>') + '</td>';
                html += '<td class="text-center"><span class="badge bg-info-subtle text-info">' + contact.conversations_count + '</span></td>';
                html += '<td class="text-center"><span class="badge bg-info-subtle text-info">' + contact.labels_count + '</span></td>';
                html += '<td><small class="text-muted">' + (contact.last_activity_at || 'Nunca') + '</small></td>';
                html += '</tr>';
            });

            html += '</tbody>';
            html += '</table>';
            html += '</div>';
            html += '<div class="text-end mt-3">';
            html += '<button class="btn btn-primary preview-merge-btn" data-group-index="' + index + '">';
            html += '<i class="fa fa-eye me-1"></i> Vista previa y combinar';
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
            toastr.warning('Por favor selecciona un contacto principal');
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
            url: '{{ route('chat.customers.merge.preview') }}',
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
                var message = xhr.responseJSON?.message || 'Error al previsualizar la combinación';
                toastr.error(message);
            }
        });
    }

    // Display preview
    function displayPreview(preview) {
        const $container = $('#preview-content').empty();

        // Warning alert
        const $warning = $('<div>').addClass('alert alert-warning border-0 bg-warning-subtle');
        $warning.append($('<div>').addClass('d-flex align-items-start gap-2')
            .append($('<i>').addClass('fa fa-exclamation-triangle fs-5'))
            .append($('<div>')
                .append($('<strong>').addClass('d-block mb-1').text('Advertencia:'))
                .append($('<small>').text('Esta acción no se puede deshacer. Los contactos duplicados serán eliminados permanentemente después de la combinación.'))
            )
        );
        $container.append($warning);

        // Primary contact
        $container.append($('<h6>').addClass('mt-4 mb-2 fw-semibold').text('Contacto principal (se mantendrá):'));
        const $primaryCard = $('<div>').addClass('card border-success mb-3');
        const $primaryBody = $('<div>').addClass('card-body');
        $primaryBody.append($('<p>').addClass('mb-1').append($('<strong>').text('Nombre: ')).append(document.createTextNode(preview.primary.name)));
        $primaryBody.append($('<p>').addClass('mb-1').append($('<strong>').text('Email: ')).append(document.createTextNode(preview.primary.email || 'N/A')));
        $primaryBody.append($('<p>').addClass('mb-1').append($('<strong>').text('Teléfono: ')).append(document.createTextNode(preview.primary.phone || 'N/A')));
        $primaryBody.append($('<p>').addClass('mb-1').append($('<strong>').text('Conversaciones: ')).append(document.createTextNode(preview.primary.conversations_count)));
        $primaryBody.append($('<p>').addClass('mb-0').append($('<strong>').text('Etiquetas: ')).append(document.createTextNode(preview.primary.labels_count)));
        $primaryCard.append($primaryBody);
        $container.append($primaryCard);

        // Duplicates
        $container.append($('<h6>').addClass('fw-semibold mb-2').text('Contactos a combinar (serán eliminados):'));
        $.each(preview.duplicates, function(i, dup) {
            const $dupCard = $('<div>').addClass('card mb-2 border-danger');
            const $dupBody = $('<div>').addClass('card-body py-2');
            const $small = $('<small>');
            $small.append($('<strong>').text(dup.name));
            $small.append(document.createTextNode(' - '));
            $small.append(document.createTextNode(dup.email || 'Sin email'));
            $small.append(document.createTextNode(' - '));
            $small.append(document.createTextNode(dup.phone || 'Sin teléfono'));
            $small.append(document.createTextNode(' - '));
            $small.append(document.createTextNode(dup.conversations_count + ' conversaciones, '));
            $small.append(document.createTextNode(dup.labels_count + ' etiquetas'));
            $dupBody.append($small);
            $dupCard.append($dupBody);
            $container.append($dupCard);
        });

        // After merge
        $container.append($('<h6>').addClass('mt-4 mb-2 fw-semibold').text('Resultado después de combinar:'));
        const $list = $('<ul>').addClass('list-group list-group-flush');
        const $convItem = $('<li>').addClass('list-group-item');
        $convItem.append(document.createTextNode('Total de conversaciones: '));
        $convItem.append($('<strong>').addClass('text-primary').text(preview.after_merge.total_conversations));
        $list.append($convItem);

        const $labelsItem = $('<li>').addClass('list-group-item');
        $labelsItem.append(document.createTextNode('Total de etiquetas: '));
        $labelsItem.append($('<strong>').addClass('text-primary').text(preview.after_merge.total_labels));
        $list.append($labelsItem);

        if (preview.after_merge.fields_to_update && preview.after_merge.fields_to_update.length > 0) {
            const $fieldsItem = $('<li>').addClass('list-group-item');
            $fieldsItem.append(document.createTextNode('Campos a actualizar: '));
            $fieldsItem.append($('<strong>').text(preview.after_merge.fields_to_update.join(', ')));
            $list.append($fieldsItem);
        }

        $container.append($list);
    }

    // Confirm merge button
    $('#confirm-merge-btn').on('click', function() {
        if (!currentMergeData) {
            toastr.error('No hay datos de combinación disponibles');
            return;
        }

        executeMerge(currentMergeData);
    });

    // Execute merge via AJAX
    function executeMerge(mergeData) {
        $('#confirm-merge-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Combinando...');

        $.ajax({
            url: '{{ route('chat.customers.merge.execute') }}',
            method: 'POST',
            data: mergeData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#previewModal').modal('hide');
                    toastr.success(response.message || 'Contactos combinados correctamente');

                    // Refresh duplicate search
                    setTimeout(function() {
                        findDuplicates();
                    }, 1000);
                }
                $('#confirm-merge-btn').prop('disabled', false).html('<i class="fa fa-check me-1"></i> Confirmar combinación');
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Error al combinar contactos';
                toastr.error(message);
                $('#confirm-merge-btn').prop('disabled', false).html('<i class="fa fa-check me-1"></i> Confirmar combinación');
            }
        });
    }

    // Reset modal state when closed
    $('#previewModal').on('hidden.bs.modal', function() {
        currentMergeData = null;
        $('#preview-content').html('');
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush

@endsection
