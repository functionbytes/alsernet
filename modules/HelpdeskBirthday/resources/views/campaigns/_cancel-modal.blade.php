{{-- Confirmación de cancelar la campaña. Compartida por las pantallas que
     pintan la cabecera, que es donde vive el botón. --}}
@can('helpdeskbirthday.manage')
    @if($campaign->canBeCancelled())
        <div class="modal fade" id="bd-cancel-modal" tabindex="-1" aria-labelledby="bd-cancel-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bd-cancel-title">Cancelar la campaña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            Se descartarán los {{ $campaign->pendingCount() }} envíos que quedan pendientes.
                            Los correos ya enviados no se pueden retirar.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <form method="POST" action="{{ route('helpdeskbirthday.campaigns.cancel', $campaign) }}" class="w-100">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 mb-2">Cancelar la campaña</button>
                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Volver</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endcan
