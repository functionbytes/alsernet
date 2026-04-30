{{-- Variables: $conversation --}}
<div class="modal fade" id="emailTranscriptModal" tabindex="-1" aria-labelledby="emailTranscriptModalLabel" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered"">
        <div class="modal-content">
            <form action="{{ route('chat.conversations.emailTranscript', $conversation->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="emailTranscriptModalLabel">Enviar transcripcion por email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Mensaje (opcional):</label>
                        <textarea class="form-control" id="message" name="message" rows="3" placeholder="Agrega un mensaje personalizado..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
