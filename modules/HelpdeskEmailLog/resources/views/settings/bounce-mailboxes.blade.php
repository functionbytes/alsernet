@extends('layouts.theme')

@section('title', 'Log de emails — Buzones de rebote')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Buzones de rebote'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header p-4 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1 fw-bold">Buzones de rebote</h5>
                        <p class="small mb-0 text-muted">
                            Buzones IMAP que <code>email-logs:process-bounces</code> revisa cada 10 minutos en busca de
                            rebotes (DSN) y quejas de spam. Sin ningún buzón habilitado, no se detecta ningún rebote.
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bounce-mailbox-add-modal">
                        Añadir buzón
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Etiqueta</th>
                                    <th>Servidor</th>
                                    <th>Módulos</th>
                                    <th>Estado</th>
                                    <th>Última revisión</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mailboxes as $mailbox)
                                    <tr>
                                        <td class="fw-semibold">{{ $mailbox['label'] ?? '—' }}</td>
                                        <td class="small">
                                            {{ $mailbox['username'] ?? '' }}@{{ $mailbox['host'] ?? '' }}:{{ $mailbox['port'] ?? 993 }}
                                            <div class="text-muted">{{ $mailbox['folder'] ?? 'INBOX' }}</div>
                                        </td>
                                        <td>
                                            @forelse(($mailbox['module_scope'] ?? []) as $mod)
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $mod }}</span>
                                            @empty
                                                <span class="text-muted small">Todos (sin acotar)</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if($mailbox['enabled'] ?? false)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis">Inactivo</span>
                                            @endif
                                            @if(($mailbox['consecutive_failures'] ?? 0) >= 3)
                                                <span class="badge bg-danger-subtle text-danger" title="{{ $mailbox['last_error'] ?? '' }}">
                                                    {{ $mailbox['consecutive_failures'] }} fallos seguidos
                                                </span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            @if(!empty($mailbox['last_checked_at']))
                                                {{ \Illuminate\Support\Carbon::parse($mailbox['last_checked_at'])->diffForHumans() }}
                                            @else
                                                Nunca
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical" aria-hidden="true"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button" class="dropdown-item js-edit-mailbox"
                                                                data-mailbox="{{ json_encode($mailbox) }}"
                                                                data-update-url="{{ route('settings.helpdeskemaillog.bounce-mailboxes.update', $mailbox['id']) }}">
                                                            Editar
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('settings.helpdeskemaillog.bounce-mailboxes.destroy', $mailbox['id']) }}"
                                                              onsubmit="return confirm('¿Eliminar este buzón de rebote?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item">Eliminar</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Sin buzones de rebote configurados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: añadir --}}
    <div class="modal fade" id="bounce-mailbox-add-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('settings.helpdeskemaillog.bounce-mailboxes.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Añadir buzón de rebote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @include('helpdeskemaillog::settings.partials.bounce-mailbox-fields', ['prefix' => 'add'])
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Añadir buzón</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: editar (un solo modal, rellenado por JS con los datos de la fila) --}}
    <div class="modal fade" id="bounce-mailbox-edit-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="bounce-mailbox-edit-form" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Editar buzón de rebote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @include('helpdeskemaillog::settings.partials.bounce-mailbox-fields', ['prefix' => 'edit'])
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#bounce-mailbox-add-modal .form-select').select2({ width: '100%', dropdownParent: $('#bounce-mailbox-add-modal') });
    $('#bounce-mailbox-edit-modal .form-select').select2({ width: '100%', dropdownParent: $('#bounce-mailbox-edit-modal') });

    const editModalEl = document.getElementById('bounce-mailbox-edit-modal');
    const editModal = new bootstrap.Modal(editModalEl);
    const $editForm = $('#bounce-mailbox-edit-form');

    $(document).on('click', '.js-edit-mailbox', function () {
        const data = $(this).data('mailbox');
        const updateUrl = $(this).data('update-url');

        $editForm.attr('action', updateUrl);
        $editForm.find('[name="label"]').val(data.label || '');
        $editForm.find('[name="host"]').val(data.host || '');
        $editForm.find('[name="port"]').val(data.port || 993);
        $editForm.find('[name="encryption"]').val(data.encryption || 'ssl').trigger('change');
        $editForm.find('[name="username"]').val(data.username || '');
        $editForm.find('[name="password"]').val('');
        $editForm.find('[name="folder"]').val(data.folder || 'INBOX');
        $editForm.find('[name="enabled"]').val(data.enabled ? '1' : '0').trigger('change');

        const $scope = $editForm.find('[name="module_scope[]"]');
        $scope.val(data.module_scope || []).trigger('change');

        editModal.show();
    });
});
</script>
@endpush
