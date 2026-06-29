@extends('layouts.theme')

@section('title', 'Automatizaciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Automatizaciones'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="text-muted mb-0 small">Flujos automáticos de email activados por eventos de tus clientes</p>
        </div>
        <a href="{{ route('remarketing.automations.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva automatización
        </a>
    </div>

    @if($automations->isEmpty())
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-bolt fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-3">No hay automatizaciones configuradas todavía</p>
                <a href="{{ route('remarketing.automations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Crear primera automatización
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($automations as $automation)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1 me-2">
                                    <h6 class="fw-bold mb-1">{{ $automation->name }}</h6>
                                    @php
                                        $triggerLabels = [
                                            'welcome'       => ['primary',   'Bienvenida'],
                                            'cart_abandoned'=> ['warning',   'Carrito abandonado'],
                                            'post_purchase' => ['success',   'Post-compra'],
                                            'win_back'      => ['danger',    'Recuperar cliente'],
                                        ];
                                        [$tColor, $tLabel] = $triggerLabels[$automation->trigger] ?? ['secondary', $automation->trigger];
                                    @endphp
                                    <span class="badge bg-{{ $tColor }}-subtle text-{{ $tColor }}">{{ $tLabel }}</span>
                                </div>
                                <div class="form-check form-switch ms-2">
                                    <input class="form-check-input toggle-automation"
                                           type="checkbox"
                                           data-id="{{ $automation->id }}"
                                           {{ $automation->status === 'active' ? 'checked' : '' }}>
                                </div>
                            </div>

                            {{-- Steps preview --}}
                            @if($automation->steps && $automation->steps->count() > 0)
                                <div class="mb-3">
                                    <small class="text-muted fw-semibold">Pasos:</small>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($automation->steps->take(3) as $step)
                                            @if($step->type === 'wait')
                                                <span class="badge bg-light text-muted border">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Esperar {{ $step->config['hours'] ?? '?' }}h
                                                </span>
                                            @elseif($step->type === 'send_email')
                                                <span class="badge bg-primary-subtle text-primary">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    Email
                                                </span>
                                            @endif
                                        @endforeach
                                        @if($automation->steps->count() > 3)
                                            <span class="badge bg-light text-muted border">+{{ $automation->steps->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span><i class="fas fa-play me-1"></i>{{ number_format($automation->runs_total) }} ejecuciones</span>
                                <span class="{{ $automation->status === 'active' ? 'text-success' : 'text-secondary' }}">
                                    {{ $automation->status === 'active' ? 'Activa' : ucfirst($automation->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top d-flex gap-2">
                            <a href="{{ route('remarketing.automations.edit', $automation) }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item btn-delete-automation"
                                                data-id="{{ $automation->id }}"
                                                data-name="{{ $automation->name }}"
                                                data-url="{{ route('remarketing.automations.destroy', $automation) }}">
                                            Eliminar
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar automatización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar la automatización <strong id="delete-name"></strong>? Se detendrán todas las ejecuciones activas.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar automatización</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $(document).on('change', '.toggle-automation', function () {
        var id = $(this).data('id');
        var active = $(this).is(':checked');
        $.ajax({
            url: '/api/remarketing/automations/' + id + '/toggle',
            method: 'POST',
            data: { active: active ? 1 : 0 },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message || (active ? 'Automatización activada' : 'Automatización pausada'));
            },
            error: function () {
                toastr.error('No se pudo cambiar el estado');
                // revert toggle
                $('[data-id="' + id + '"]').prop('checked', !active);
            }
        });
    });

    $(document).on('click', '.btn-delete-automation', function () {
        $('#delete-name').text($(this).data('name'));
        $('#deleteForm').attr('action', $(this).data('url'));
        $('#deleteModal').modal('show');
    });

});
</script>
@endpush
