{{--
    Seccion reutilizable de CRUD "canal + idioma + mensaje" — misma forma para
    fuera de horario / bienvenida / despedida. Sigue el layout de los listados
    de ajustes de tickets (ticket-categories): cabecera con titulo y boton de
    alta, tarjetas de totales, barra de filtros, tabla con seleccion multiple,
    acciones masivas y paginacion. El alta y la edicion siguen en modal.

    Parametros: $sectionTitle, $sectionDescription, $items (paginador), $stats,
    $idPrefix (ids DOM unicos), $storeRoute, $updateRouteName,
    $destroyRouteName, $bulkRouteName, $indexRouteName, $errorBag,
    $emptyMessage, $offHoursChannels, $offHoursLanguages, $deleteTitle,
    $editTitle, $itemNoun (p. ej. "mensaje").
--}}
@php
    // "__none__" representa las filas sin canal/idioma (NULL): en la URL el
    // valor vacio no se distingue de "sin filtro".
    $ccNone = '__none__';
    $ccFiltering = request()->hasAny(['search', 'channel', 'language', 'status']);
    $ccActiveFilterCount = collect(['channel', 'language', 'status'])->filter(fn ($k) => request($k))->count();
@endphp

<div class="card">

    {{-- Cabecera --}}
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">{{ $sectionTitle }}</h5>
                <p class="small mb-0 text-muted">{{ $sectionDescription }}</p>
            </div>
            <div class="ms-auto ps-3">
                <button type="button" class="btn btn-primary flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#{{ $idPrefix }}-create-modal">
                    Nuevo {{ $itemNoun }}
                </button>
            </div>
        </div>
    </div>

    {{-- Totales --}}
    <div class="card-body border-bottom">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Total</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                        <small class="text-muted">Mensajes configurados</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Activos</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                        <small class="text-muted">Se envian</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Inactivos</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                        <small class="text-muted">Guardados sin usar</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Canales</h6>
                        <h4 class="mb-1 fw-bold">{{ $stats['channels'] }} <span class="fs-6 fw-normal text-muted">/ {{ $stats['channels_total'] }}</span></h4>
                        <small class="text-muted">Con mensaje activo</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route($indexRouteName) }}" id="{{ $idPrefix }}-filter-form">
            <input type="hidden" name="channel" id="{{ $idPrefix }}-filter-channel" value="{{ request('channel') }}">
            <input type="hidden" name="language" id="{{ $idPrefix }}-filter-language" value="{{ request('language') }}">
            <input type="hidden" name="status" id="{{ $idPrefix }}-filter-status" value="{{ request('status') }}">

            <div class="d-flex align-items-center gap-2">
                <input type="search" name="search" class="form-control flex-grow-1"
                       placeholder="Buscar en el texto del mensaje..."
                       value="{{ request('search') }}">

                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#{{ $idPrefix }}-filter-modal" title="Filtros avanzados">
                    <i class="fas fa-filter"></i>
                    @if($ccActiveFilterCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary ts-filter-badge">
                            {{ $ccActiveFilterCount }}
                        </span>
                    @endif
                </button>

                <div class="d-flex gap-1 flex-shrink-0">
                    <button type="submit" class="btn btn-primary" title="Buscar">
                        <i class="fas fa-magnifying-glass"></i>
                    </button>
                    @if($ccFiltering)
                        <a href="{{ route($indexRouteName) }}" class="btn btn-secondary" title="Limpiar filtros">
                            <i class="fas fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </div>

            @if($ccActiveFilterCount > 0)
                <div class="d-flex gap-2 flex-wrap mt-4">
                    <div>
                        <h6 class="mb-1">Filtrados:</h6>
                    </div>
                    @if(request('channel'))
                        <span class="badge bg-primary-subtle text-primary py-1 px-2">
                            Canal: {{ $offHoursChannels[request('channel') === $ccNone ? null : request('channel')] ?? request('channel') }}
                        </span>
                    @endif
                    @if(request('language'))
                        <span class="badge bg-primary-subtle text-primary py-1 px-2">
                            Idioma: {{ $offHoursLanguages[request('language') === $ccNone ? null : request('language')] ?? request('language') }}
                        </span>
                    @endif
                    @if(request('status'))
                        <span class="badge bg-primary-subtle text-primary py-1 px-2">
                            Estado: {{ request('status') === 'active' ? 'Activos' : 'Inactivos' }}
                        </span>
                    @endif
                </div>
            @endif
        </form>
    </div>

    {{-- Tabla --}}
    <div class="card-body">
        @if($items->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                            <th class="text-nowrap">Canal</th>
                            <th class="text-nowrap">Idioma</th>
                            <th>Mensaje</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $item->id }}"></td>
                                <td class="text-nowrap">{{ $offHoursChannels[$item->channel] ?? $item->channel }}</td>
                                <td class="text-nowrap">
                                    @if($item->language)
                                        <span class="badge bg-primary-subtle text-primary">{{ $offHoursLanguages[$item->language] ?? $item->language }}</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Automatico</span>
                                    @endif
                                </td>
                                <td class="small text-break">{{ Str::limit($item->message, 120) }}</td>
                                <td class="text-center">
                                    @if($item->is_active)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#{{ $idPrefix }}-edit-{{ $item->id }}">
                                                    Editar
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item delete-btn" href="#"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#delete-modal"
                                                   data-url="{{ route($destroyRouteName, $item) }}"
                                                   data-title="{{ $deleteTitle }}">
                                                    Eliminar
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">
                    @if($ccFiltering)
                        Ningun mensaje coincide con los filtros aplicados
                    @else
                        {{ $emptyMessage }}
                    @endif
                </p>
                @if($ccFiltering)
                    <a href="{{ route($indexRouteName) }}" class="btn btn-secondary">Limpiar filtros</a>
                @else
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $idPrefix }}-create-modal">
                        Nuevo {{ $itemNoun }}
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Paginacion --}}
    @if($items->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando {{ $items->firstItem() }} - {{ $items->lastItem() }} de {{ $items->total() }}
                </div>
                <div>{{ $items->links() }}</div>
            </div>
        </div>
    @endif

</div>

{{-- Modales de edicion: fuera de la tabla, un <div> dentro de <tbody> no es
     HTML valido y el navegador lo saca del sitio al parsear. --}}
@foreach($items as $item)
    <div class="modal fade" id="{{ $idPrefix }}-edit-{{ $item->id }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route($updateRouteName, $item) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_item_id" value="{{ $item->id }}">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editTitle }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small">Canal</label>
                            <select name="channel" class="form-select">
                                @foreach($offHoursChannels as $value => $label)
                                    <option value="{{ $value }}" {{ $item->channel === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Idioma</label>
                            <select name="language" class="form-select">
                                @foreach($offHoursLanguages as $value => $label)
                                    <option value="{{ $value }}" {{ $item->language === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Mensaje</label>
                            <textarea name="message" rows="3" maxlength="1000" class="form-control" required>{{ $item->message }}</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small" for="{{ $idPrefix }}-active-{{ $item->id }}">Estado</label>
                            <select name="is_active" id="{{ $idPrefix }}-active-{{ $item->id }}" class="form-select select2" data-minimum-results-for-search="Infinity">
                                <option value="1" @selected($item->is_active)>Activo</option>
                                <option value="0" @selected(! $item->is_active)>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar cambios</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- Modal de alta --}}
<div class="modal fade" id="{{ $idPrefix }}-create-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $storeRoute }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo {{ $itemNoun }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small" for="{{ $idPrefix }}-channel">Canal</label>
                        <select name="channel" id="{{ $idPrefix }}-channel" class="form-select @error('channel', $errorBag) is-invalid @enderror">
                            @foreach($offHoursChannels as $value => $label)
                                <option value="{{ $value }}" {{ old('channel') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('channel', $errorBag)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="{{ $idPrefix }}-language">Idioma</label>
                        <select name="language" id="{{ $idPrefix }}-language" class="form-select @error('language', $errorBag) is-invalid @enderror">
                            @foreach($offHoursLanguages as $value => $label)
                                <option value="{{ $value }}" {{ old('language') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('language', $errorBag)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="{{ $idPrefix }}-message">Mensaje</label>
                        <textarea name="message" id="{{ $idPrefix }}-message" rows="3" maxlength="1000" class="form-control @error('message', $errorBag) is-invalid @enderror" required>{{ old('message') }}</textarea>
                        @error('message', $errorBag)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label small" for="{{ $idPrefix }}-active">Estado</label>
                        <select name="is_active" id="{{ $idPrefix }}-active" class="form-select select2" data-minimum-results-for-search="Infinity">
                            <option value="1" @selected(old('is_active', true))>Activo</option>
                            <option value="0" @selected(! old('is_active', true))>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Nuevo {{ $itemNoun }}</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal de filtros avanzados --}}
<div class="modal fade" id="{{ $idPrefix }}-filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filtros avanzados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Canal</label>
                    <select id="{{ $idPrefix }}-modal-channel" class="form-control select2-filter-modal">
                        <option value="">Todos los canales</option>
                        @foreach($offHoursChannels as $value => $label)
                            @php $opt = $value === null || $value === '' ? $ccNone : $value; @endphp
                            <option value="{{ $opt }}" @selected(request('channel') === $opt)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Idioma</label>
                    <select id="{{ $idPrefix }}-modal-language" class="form-control select2-filter-modal">
                        <option value="">Todos los idiomas</option>
                        @foreach($offHoursLanguages as $value => $label)
                            @php $opt = $value === null || $value === '' ? $ccNone : $value; @endphp
                            <option value="{{ $opt }}" @selected(request('language') === $opt)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Estado</label>
                    <select id="{{ $idPrefix }}-modal-status" class="form-control select2-filter-modal">
                        <option value="">Activos e inactivos</option>
                        <option value="active" @selected(request('status') === 'active')>Solo activos</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Solo inactivos</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="{{ $idPrefix }}-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                    Aplicar filtros
                </button>
                <button type="button" id="{{ $idPrefix }}-filter-clear-btn" class="btn btn-secondary w-100">
                    Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Barra flotante de seleccion --}}
<div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none ts-bulk-toolbar">
    <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
        <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
    </button>
</div>

{{-- Modal de accion masiva --}}
<div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Accion masiva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Se aplicara la accion sobre <strong><span data-bulk-count>0</span> mensaje(s)</strong>.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Accion</label>
                    <select id="bulk-action-select" class="form-select select2">
                        <option value="">Seleccionar accion...</option>
                        <option value="activate">Activar</option>
                        <option value="deactivate">Desactivar</option>
                        <option value="delete">Eliminar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .ts-bulk-toolbar { z-index: 1050; }
    .ts-filter-badge { font-size: .6rem; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
@php
    $hdAutoReplySectionConfig = [
    'idPrefix' => $idPrefix,
    'bulkUrl' => route($bulkRouteName),
    // Validacion fallida o duplicado detectado (este ultimo llega como flash
    // 'error', no como error de formulario): reabrir el modal correspondiente
    // — el de alta, o la edicion concreta via old('_item_id') — en vez de
    // dejar el motivo oculto dentro de un modal cerrado.
    'reopenModalId' => ($errors->getBag($errorBag)->isNotEmpty() || session('error'))
        ? ($idPrefix.'-'.(old('_item_id') ? 'edit-'.old('_item_id') : 'create-modal'))
        : null
];
@endphp
window.HdAutoReplySectionConfig = @json($hdAutoReplySectionConfig);
</script>
<script>window.HdSettingsCommonSkipAutoInit = true;</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/auto-reply-section.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/auto-reply-section.js')) }}" defer></script>
@endpush
