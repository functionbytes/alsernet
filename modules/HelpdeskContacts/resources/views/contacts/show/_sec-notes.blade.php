<div class="ctf-sec ctf-col-section ctf-sec-notes">
    <div class="ctf-section-title">
        <span class="t">Notas internas</span>
        @can('contacts.update')
            <button type="button" class="ctf-link-btn ms-auto" data-ctf-note-add>Añadir</button>
        @endcan
    </div>
    @can('contacts.update')
        <form id="ctf-note-form" class="ctf-note-form d-none" data-url="{{ route('contacts.notes.store', $customer) }}">
            <textarea class="ct-finput" name="body" rows="2" maxlength="2000" placeholder="Escribe una nota para el equipo…" required></textarea>
            <div class="ctf-note-form-actions">
                <button type="submit" class="psc-btn psc-btn--primary">Guardar nota</button>
                <button type="button" class="psc-btn psc-btn--outline" data-ctf-note-cancel>Cancelar</button>
            </div>
        </form>
    @endcan
    <div class="ctf-notes-list" id="ctf-notes-list"
         data-destroy-url="{{ url('panel/helpdesk/contacts/'.$customer->id.'/notes') }}">
        @foreach($notes as $note)
            <div class="ctf-note-card" data-note-id="{{ $note->id }}">
                <div class="body">{{ $note->body }}</div>
                <div class="meta">
                    <span>{{ $note->author?->full_name ?: 'Agente eliminado' }} · {{ $note->created_at->translatedFormat('d M') }}</span>
                    @if((int) $note->user_id === (int) auth()->id())
                        <button type="button" class="ctf-note-del" data-ctf-note-delete="{{ $note->id }}">Borrar</button>
                    @endif
                </div>
            </div>
        @endforeach
        @if($customer->internal_notes)
            {{-- Nota heredada de la columna única internal_notes (se edita desde "Editar"). --}}
            <div class="ctf-note-card is-legacy">
                <div class="body">{{ $customer->internal_notes }}</div>
                <div class="meta"><span>Nota de ficha · se edita desde Editar</span></div>
            </div>
        @endif
        <div class="ctf-note-empty{{ $notes->isEmpty() && ! $customer->internal_notes ? '' : ' d-none' }}" id="ctf-notes-empty">Sin notas internas registradas.</div>
    </div>
</div>
