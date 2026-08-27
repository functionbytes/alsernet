@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    @include('core::components.card', ['title' => $pageTitle])

    <div class="row g-3">

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Fuentes personalizadas</h5>
                    <small class="text-muted">
                        Las fuentes subidas aqui se embeben en el PDF y aparecen en el desplegable de fuentes del editor de plantillas.
                    </small>
                </div>

                <div class="card-body">
                    @include('core::components.alerts')

                    @if($fonts->isEmpty())
                        <p class="text-muted mb-0">
                            Todavia no hay fuentes personalizadas. Las plantillas usan Helvetica, Times y Courier.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Familia</th>
                                        <th>Variante</th>
                                        <th>Archivo</th>
                                        <th>Subida</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fonts as $font)
                                        <tr>
                                            <td class="fw-semibold">{{ $font->name }}</td>
                                            <td><code>{{ $font->family }}</code></td>
                                            <td>{{ $font->variantLabel() }}</td>
                                            <td class="text-muted">{{ basename($font->file_path) }}</td>
                                            <td class="text-muted">{{ $font->created_at?->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button type="button" class="dropdown-item delete-font-btn"
                                                                    data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                                    data-title="Eliminar fuente: {{ $font->name }} ({{ $font->variantLabel() }})"
                                                                    data-url="{{ route('settings.pricelabels.fonts.destroy', $font) }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="card-footer border-top">
                    <h6 class="fw-bold mb-3">Subir fuente</h6>
                    <p class="small text-muted mb-3">
                        Sube un archivo TTF u OTF por cada variante. Para que negrita y cursiva se vean correctamente en el PDF,
                        sube el archivo de cada variante con el mismo nombre y cambiando el grosor o el estilo.
                    </p>

                    <form action="{{ route('settings.pricelabels.fonts.store') }}" method="POST"
                          enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf

                        <div class="col-12 col-md-4">
                            <label class="form-label">Nombre de la fuente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{ old('name') }}" placeholder="ej: Montserrat" required>
                            @error('name')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                            @error('family')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label">Grosor</label>
                            <select class="form-select" name="weight">
                                <option value="normal" {{ old('weight') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="bold" {{ old('weight') === 'bold' ? 'selected' : '' }}>Negrita</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label">Estilo</label>
                            <select class="form-select" name="style">
                                <option value="normal" {{ old('style') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="italic" {{ old('style') === 'italic' ? 'selected' : '' }}>Cursiva</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label">Archivo TTF/OTF <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('font_file') is-invalid @enderror"
                                   name="font_file" accept=".ttf,.otf" required>
                            @error('font_file')
                                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Subir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.delete-font-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
});
</script>
@endpush
