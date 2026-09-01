@extends('layouts.theme')

@section('title', 'Log de emails — Lista de supresión')

@section('page_header')
    @include('core::components.card', ['title' => 'Log de emails — Lista de supresión'])
@endsection

@section('content')
    @include('core::components.alerts')

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header p-4 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1 fw-bold">Lista de supresión</h5>
                        <p class="small mb-0 text-muted">
                            A estas direcciones nunca se les vuelve a enviar correo automático ni manual. Un rebote
                            permanente o una queja de spam las añaden aquí automáticamente; también se pueden añadir
                            a mano (p. ej. una baja voluntaria pedida por teléfono).
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#suppression-add-modal">
                        Añadir dirección
                    </button>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Email</th>
                                    <th>Alcance</th>
                                    <th>Motivo</th>
                                    <th>Origen</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suppressions as $s)
                                    <tr>
                                        <td class="fw-semibold">{{ $s->email }}</td>
                                        <td>
                                            @if($s->module === '')
                                                <span class="badge bg-danger-subtle text-danger">Global</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $s->module }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $s->reason->label() }}</td>
                                        <td class="small text-muted">
                                            @if($s->causer_id)
                                                {{ $s->causer?->name ?? ('#'.$s->causer_id) }}
                                            @else
                                                Automático
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical" aria-hidden="true"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form method="POST" action="{{ route('settings.helpdeskemaillog.suppressions.destroy', $s) }}"
                                                              onsubmit="return confirm('¿Quitar esta dirección de la lista de supresión? Volverá a recibir correo.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item">Quitar</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Sin direcciones suprimidas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($suppressions->hasPages())
                        <div class="p-3">{{ $suppressions->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: añadir --}}
    <div class="modal fade" id="suppression-add-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('settings.helpdeskemaillog.suppressions.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Añadir dirección a la lista de supresión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-email" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="add-email" name="email" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="add-module" class="form-label fw-semibold">Alcance</label>
                        <select class="form-select" id="add-module" name="module">
                            <option value="">Global (todos los módulos)</option>
                            @foreach($availableModules as $mod)
                                <option value="{{ $mod }}">Solo {{ $mod }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add-reason" class="form-label fw-semibold">Motivo</label>
                        <select class="form-select" id="add-reason" name="reason" required>
                            @foreach(\Modules\HelpdeskEmailLog\Enums\SuppressionReason::options() as $value => $label)
                                <option value="{{ $value }}" @selected($value === 'manual')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label for="add-notes" class="form-label fw-semibold">Notas</label>
                        <textarea class="form-control" id="add-notes" name="notes" rows="2" maxlength="2000"
                                  placeholder="Ej. cliente pidió baja por teléfono el 31/08"></textarea>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Añadir</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#suppression-add-modal .form-select').select2({ width: '100%', dropdownParent: $('#suppression-add-modal') });
});
</script>
@endpush
