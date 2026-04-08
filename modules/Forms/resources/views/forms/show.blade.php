@extends('layouts.theme')

@section('title', $form->name)

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => $form->name,
    ])

    {{-- Actions --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('settings.forms.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="text-muted">{{ $form->slug }}</span>
            @if ($form->is_active)
                <span class="badge bg-light-success text-success">Activo</span>
            @else
                <span class="badge bg-light-danger text-danger">Inactivo</span>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-pencil-alt me-1"></i> Editar
            </a>
            <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-inbox me-1"></i> Ver submissions
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnShowQr">
                <i class="fas fa-qrcode me-1"></i> QR Code
            </button>
            <a href="{{ route('settings.forms.export-json', $form) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-file-export me-1"></i> Exportar JSON
            </a>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $totalSubmissions = $form->submissions->count();
        $unread           = $form->submissions->where('status', 'unread')->count();
        $spam             = $form->submissions->where('is_spam', true)->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-light-primary text-primary">
                        <i class="fas fa-inbox fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $totalSubmissions }}</h5>
                        <small class="text-muted">Total submissions</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-light-warning text-warning">
                        <i class="fas fa-envelope fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $unread }}</h5>
                        <small class="text-muted">No leídas</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-light-danger text-danger">
                        <i class="fas fa-ban fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $spam }}</h5>
                        <small class="text-muted">Spam</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-light-info text-info">
                        <i class="fas fa-th-list fs-4"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $form->fields->count() }}</h5>
                        <small class="text-muted">Campos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row g-3">

        {{-- Columna principal: shortcodes + preview --}}
        <div class="col-lg-8">

            {{-- Shortcodes --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Shortcodes para insertar el formulario</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Por ID</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace"
                                       value='[form id="{{ $form->id }}"]' readonly>
                                <button type="button"
                                        class="btn btn-outline-secondary btn-copy-shortcode"
                                        data-shortcode='[form id="{{ $form->id }}"]'
                                        title="Copiar shortcode"
                                        aria-label="Copiar shortcode por ID">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Por slug</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace"
                                       value='[form slug="{{ $form->slug }}"]' readonly>
                                <button type="button"
                                        class="btn btn-outline-secondary btn-copy-shortcode"
                                        data-shortcode='[form slug="{{ $form->slug }}"]'
                                        title="Copiar shortcode"
                                        aria-label="Copiar shortcode por slug">
                                    <i class="far fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preview --}}
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-bottom">
                    <h6 class="fw-bold mb-0">Preview del formulario</h6>
                    <a href="{{ route('settings.forms.preview', $form) }}" target="_blank"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> Abrir en nueva pestaña
                    </a>
                </div>
                <div class="card-body p-0">
                    <iframe src="{{ route('settings.forms.preview', $form) }}"
                            class="w-100 border-0 rounded-bottom"
                            style="height: 500px; min-height: 300px;"
                            title="Preview {{ $form->name }}"
                            loading="lazy"></iframe>
                </div>
            </div>

        </div>

        {{-- Columna lateral: info --}}
        <div class="col-lg-4">

            {{-- Info --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Información</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Categoría</dt>
                        <dd class="col-7">{{ $form->category->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Acceso</dt>
                        <dd class="col-7">
                            @php
                                $accessLabels = [
                                    'public'        => 'Público',
                                    'authenticated' => 'Autenticado',
                                    'roles'         => 'Por roles',
                                ];
                            @endphp
                            {{ $accessLabels[$form->access_control] ?? $form->access_control }}
                        </dd>
                        <dt class="col-5 text-muted">Multi-paso</dt>
                        <dd class="col-7">{{ $form->is_multi_step ? 'Sí' : 'No' }}</dd>
                        <dt class="col-5 text-muted">CAPTCHA</dt>
                        <dd class="col-7">{{ $form->captcha_enabled ? 'Activo' : 'Inactivo' }}</dd>
                        <dt class="col-5 text-muted">Creado</dt>
                        <dd class="col-7">{{ $form->created_at->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted">Actualizado</dt>
                        <dd class="col-7">{{ $form->updated_at->diffForHumans() }}</dd>
                        @if ($form->expires_at)
                            <dt class="col-5 text-muted">Expira</dt>
                            <dd class="col-7 {{ $form->expires_at->isPast() ? 'text-danger' : '' }}">
                                {{ $form->expires_at->format('d/m/Y H:i') }}
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Campos del formulario --}}
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Campos ({{ $form->fields->count() }})</h6>
                    @forelse ($form->fields as $field)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-light text-muted">{{ $field->type }}</span>
                            <span class="small text-truncate">{{ $field->label }}</span>
                            @if ($field->is_required)
                                <span class="badge bg-light-danger text-danger ms-auto flex-shrink-0">*</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Sin campos configurados</p>
                    @endforelse
                </div>
                @if ($form->fields->isNotEmpty())
                    <div class="card-footer border-top">
                        <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-pencil-alt me-1"></i> Editar campos
                        </a>
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
                <img src="{{ route('settings.forms.qrcode', $form) }}"
                     alt="QR Code {{ $form->name }}"
                     class="img-fluid rounded mb-3"
                     style="max-width: 250px;">
                <div>
                    <a href="{{ route('settings.forms.qrcode', $form) }}" download="qrcode-{{ $form->slug }}.png"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download me-1"></i> Descargar PNG
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $('#btnShowQr').on('click', function () {
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    });

    $(document).on('click', '.btn-copy-shortcode', function () {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(function () {
            toastr.success('Shortcode copiado al portapapeles');
        });
    });
</script>
@endpush
