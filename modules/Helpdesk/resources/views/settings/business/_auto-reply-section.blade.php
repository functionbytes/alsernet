{{--
    Sección reutilizable de CRUD "canal + idioma + mensaje" — misma forma para
    fuera de horario / bienvenida / despedida. Mismo layout que
    documents::settings.types.index (header con título+descripción a la
    izquierda y botón de alta a la derecha, tabla a ancho completo); el alta
    se hace en modal en vez de formulario inline, igual que la edición.
    Parámetros esperados: $sectionTitle, $sectionDescription, $items,
    $idPrefix (ids DOM únicos), $storeRoute, $updateRouteName,
    $destroyRouteName, $errorBag, $emptyMessage, $offHoursChannels,
    $offHoursLanguages, $deleteTitle, $editTitle.
--}}
<div class="card border-0 shadow-sm mt-1">
    <div class="card-header bg-white p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div class="me-3">
                <h5 class="mb-1 fw-bold">{{ $sectionTitle }}</h5>
                <p class="small mb-0 text-muted">{{ $sectionDescription }}</p>
            </div>
            <button type="button" class="btn btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#{{ $idPrefix }}-create-modal" title="Añadir mensaje" aria-label="Añadir mensaje">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="w-auto">Canal</th>
                        <th class="w-auto">Idioma</th>
                        <th>Mensaje</th>
                        <th class="text-center">Activo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="text-nowrap">{{ $offHoursChannels[$item->channel] ?? $item->channel }}</td>
                            <td class="text-nowrap">
                                @if($item->language)
                                    <span class="badge bg-info-subtle text-info">{{ $offHoursLanguages[$item->language] ?? $item->language }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Automático</span>
                                @endif
                            </td>
                            <td class="small text-break">{{ $item->message }}</td>
                            <td class="text-center">
                                @if($item->is_active)
                                    <span class="badge bg-success-subtle text-success">Sí</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-vertical"></i>
                                    </button>
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

                        {{-- Modal de edición --}}
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
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ $emptyMessage }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal de alta --}}
<div class="modal fade" id="{{ $idPrefix }}-create-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $storeRoute }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Añadir mensaje</h5>
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
                    <button type="submit" class="btn btn-primary w-100 mb-2">Añadir mensaje</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function () {
        // width:'100%' evita el bug de select2 calculando 0px de ancho
        // porque el <select> vive dentro de un modal oculto (display:none)
        // al momento del init; dropdownParent evita que el desplegable
        // quede detrás del modal.
        $('.select2').each(function () {
            $(this).select2({
                width: '100%',
                minimumResultsForSearch: Infinity,
                dropdownParent: $(this).closest('.modal'),
            });
        });
    });
</script>
@endpush

@if($errors->getBag($errorBag)->isNotEmpty() || session('error'))
    {{-- Validación fallida o duplicado detectado (este último llega como
         flash 'error', no como error de formulario): reabre el modal
         correspondiente — el de alta, o la edición concreta vía
         old('_item_id') — en vez de dejar el motivo oculto dentro de un
         modal cerrado. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var failedItemId = @json(old('_item_id'));
            var modalId = failedItemId ? '{{ $idPrefix }}-edit-' + failedItemId : '{{ $idPrefix }}-create-modal';
            var modalEl = document.getElementById(modalId);
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        });
    </script>
@endif
