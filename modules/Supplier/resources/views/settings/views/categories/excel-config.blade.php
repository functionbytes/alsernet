@extends('layouts.theme')

@section('title', 'Configuración importación Excel')

@section('page_header')
    @include('core::components.card', ['title' => 'Configuración importación Excel'])
@endsection

@section('content')

    <div class="row">

        <div class="col-lg-8">
            <div class="card">

                <form id="excel-config-form">
                    @csrf

                    <div class="card-header">
                        <h5 class="mb-1">Configuración de importación excel</h5>
                        <p class="small mb-0 text-muted">
                            Define qué columnas del Excel ERP corresponden a cada nivel jerárquico.
                            La jerarquía del archivo es: Deporte → Categoría → Familia → Subfamilia → Grupo.
                        </p>
                    </div>

                    <div class="card-body">

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="import_level">Nivel a importar como familia</label>
                                <select class="form-select select2" id="import_level" name="import_level">
                                    @foreach(['deporte' => 'Deporte', 'categoria' => 'Categoría', 'familia' => 'Familia', 'subfamilia' => 'Subfamilia', 'grupo' => 'Grupo'] as $value => $label)
                                        <option value="{{ $value }}" {{ $config['import_level'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">El nivel seleccionado se importará en la tabla de familias (supplier_categories).</small>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="strip_prefix">Prefijo a eliminar</label>
                                <input type="text" class="form-control" id="strip_prefix" name="strip_prefix"
                                       value="{{ $config['strip_prefix'] }}" placeholder="Ej: T.">
                                <small class="text-muted">Se elimina del inicio de cada nombre al importar. Dejar vacío para no eliminar nada.</small>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-4" style="background:#f8f9fa;">
                            <p class="small mb-3 fw-semibold text-dark">Instrucciones por nivel</p>
                            <table class="table table-sm small mb-0">
                                <thead class="table-light">
                                    <tr><th style="width:110px;">Nivel</th><th>Descripción</th></tr>
                                </thead>
                                <tbody class="text-muted">
                                    <tr><td><strong>Deporte</strong></td><td>Nivel más general. Agrupa todas las categorías de un deporte (ej. Golf). Se sincroniza automáticamente al importar.</td></tr>
                                    <tr><td><strong>Categoría</strong></td><td>Agrupación dentro de un deporte (ej. Accesorios golf). Se almacena como referencia en cada familia.</td></tr>
                                    <tr><td><strong>Familia</strong></td><td>Subgrupo de una categoría (ej. Fundas). Nivel recomendado para importar si se desea un catálogo equilibrado.</td></tr>
                                    <tr><td><strong>Subfamilia</strong></td><td>Especialización de una familia (ej. Hierros). Usar si se necesita mayor granularidad en el catálogo.</td></tr>
                                    <tr><td><strong>Grupo</strong></td><td>Nivel más específico (ej. Fundas hierros). Genera el mayor número de registros al importar.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="control-label col-form-label d-block">Deporte</label>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_deporte_id">Columna ID</label>
                                <input type="text" class="form-control" id="col_deporte_id" name="col_deporte_id"
                                       value="{{ $config['col_deporte_id'] }}" placeholder="IDDEPORTE_CL">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_deporte_name">Columna nombre</label>
                                <input type="text" class="form-control" id="col_deporte_name" name="col_deporte_name"
                                       value="{{ $config['col_deporte_name'] }}" placeholder="DESC_DEPORTE_CL">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="control-label col-form-label d-block">Categoría</label>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_categoria_id">Columna ID</label>
                                <input type="text" class="form-control" id="col_categoria_id" name="col_categoria_id"
                                       value="{{ $config['col_categoria_id'] }}" placeholder="IDCATEGORIA_CL">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_categoria_name">Columna nombre</label>
                                <input type="text" class="form-control" id="col_categoria_name" name="col_categoria_name"
                                       value="{{ $config['col_categoria_name'] }}" placeholder="DESC_CATEGORIA_CL">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="control-label col-form-label d-block">Familia</label>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_familia_id">Columna ID</label>
                                <input type="text" class="form-control" id="col_familia_id" name="col_familia_id"
                                       value="{{ $config['col_familia_id'] }}" placeholder="IDFAMILIA_CL">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_familia_name">Columna nombre</label>
                                <input type="text" class="form-control" id="col_familia_name" name="col_familia_name"
                                       value="{{ $config['col_familia_name'] }}" placeholder="DESC_FAMILIA_CL">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="control-label col-form-label d-block">Subfamilia</label>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_subfamilia_id">Columna ID</label>
                                <input type="text" class="form-control" id="col_subfamilia_id" name="col_subfamilia_id"
                                       value="{{ $config['col_subfamilia_id'] }}" placeholder="IDSUBFAMILIA_CL">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_subfamilia_name">Columna nombre</label>
                                <input type="text" class="form-control" id="col_subfamilia_name" name="col_subfamilia_name"
                                       value="{{ $config['col_subfamilia_name'] }}" placeholder="DESC_SUBFAMILIA_CL">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="control-label col-form-label d-block">Grupo <small class="text-muted fw-normal">(nivel más específico)</small></label>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_grupo_id">Columna ID</label>
                                <input type="text" class="form-control" id="col_grupo_id" name="col_grupo_id"
                                       value="{{ $config['col_grupo_id'] }}" placeholder="IDGRUPO_CL">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_grupo_name">Columna nombre</label>
                                <input type="text" class="form-control" id="col_grupo_name" name="col_grupo_name"
                                       value="{{ $config['col_grupo_name'] }}" placeholder="DESC_GRUPO_CL">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="control-label col-form-label" for="col_desc_completa">Columna descripción completa <span class="text-muted">(opcional)</span></label>
                                <input type="text" class="form-control" id="col_desc_completa" name="col_desc_completa"
                                       value="{{ $config['col_desc_completa'] }}" placeholder="DESC_COMPLETA">
                            </div>
                        </div>

                        <div id="save-feedback" class="d-none mt-2"></div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" id="save-btn" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                            Guardar
                        </button>
                        <a href="{{ route('settings.suppliers.categories.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                            Volver
                        </a>
                    </div>

                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Columnas del Excel ERP</h6>
                    <table class="table table-sm small mb-0">
                        <thead class="table-light">
                            <tr><th>Columna</th><th>Nivel</th></tr>
                        </thead>
                        <tbody class="text-muted">
                            <tr><td><code>IDGRUPO_CL</code></td><td>ID Grupo</td></tr>
                            <tr><td><code>DESC_GRUPO_CL</code></td><td>Nombre Grupo</td></tr>
                            <tr><td><code>IDSUBFAMILIA_CL</code></td><td>ID Subfamilia</td></tr>
                            <tr><td><code>DESC_SUBFAMILIA_CL</code></td><td>Nombre Subfamilia</td></tr>
                            <tr><td><code>IDFAMILIA_CL</code></td><td>ID Familia</td></tr>
                            <tr><td><code>DESC_FAMILIA_CL</code></td><td>Nombre Familia</td></tr>
                            <tr><td><code>IDCATEGORIA_CL</code></td><td>ID Categoría</td></tr>
                            <tr><td><code>DESC_CATEGORIA_CL</code></td><td>Nombre Categoría</td></tr>
                            <tr><td><code>IDDEPORTE_CL</code></td><td>ID Deporte</td></tr>
                            <tr><td><code>DESC_DEPORTE_CL</code></td><td>Nombre Deporte</td></tr>
                            <tr><td><code>DESC_COMPLETA</code></td><td>Ruta completa</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Jerarquía</h6>
                    <table class="table table-sm small mb-0">
                        <thead class="table-light">
                            <tr><th>Nivel</th><th>Ejemplo</th></tr>
                        </thead>
                        <tbody class="text-muted">
                            <tr><td>Deporte</td><td>GOLF</td></tr>
                            <tr><td>Categoría</td><td>ACCESORIOS GOLF</td></tr>
                            <tr><td>Familia</td><td>FUNDAS</td></tr>
                            <tr><td>Subfamilia</td><td>HIERROS</td></tr>
                            <tr><td>Grupo</td><td>FUNDAS HIERROS</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Prefijo</h6>
                    <p class="small text-muted mb-0">
                        Los nombres en el Excel incluyen el prefijo <code>T.</code>
                        (ej. <code>T.GOLF</code>). Al configurarlo se importará como <code>GOLF</code>.
                        Deja vacío para importar el nombre tal cual.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    $(function () {
        $('#import_level').select2({ width: '100%' });
    });

    $('#excel-config-form').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#save-btn');
        $btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: '{{ route("settings.suppliers.categories.excel.config.save") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                $('#save-feedback')
                    .removeClass('d-none alert-danger')
                    .addClass('alert alert-success')
                    .html(res.message);
                toastr.success(res.message, '', { positionClass: 'toast-bottom-right' });
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message ?? 'Error al guardar la configuración';
                $('#save-feedback')
                    .removeClass('d-none alert-success')
                    .addClass('alert alert-danger')
                    .html(msg);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Guardar');
            }
        });
    });
</script>
@endpush
