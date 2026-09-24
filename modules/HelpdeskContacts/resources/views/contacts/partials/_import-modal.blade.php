{{-- Modal: importar contactos desde CSV.
     Backend real en ContactsController::importProcess() — detecta columnas
     por alias fijos (name/nombre, email/correo, phone/telefono,
     whatsapp_phone/whatsapp) contra la cabecera normalizada, exige al menos
     nombre o email por fila y actualiza por email los existentes salvo que se
     desmarque "Actualizar los contactos que ya existan" (update_existing=0).
     Las filas rechazadas se descargan después (contacts.import.rejected). --}}
<div class="modal fade ct-mdl" id="contact-import-modal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="ct-modal-icon me-2"><i class="fas fa-file-csv"></i></span>
                <div class="flex-grow-1">
                    <div class="ct-modal-eyebrow">Contactos · Importar</div>
                    <h5 class="modal-title" id="importModalLabel">Importar desde CSV</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="contact-import-form" action="{{ route('contacts.import.process') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body d-flex flex-column gap-2">
                    {{-- Zona de fichero (mockup pieza 08): el <input type="file"> va
                         oculto dentro de la etiqueta; al elegir, muestra nombre, filas y tamaño. --}}
                    <label class="ct-dropzone" for="contact-import-file" id="contact-import-drop">
                        <input type="file" class="d-none" id="contact-import-file" name="file" accept=".csv,.txt" required>
                        <span class="t" id="contact-import-file-name">Elige un fichero CSV</span>
                        <span class="s" id="contact-import-file-info">nombre, email, teléfono · hasta 5 MB</span>
                        <span class="a" id="contact-import-file-action">Seleccionar fichero</span>
                    </label>

                    <div id="contact-import-columns" class="ct-preview-card d-none">
                        <div class="ct-preview-card-hd">Columnas detectadas</div>
                        <div id="contact-import-columns-list" class="ct-import-columns-list"></div>
                    </div>

                    <input type="hidden" name="update_existing" value="0">
                    <label class="ct-fcheck">
                        <input type="checkbox" name="update_existing" value="1" checked>
                        <span class="ct-fcheck-grow">Actualizar los contactos que ya existan por email</span>
                    </label>

                    <div class="psc-note psc-note--warn" id="contact-import-note">
                        <i class="fas fa-circle-info mt-1"></i>
                        <span id="contact-import-note-text">Las filas sin nombre ni email, o con un email inválido, se omitirán. Al terminar se muestra el resumen con las filas rechazadas descargables.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="psc-btn psc-btn--primary" id="contact-import-submit" disabled>Selecciona un archivo</button>
                    <button type="button" class="psc-btn psc-btn--outline" id="contact-import-template-btn">Descargar plantilla de ejemplo</button>
                    <button type="button" class="psc-btn psc-btn--outline" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    @php
        $importModalJsMtime = @filemtime(public_path('modules/contacts/js/import-modal.js'));
    @endphp
    <script src="{{ asset('modules/contacts/js/import-modal.js') }}?v={{ $importModalJsMtime }}"></script>
@endpush
