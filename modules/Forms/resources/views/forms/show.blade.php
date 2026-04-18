@extends('layouts.theme')

@section('title', $form->name)

@section('content')

    @include('core::components.card', ['title' => $form->name])

    @include('core::components.alerts')

    @php
        $totalSubmissions = $form->submissions->count();
        $unreadCount      = $form->submissions->where('is_read', false)->count();
        $spamCount        = $form->submissions->where('is_spam', true)->count();
        $fieldsCount      = $form->fields->count();

        $accessLabels = [
            'public'        => 'Público',
            'authenticated' => 'Autenticado',
            'roles'         => 'Por roles',
        ];
    @endphp

    <div class="widget-content searchable-container list">
        <div class="row g-4 align-items-start">

            {{-- ── Columna izquierda ────────────────────────────────── --}}
            <div class="col-lg-8">
                <div class="card">

                    {{-- Información básica --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Información básica</h6>
                        <p class="text-muted mb-3">Datos generales del formulario.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre</label>
                                <input type="text" class="form-control" value="{{ $form->name }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Slug</label>
                                <input type="text" class="form-control font-monospace" value="{{ $form->slug }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Categoría</label>
                                <input type="text" class="form-control" value="{{ $form->category->name ?? '—' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estado</label>
                                <input type="text" class="form-control" value="{{ $form->is_active ? 'Activo' : 'Inactivo' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Acceso</label>
                                <input type="text" class="form-control" value="{{ $accessLabels[$form->access_control] ?? $form->access_control }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Multi-paso</label>
                                <input type="text" class="form-control" value="{{ $form->is_multi_step ? 'Sí' : 'No' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">CAPTCHA</label>
                                <input type="text" class="form-control" value="{{ $form->captcha_enabled ? 'Activo' : 'Inactivo' }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Múltiples envíos</label>
                                <input type="text" class="form-control" value="{{ $form->allow_multiple ? 'Permitidos' : 'Un envío por usuario' }}" disabled>
                            </div>
                            @if ($form->redirect_url)
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Redirección</label>
                                    <input type="text" class="form-control font-monospace" value="{{ $form->redirect_url }}" disabled>
                                </div>
                            @endif
                            @if ($form->expires_at)
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Expira</label>
                                    <input type="text" class="form-control {{ $form->expires_at->isPast() ? 'text-danger' : '' }}"
                                           value="{{ $form->expires_at->format('d/m/Y H:i') }}" disabled>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Creado</label>
                                <input type="text" class="form-control" value="{{ $form->created_at->format('d/m/Y') }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Actualizado</label>
                                <input type="text" class="form-control" value="{{ $form->updated_at->diffForHumans() }}" disabled>
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Campos --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Campos ({{ $fieldsCount }})</h6>
                        <p class="text-muted mb-3">Campos configurados en el formulario.</p>

                        @if ($form->fields->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Etiqueta</th>
                                            <th>Nombre clave</th>
                                            <th class="text-center">Requerido</th>
                                            <th class="text-center">Visible</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($form->fields as $field)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-light text-secondary font-monospace">{{ $field->type }}</span>
                                                </td>
                                                <td>{{ $field->label }}</td>
                                                <td class="font-monospace text-muted">{{ $field->name }}</td>
                                                <td class="text-center">
                                                    @if ($field->is_required)
                                                        <i class="fas fa-check text-success"></i>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($field->is_visible)
                                                        <i class="fas fa-check text-success"></i>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">Sin campos configurados.</p>
                        @endif
                    </div>

                    <hr class="my-0">

                    {{-- Shortcodes --}}
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-1">Shortcodes</h6>
                        <p class="text-muted mb-3">Inserta el formulario en cualquier página usando estos shortcodes.</p>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-muted">Por ID</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace"
                                           value='[form id="{{ $form->id }}"]' readonly>
                                    <button type="button" class="btn btn-info btn-copy-shortcode"
                                            data-shortcode='[form id="{{ $form->id }}"]'>
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-muted">Por slug</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace"
                                           value='[form slug="{{ $form->slug }}"]' readonly>
                                    <button type="button" class="btn btn-info btn-copy-shortcode"
                                            data-shortcode='[form slug="{{ $form->slug }}"]'>
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-0">

                    {{-- Preview --}}
                    <div class="card-body pb-0">
                        <h6 class="fw-bold text-dark mb-1">Vista previa</h6>
                        <p class="text-muted mb-3">Así se ve el formulario para los usuarios.</p>
                    </div>
                    <iframe src="{{ route('settings.forms.preview', $form) }}"
                            class="w-100 border-0"
                            style="height: 520px;"
                            title="Preview {{ $form->name }}"
                            loading="lazy"></iframe>

                </div>
            </div>

            {{-- ── Columna derecha: acciones + stats ───────────────── --}}
            <div class="col-lg-4">

                {{-- Acciones --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">Acciones</h6>
                        <p class="text-muted mb-3">Gestiona y configura este formulario.</p>

                        <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-primary w-100 mb-2">
                           Editar formulario
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary w-100 mb-2">
                            Ver submissions
                        </a>
                        <a href="{{ route('settings.forms.analytics', $form) }}" class="btn btn-outline-secondary w-100 mb-2">
                           Analytics
                        </a>
                        <button type="button" class="btn btn-outline-secondary w-100 mb-2" id="btnShowQr">
                            Ver QR Code
                        </button>
                        <a href="{{ route('settings.forms.export-json', $form) }}" class="btn btn-outline-secondary w-100">
                            Exportar JSON
                        </a>
                    </div>
                </div>

                {{-- Estadísticas --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Estadísticas</h6>

                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted">Total submissions</span>
                            <span class="fw-semibold">{{ $totalSubmissions }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted">Sin leer</span>
                            <span class="fw-semibold {{ $unreadCount > 0 ? 'text-warning' : '' }}">{{ $unreadCount }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted">Spam</span>
                            <span class="fw-semibold {{ $spamCount > 0 ? 'text-danger' : '' }}">{{ $spamCount }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted">Campos configurados</span>
                            <span class="fw-semibold">{{ $fieldsCount }}</span>
                        </div>
                    </div>
                </div>

                {{-- URL pública --}}
                @if ($form->is_active)
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1">URL pública</h6>
                            <p class="text-muted mb-3">Enlace directo al formulario.</p>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace bg-light"
                                       id="publicUrl"
                                       value="{{ route('forms.public.submit', $form->slug) }}"
                                       readonly>
                                <button type="button" class="btn btn-info" id="btnCopyUrl">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

        </div>
    </div>

    {{-- Modal QR Code --}}
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">QR Code — {{ $form->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="qrImage"
                         data-src="{{ route('settings.forms.qrcode', $form) }}"
                         alt="QR Code {{ $form->name }}"
                         class="img-fluid rounded mb-3"
                         style="max-width: 250px;">
                    <div>
                        <a href="{{ route('settings.forms.qrcode', $form) }}" download="qrcode-{{ $form->slug }}.png"
                           class="btn btn-outline-primary w-100">
                            <i class="fas fa-download me-1"></i> Descargar PNG
                        </a>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $('#btnShowQr').on('click', function () {
        var $img = $('#qrImage');
        if (!$img.attr('src')) {
            $img.attr('src', $img.data('src'));
        }
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    });

    $(document).on('click', '.btn-copy-shortcode', function () {
        var shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(function () {
            toastr.success('Shortcode copiado al portapapeles');
        });
    });

    $('#btnCopyUrl').on('click', function () {
        var url = $('#publicUrl').val();
        navigator.clipboard.writeText(url).then(function () {
            toastr.success('URL copiada al portapapeles');
        });
    });

});
</script>
@endpush
