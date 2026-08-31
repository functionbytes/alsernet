@extends('layouts.theme')

@section('title', 'Editar canal de correo: ' . $channel['name'])

@section('page_header')
    @include('core::components.card', ['title' => 'Editar canal de correo'])
@endsection

@section('content')

    @php
        $hasError = ! empty($channel['last_error']);
        $neverChecked = empty($channel['last_checked_at']);
    @endphp

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="channelForm" action="{{ route('manager.helpdesk.settings.email-channels.update', $channel['id']) }}" method="POST">
                    @csrf
                    {{-- PUT por spoofing: el PUT real via formulario/AJAX da 405 en este Docker --}}
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar: {{ $channel['name'] }}</h5>
                        <small class="text-muted">Modifica la conexion y el comportamiento de este canal</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- showTestButtons: false — al editar, "Probar conexion"
                             se pinta en la tarjeta "Estado del canal" de la
                             derecha, junto a Sincronizar y Eliminar. --}}
                        @include('helpdesktickets::managers.settings.email-channels._form', [
                            'channel' => $channel,
                            'showTestButtons' => false,
                        ])
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.email-channels.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Side panel --}}
        <div class="col-12 col-lg-4">

            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Estado del canal</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        @if($neverChecked)
                            <span class="badge bg-secondary-subtle text-secondary">Nunca sincronizado</span>
                        @elseif($hasError)
                            <span class="badge bg-warning-subtle text-warning">Error</span>
                        @else
                            <span class="badge bg-success-subtle text-success">OK</span>
                        @endif
                    </div>

                    <ul class="list-unstyled mb-3">
                        <li class="mb-2">
                            <span class="fw-semibold">Ultima revision:</span>
                            {{ $neverChecked ? 'Nunca' : \Illuminate\Support\Carbon::parse($channel['last_checked_at'])->format('d/m/Y H:i') }}
                        </li>
                        <li class="mb-2">
                            <span class="fw-semibold">Ultimo exito:</span>
                            {{ empty($channel['last_success_at']) ? 'Nunca' : \Illuminate\Support\Carbon::parse($channel['last_success_at'])->format('d/m/Y H:i') }}
                        </li>
                        <li class="mb-0">
                            <span class="fw-semibold">Creado:</span>
                            {{ empty($channel['created_at']) ? '—' : \Illuminate\Support\Carbon::parse($channel['created_at'])->format('d/m/Y H:i') }}
                        </li>
                    </ul>

                    @if($hasError)
                        <div class="alert alert-warning border-0 small mb-3">{{ $channel['last_error'] }}</div>
                    @endif

                    {{-- Pruebas de conexion. Van aqui y no dentro del formulario
                         porque son acciones sobre el canal, como Sincronizar y
                         Eliminar. Los botones leen los campos del formulario por
                         id (channelHost, channelPort...), asi que estar fuera del
                         <form> no les afecta: el JS vive en _form.blade.php y
                         sigue encontrandolos en el mismo documento. --}}
                    <button type="button" class="btn btn-light w-100 mb-2" id="btn-test-channel">
                        Probar conexion IMAP
                    </button>
                    <div id="channelTestResult" class="d-none mb-2"></div>

                    <button type="button" class="btn btn-light w-100 mb-2" id="btn-test-smtp-channel">
                        Probar conexion SMTP
                    </button>
                    <div id="channelSmtpTestResult" class="d-none mb-2"></div>

                    <button type="button" class="btn btn-light w-100 mb-2 btn-sync-channel" data-id="{{ $channel['id'] }}">
                        Sincronizar ahora
                    </button>

                    <form action="{{ route('manager.helpdesk.settings.email-channels.destroy', $channel['id']) }}" method="POST"
                          class="needs-confirm" data-confirm-msg="Eliminar este canal de correo?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-light w-100">Eliminar canal</button>
                    </form>
                </div>
            </div>

            <div class="mt-3">
                @include('helpdesktickets::managers.settings.email-channels._help')
            </div>

        </div>

    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelector('.btn-sync-channel')?.addEventListener('click', function () {
        const button = this;
        const original = button.textContent;
        button.disabled = true;
        button.textContent = 'Sincronizando...';

        fetch('{{ route('manager.helpdesk.settings.email-channels.sync', $channel['id']) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        }).then(r => r.json()).then(data => {
            if (data.success) {
                toastr.success(data.message);
            } else {
                toastr.error(data.message);
            }
            setTimeout(() => location.reload(), 1200);
        }).catch(() => {
            toastr.error('Error inesperado al sincronizar el canal.');
            button.disabled = false;
            button.textContent = original;
        });
    });
});
</script>
@endpush
