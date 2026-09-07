@extends('layouts.theme')

@section('title', 'Actividad de correo — Lista de supresión')

{{-- Sin page_header y con content_full_width, igual que el listado: el
     título de la franja del tema repetía el que ya lleva el breadcrumb de
     .evx-toolbar de dentro, y dejaba estas pantallas con un ancho distinto
     al del listado del que cuelgan. --}}
@section('content_full_width', true)

@include('helpdeskemailactivity::settings.partials.css')

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <div class="evx-shell">
            @include('helpdeskemailactivity::settings.partials.subnav', ['current' => 'suppressions'])

            <div class="evx-section-block d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.suppressions.heading') }}</h2>
                    <p class="evx-section-desc mb-0">
                        A estas direcciones nunca se les vuelve a enviar correo automático ni manual. Un rebote
                        permanente o una queja de spam las añaden aquí automáticamente; también se pueden añadir
                        a mano (p. ej. una baja voluntaria pedida por teléfono).
                    </p>
                </div>
                <button type="button" class="evx-btn evx-btn-primary evx-btn-inline" data-bs-toggle="modal" data-bs-target="#suppression-add-modal">
                    <i class="fas fa-plus" aria-hidden="true"></i> {{ __('helpdeskemailactivity::emaillog.suppressions.add') }}
                </button>
            </div>

            <div class="evx-list">
                @forelse($suppressions as $s)
                    <div class="evx-list-row">
                        <div class="evx-list-main">
                            <div class="evx-list-title d-flex align-items-center gap-2">
                                <span>{{ $s->email }}</span>
                                <span class="evx-tag {{ $s->module === '' ? '' : 'mono' }}">{{ $s->module === '' ? 'Global' : $s->module }}</span>
                            </div>
                            <div class="evx-list-sub">
                                {{ $s->reason->label() }} ·
                                {{ $s->causer_id ? ($s->causer?->name ?? '#'.$s->causer_id) : 'Automático' }}
                            </div>
                        </div>
                        <span class="evx-list-date">{{ $s->created_at->format('d/m/Y H:i') }}</span>
                        <div class="dropdown">
                            <button type="button" class="evx-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones">
                                <i class="fas fa-ellipsis-vertical" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <form method="POST" action="{{ route('settings.helpdeskemailactivity.suppressions.destroy', $s) }}"
                                          onsubmit="return confirm('¿Quitar esta dirección de la lista de supresión? Volverá a recibir correo.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item">{{ __('helpdeskemailactivity::emaillog.suppressions.remove') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                @empty
                    <div class="evx-empty-row">
                        <i class="fas fa-shield" aria-hidden="true"></i>
                        <p>{{ __('helpdeskemailactivity::emaillog.suppressions.empty') }}</p>
                    </div>
                @endforelse
            </div>

            @if($suppressions->hasPages())
                <div class="evx-pagination">
                    {{ $suppressions->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: añadir --}}
    <div class="modal fade evx-dialog" id="suppression-add-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('settings.helpdeskemailactivity.suppressions.store') }}" class="modal-content">
                @csrf
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-ban',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.suppression'),
                    'title' => __('helpdeskemailactivity::emaillog.suppressions.add_title'),
                    'titleId' => 'a-adir-direcci-n-a-la-lista-de-supresi-n-title',
                ])
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-email" class="form-label fw-semibold">{{ __('helpdeskemailactivity::emaillog.suppressions.email') }}</label>
                        <input type="email" class="form-control" id="add-email" name="email" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="add-module" class="form-label fw-semibold">{{ __('helpdeskemailactivity::emaillog.suppressions.scope') }}</label>
                        <select class="form-select" id="add-module" name="module">
                            <option value="">{{ __('helpdeskemailactivity::emaillog.suppressions.scope_global') }}</option>
                            @foreach($availableModules as $mod)
                                <option value="{{ $mod }}">Solo {{ $mod }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add-reason" class="form-label fw-semibold">{{ __('helpdeskemailactivity::emaillog.suppressions.reason_label') }}</label>
                        <select class="form-select" id="add-reason" name="reason" required>
                            @foreach(\Modules\HelpdeskEmailActivity\Enums\SuppressionReason::options() as $value => $label)
                                <option value="{{ $value }}" @selected($value === 'manual')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label for="add-notes" class="form-label fw-semibold">{{ __('helpdeskemailactivity::emaillog.suppressions.notes') }}</label>
                        <textarea class="form-control" id="add-notes" name="notes" rows="2" maxlength="2000"
                                  placeholder="{{ __('helpdeskemailactivity::emaillog.suppressions.notes_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('helpdeskemailactivity::emaillog.suppressions.submit') }}</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('helpdeskemailactivity::emaillog.suppressions.cancel') }}</button>
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
