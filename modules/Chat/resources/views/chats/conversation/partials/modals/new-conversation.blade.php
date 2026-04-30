{{-- Variables: $inboxes --}}
<div class="modal fade" id="newConversationModal" tabindex="-1" aria-labelledby="newConversationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" >
        <div class="modal-content">
            <form action="{{ route('chat.conversations.store') }}" method="POST" id="new-conversation-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="newConversationModalLabel">
                        Nueva Conversacion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Buscar contacto -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Para:</label>
                        <input type="hidden" name="customer_id" id="selected_contact_id" required>
                        <input type="text"
                               class="form-control"
                               id="contact_search"
                               placeholder="Buscar contacto por nombre, email o telefono"
                               autocomplete="off">

                        <div id="contact_search_results" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto;"></div>

                        <div id="selected_contact" style="display: none;" class="mt-2">
                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                <div class="flex-grow-1">
                                    <strong id="selected_contact_name"></strong><br>
                                    <small class="text-muted" id="selected_contact_email"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="remove_contact">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Seleccion de canal -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Canal:</label>
                        <select name="inbox" class="form-control select2" required>
                            <option value="">Seleccionar canal...</option>
                            @foreach($inboxes as $inbox)
                                <option value="{{ $inbox->id }}">{{ $inbox->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mensaje inicial -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mensaje inicial (opcional):</label>
                        <textarea name="initial_message"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Escribe tu mensaje..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Crear Conversacion
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
