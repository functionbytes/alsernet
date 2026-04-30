@extends('layouts.theme')

@section('title', 'Versiones: ' . $form->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Versiones'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('settings.forms.edit', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                            <small class="text-muted">{{ $form->slug }}</small>
                        </div>
                        @if ($form->is_active)
                            <span class="badge bg-light-success text-success">Activo</span>
                        @else
                            <span class="badge bg-light-danger text-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.forms.preview', $form) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-desktop me-1"></i> Preview
                        </a>
                        <a href="{{ route('settings.forms.submissions.index', $form) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-inbox me-1"></i> Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header p-4 border-bottom">
                        <h5 class="mb-1 fw-bold">Historial de versiones</h5>
                        <p class="small mb-0 text-muted">Versiones guardadas automáticamente al modificar los campos del formulario</p>
                    </div>
                    <div class="card-body">
                        @if ($versions->isEmpty())
                            <div class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-history fs-7"></i>
                                    </div>
                                    <h6 class="mb-1">No hay versiones disponibles</h6>
                                    <p class="text-muted mb-0">Las versiones se guardan automáticamente al modificar los campos del formulario</p>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Versión</th>
                                            <th>Fecha</th>
                                            <th class="text-center">Campos</th>
                                            <th>Creado por</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($versions as $version)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-primary-subtle text-primary">
                                                            v{{ $version->version_number ?? $loop->iteration }}
                                                        </span>
                                                        @if ($loop->first)
                                                            <span class="badge bg-success-subtle text-success">Actual</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    {{ $version->created_at?->format('d/m/Y H:i') }}
                                                    <small class="d-block text-muted">{{ $version->created_at?->diffForHumans() }}</small>
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $fieldCount = is_array($version->fields_snapshot) ? count($version->fields_snapshot) : 0;
                                                    @endphp
                                                    <span class="badge bg-light text-dark border">{{ $fieldCount }}</span>
                                                </td>
                                                <td>{{ $version->creator?->name ?? '—' }}</td>
                                                <td class="text-center">
                                                    @if (!$loop->first)
                                                        <div class="dropdown">
                                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-vertical"></i>
                                                            </a>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li>
                                                                    <button type="button" class="dropdown-item btn-restore"
                                                                            data-url="{{ route('settings.forms.versions.restore', [$form, $version]) }}"
                                                                            data-version="{{ $version->version_number ?? $loop->iteration }}">
                                                                        Restaurar
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <hr class="my-4">

                        <p class="small text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Restaurar una versión reemplazará la estructura de campos actual del formulario. Las submissions existentes no se verán afectadas.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                @include('forms::settings.partials.tabs', ['active' => 'versions'])

                <div class="card mt-3">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0 fw-bold">Sobre las versiones</h6>
                    </div>
                    <div class="card-body">
                        <ul class="text-muted ps-3 mb-0 small">
                            <li class="mb-1">Las versiones se guardan automáticamente al modificar los campos.</li>
                            <li class="mb-1">Restaurar una versión reemplaza los campos actuales.</li>
                            <li>Las submissions existentes no se ven afectadas al restaurar.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $(document).on('click', '.btn-restore', function () {
        const version = $(this).data('version');
        if (!confirm('¿Restaurar la versión #' + version + '? Los campos actuales serán reemplazados.')) return;

        const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Restaurando...');

        $.ajax({
            url: $(this).data('url'),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message || 'Versión restaurada correctamente');
                setTimeout(function () { location.reload(); }, 1200);
            },
            error: function () {
                toastr.error('Error al restaurar la versión');
                $btn.prop('disabled', false).html('Restaurar');
            }
        });
    });

    @if(session('success'))
    toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
    toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
