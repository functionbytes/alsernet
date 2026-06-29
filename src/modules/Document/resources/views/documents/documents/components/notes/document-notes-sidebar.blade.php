<!-- Document Notes Sidebar (Col-3) -->
<div class="card">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Notas</h5>
        <p class="small mb-0 text-muted">Anotaciones y comentarios sobre el documento</p>
    </div>
    <div class="card-body">
        <!-- Notes List -->
        @if(!$document || $document->notes->isEmpty())
            <div class="alert bg-light-subtle py-3 px-3 mb-0" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fas fa-circle-info text-black me-2 mt-1" style="font-size: 0.9rem;"></i>
                    <div>
                        <small class="fw-semibold d-block">No hay notas aún.</small>
                        <small class="text-muted">No hay notas para este documento aún registradass.</small>
                    </div>
                </div>
            </div>
        @else
            <div class="notes-scroll scrollable mb-3">
                @foreach($document->notes->sortByDesc('created_at') as $note)
                    <div class="note-item border-bottom px-2 px-md-0 py-2" data-note-id="{{ $note->id }}">
                        <!-- Note Author & Date -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                            <div class="d-flex align-items-center gap-2 min-width-0">
                                <div class="avatar-initials flex-shrink-0"
                                     style="background-color: #f6f7f9;"
                                     data-bs-toggle="tooltip"
                                     data-bs-title="{{ $note->author?->full_name ?? 'Sistema' }}">
                                    {{ strtoupper(substr($note->author->firstname ?? '', 0, 1) . substr($note->author->lastname ?? '', 0, 1)) }}
                                </div>
                                <div class="min-width-0">
                                    <small class="fw-semibold d-block text-truncate">
                                        @if($note->author)
                                            {{ $note->author->firstname }} {{ substr($note->author->lastname ?? '', 0, 1) }}
                                            .
                                        @else
                                            Sistema
                                        @endif
                                    </small>
                                    <small class="text-muted d-block"
                                           style="font-size: 0.7rem;">{{ $note->created_at->format('d M Y H:i') }}</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                @if(auth()->check())
                                    <div class="note-actions-editable">
                                        @if(auth()->user()->canDocument('edit-notes'))
                                            <button class="btn-note-edit" data-note-id="{{ $note->id }}"
                                                    data-bs-toggle="tooltip" data-bs-title="Editar nota">
                                                <i class="fas fa-pen-to-square text-black fs-2"></i>
                                            </button>
                                        @endif

                                        @if(auth()->user()->canDocument('delete-notes'))
                                            <button class="btn-note-delete" data-note-id="{{ $note->id }}"
                                                    data-bs-toggle="tooltip" data-bs-title="Eliminar nota">
                                                <i class="fas fa-trash text-black fs-2"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Note Content -->
                        <div class="note-content mt-3 p-2">
                            <p class="text-dark mb-0 small text-truncate-3" data-full-text="{{ $note->content }}">
                                {{ $note->content }}
                            </p>
                        </div>

                        <!-- Edit Form (Hidden by default) -->
                        <div class="note-edit-form d-none mt-2" style="display: none;">
                            <textarea class="form-control form-control-sm note-edit-textarea" rows="2"
                                      style="resize: none; font-size: 0.85rem;">{{ $note->content }}</textarea>
                            <div class="gap-2 mt-2">
                                <button class="btn-note-save btn-sm btn btn-primary w-100 mb-1"
                                        data-note-id="{{ $note->id }}">
                                    Guardar
                                </button>
                                <button class="btn-note-cancel btn-sm btn btn-secondary  w-100">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="border-top my-3"></div>

        <!-- Add Note Form -->
        @if(auth()->user()->canDocument('add-notes'))
            <form id="addNoteForm">
                @csrf
                <div class="mb-2">
                    <textarea class="form-control form-control-sm" id="noteContent" name="content" rows="2"
                              placeholder="Escribe una nota..." required style="resize: none;"></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-1"></i> Agregar
                    </button>
                </div>
            </form>
        @else
            <div class="alert alert-info py-2 px-3 mb-0" role="alert">
                <small><i class="fas fa-info-circle me-2"></i>No tienes permiso para agregar notas</small>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            const documentUid = '{{ $document->uid }}';

            // Function to rebind event listeners after adding new notes
            function rebindNoteEventListeners() {
                // Edit Note
                $(document).off('click', '.btn-note-edit').on('click', '.btn-note-edit', editNoteHandler);

                // Delete Note
                $(document).off('click', '.btn-note-delete').on('click', '.btn-note-delete', deleteNoteHandler);

                // Save Note
                $(document).off('click', '.btn-note-save').on('click', '.btn-note-save', saveNoteHandler);

                // Cancel Note Edit
                $(document).off('click', '.btn-note-cancel').on('click', '.btn-note-cancel', cancelNoteHandler);
            }

            // Edit Note Handler
            function editNoteHandler(e) {
                e.preventDefault();
                const noteId = $(this).data('note-id');

                // Destroy all tooltips before editing
                destroyAllTooltips();

                const $noteItem = $(`[data-note-id="${noteId}"]`);
                const $noteContent = $noteItem.find('.note-content');
                const $editForm = $noteItem.find('.note-edit-form');

                $noteContent.hide();
                $editForm.removeClass('d-none').show();
                $editForm.find('.note-edit-textarea').focus();
            }

            // Cancel Edit Handler
            function cancelNoteHandler(e) {
                e.preventDefault();
                const $noteItem = $(this).closest('.note-item');
                const $noteContent = $noteItem.find('.note-content');
                const $editForm = $noteItem.find('.note-edit-form');

                $editForm.addClass('d-none').hide();
                $noteContent.show();
            }

            // Save Note Handler
            function saveNoteHandler(e) {
                e.preventDefault();
                const noteId = $(this).data('note-id');
                const $noteItem = $(`[data-note-id="${noteId}"]`);
                const $editForm = $noteItem.find('.note-edit-form');
                const content = $editForm.find('.note-edit-textarea').val();
                const $btn = $(this);

                if (!content.trim()) {
                    toastr.warning('La nota no puede estar vacía', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>');

                $.ajax({
                    url: `{{ route('api.documents.notes.update', ['uid' => 'UID_PLACEHOLDER', 'noteId' => 'NOTE_PLACEHOLDER']) }}`.replace('UID_PLACEHOLDER', documentUid).replace('NOTE_PLACEHOLDER', noteId),
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    data: JSON.stringify({content: content}),
                    contentType: 'application/json',
                    success: function (data) {
                        if (data.success) {
                            const $noteContent = $noteItem.find('.note-content');
                            $noteContent.find('p').text(content);
                            $editForm.addClass('d-none').hide();
                            $noteContent.show();

                            toastr.success('Nota actualizada correctamente', 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        } else {
                            toastr.error('Error: ' + (data.message || 'No se pudo actualizar la nota'), 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function (error) {
                        console.error('Error:', error);
                        toastr.error('Error al actualizar la nota', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html('Guardar');
                    }
                });
            }

            // Utility function to destroy all tooltips
            function destroyAllTooltips() {
                // Get all elements with tooltip instances
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
                    const tooltipInstance = bootstrap.Tooltip.getInstance(element);
                    if (tooltipInstance) {
                        tooltipInstance.dispose();
                    }
                });
                // Also remove any orphaned tooltip divs
                document.querySelectorAll('.tooltip').forEach(tooltip => {
                    tooltip.remove();
                });
            }

            // Delete Note Handler
            let pendingDeleteNoteId = null;

            function deleteNoteHandler(e) {
                e.preventDefault();
                const noteId = $(this).data('note-id');

                // Destroy all tooltips before showing modal
                destroyAllTooltips();

                // Store the note ID for deletion
                pendingDeleteNoteId = noteId;

                // Show modal
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteNoteModal'));
                deleteModal.show();
            }

            // Clean up tooltips when modal is hidden
            document.getElementById('deleteNoteModal').addEventListener('hidden.bs.modal', function() {
                destroyAllTooltips();
                pendingDeleteNoteId = null;
            });

            // Handle delete confirmation
            $('#confirmDeleteNoteBtn').on('click', function() {
                if (!pendingDeleteNoteId) return;

                const noteId = pendingDeleteNoteId;
                const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteNoteModal'));

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Eliminando...');

                $.ajax({
                    url: `{{ route('api.documents.notes.delete', ['uid' => 'UID_PLACEHOLDER', 'noteId' => 'NOTE_PLACEHOLDER']) }}`.replace('UID_PLACEHOLDER', documentUid).replace('NOTE_PLACEHOLDER', noteId),
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            deleteModal.hide();

                            // Destroy all tooltips before removing element
                            destroyAllTooltips();

                            // Remove the note item
                            $(`[data-note-id="${noteId}"]`).remove();

                            toastr.success('Nota eliminada correctamente', 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });

                            // Reload if no notes left
                            if ($('.note-item').length === 0) {
                                setTimeout(() => location.reload(), 500);
                            }
                        } else {
                            toastr.error('Error: ' + (data.message || 'No se pudo eliminar la nota'), 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function (error) {
                        console.error('Error:', error);
                        toastr.error('Error al eliminar la nota', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-trash me-2"></i>Eliminar');
                        pendingDeleteNoteId = null;
                    }
                });
            })

            // Initial binding on page load
            rebindNoteEventListeners();

            // Add New Note
            $('#addNoteForm').on('submit', function (e) {
                e.preventDefault();

                const content = $('#noteContent').val();

                if (!content.trim()) {
                    toastr.warning('La nota no puede estar vacía', 'Atención', {
                        closeButton: true,
                        progressBar: true,
                        positionClass: "toast-bottom-right"
                    });
                    return;
                }

                const $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

                $.ajax({
                    url: `{{ route('api.documents.notes.add', ['uid' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', documentUid),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    data: JSON.stringify({content: content}),
                    contentType: 'application/json',
                    success: function (data) {
                        if (data.success && data.note) {
                            // Add note to DOM without reloading
                            const $notesList = $('.notes-scroll');

                            // Safe handling of author data
                            const author = data.note.author || {};
                            const firstname = author.firstname || 'S';
                            const lastname = author.lastname || '';
                            const userInitials = firstname.charAt(0).toUpperCase() + lastname.charAt(0).toUpperCase();
                            const authorName = author.firstname ? `${author.firstname} ${lastname.charAt(0)}.` : 'Sistema';

                            const noteHTML = `
                            <div class="note-item border-bottom px-2 px-md-0 py-2" data-note-id="${data.note.id}">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <div class="d-flex align-items-center gap-2 min-width-0">
                                        <div class="avatar-initials flex-shrink-0" style="background-color: #f6f7f9;">
                                            ${userInitials}
                                        </div>
                                        <div class="min-width-0">
                                            <small class="fw-semibold d-block text-truncate">
                                                ${authorName}
                                            </small>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                ${new Date().toLocaleDateString('es-ES', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            })}
                                                ${new Date().toLocaleTimeString('es-ES', {
                                hour: '2-digit',
                                minute: '2-digit'
                            })}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        <div class="note-actions-editable">
                                            <button class="btn-note-edit" data-note-id="${data.note.id}" data-bs-toggle="tooltip" data-bs-title="Editar nota">
                                                <i class="fas fa-pen-to-square text-black fs-2"></i>
                                            </button>
                                            <button class="btn-note-delete" data-note-id="${data.note.id}" data-bs-toggle="tooltip" data-bs-title="Eliminar nota">
                                                <i class="fas fa-trash text-black fs-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="note-content mt-3">
                                    <p class="text-dark mb-0 small text-truncate-3" data-full-text="${data.note.content}">
                                        ${data.note.content}
                                    </p>
                                </div>
                                <div class="note-edit-form d-none mt-2" style="display: none;">
                                    <textarea class="form-control form-control-sm note-edit-textarea" rows="2" style="resize: none; font-size: 0.85rem;">${data.note.content}</textarea>
                                    <div class="gap-2 mt-2">
                                        <button class="btn-note-save btn-sm btn btn-success w-100" data-note-id="${data.note.id}">
                                            Guardar
                                        </button>
                                        <button class="btn-note-cancel btn-sm btn btn-secondary  w-100">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;

                            // If notes list doesn't exist, create it (first note)
                            if ($notesList.length === 0) {
                                const $noNotesAlert = $('.card-body [role="alert"]');
                                if ($noNotesAlert.length) {
                                    $noNotesAlert.replaceWith(`
                                    <div class="notes-scroll scrollable mb-3">
                                        ${noteHTML}
                                    </div>
                                `);
                                }
                            } else {
                                // Insert at the beginning of notes-scroll
                                $notesList.prepend(noteHTML);
                            }

                            // Rebind event listeners for new note
                            rebindNoteEventListeners();

                            // Clear form
                            $('#noteContent').val('');

                            toastr.success('Nota guardada correctamente', 'Éxito', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        } else {
                            toastr.error('Error: ' + (data.message || 'No se pudo guardar la nota'), 'Error', {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                        }
                    },
                    error: function (error) {
                        console.error('Error:', error);
                        toastr.error('Error al agregar la nota', 'Error', {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-bottom-right"
                        });
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i> Agregar');
                    }
                });
            });
        });
    </script>
@endpush

<!-- Modal de confirmación para eliminar nota -->
<div class="modal fade" id="deleteNoteModal" tabindex="-1" aria-labelledby="deleteNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="deleteNoteModalLabel">
                    Eliminar nota
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary w-100 mb-1" id="confirmDeleteNoteBtn">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .notes-scroll {
        overflow-y: auto;
        border-top: 1px solid #e9ecef;
        height: 250px;
    }

    @media (min-width: 576px) {
        .notes-scroll {
            height: 300px;
        }
    }

    @media (min-width: 768px) {
        .notes-scroll {
            height: 350px;
        }
    }

    @media (min-width: 992px) {
        .notes-scroll {
            height: max-content;
        }
    }

    .notes-scroll::-webkit-scrollbar {
        width: 5px;
    }

    .notes-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .notes-scroll::-webkit-scrollbar-thumb {
        background: #dee2e6;
        border-radius: 2px;
    }

    .notes-scroll::-webkit-scrollbar-thumb:hover {
        background: #adb5bd;
    }

    .note-item {
        transition: all 0.2s ease;
        border-color: #e9ecef !important;
    }

    .note-item:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6 !important;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .text-truncate-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .min-width-0 {
        min-width: 0;
    }

    .avatar-initials {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
        flex-shrink: 0;
    }

    .note-actions-editable {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .btn-note-edit,
    .btn-note-delete {
        background: none;
        border: none;
        padding: 0.25rem 0.35rem;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-note-edit,
    .btn-note-delete {
        opacity: 0.65;
        padding: 0.3rem 0.4rem;
    }

    .btn-note-edit:hover {
        opacity: 1;
        transform: scale(1.15);
    }

    .btn-note-delete:hover {
        opacity: 1;
        transform: scale(1.15);
    }

    .note-edit-form textarea {
        border-color: #0d6efd;
    }

    .note-edit-form .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    #addNoteForm .form-control {
        border-color: #dee2e6;
        font-size: 0.875rem;
    }

    #addNoteForm .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }

    #addNoteForm .form-label {
        color: #495057;
        font-size: 0.8rem;
    }

    /* Responsive Adjustments */
    @media (max-width: 575px) {
        .note-item {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .note-actions {
            gap: 0.15rem;
        }

        .btn-note-edit,
        .btn-note-delete {
            padding: 0.2rem 0.3rem;
            font-size: 0.8rem;
        }
    }
</style>
