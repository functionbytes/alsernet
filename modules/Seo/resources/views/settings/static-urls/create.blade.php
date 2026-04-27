@extends('layouts.theme')

@section('title', 'Nueva URL estática')

@section('content')
    @include('core::components.card', ['title' => 'Nueva URL estática'])

    <div class="row">
        {{-- Formulario principal --}}
        <div class="col-lg-8">
            <div class="card w-100">
                <form id="formStaticUrl" action="{{ route('settings.seo.static-urls.store') }}" method="POST" novalidate>
                    @csrf

                    <div class="card-body">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Nueva URL estática</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-0">
                            Agrega una URL estática para incluirla en el sitemap XML.
                        </p>

                        @include('core::components.alerts')

                        <div class="row">
                            {{-- URL --}}
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        URL
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('url') is-invalid @enderror"
                                           id="urlInput"
                                           name="url"
                                           value="{{ old('url') }}"
                                           placeholder="https://tudominio.com/pagina"
                                           maxlength="2048"
                                           required>
                                    @error('url')
                                        <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                    @else
                                        <small class="form-text text-muted">URL completa incluyendo https://</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- Prioridad --}}
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Prioridad
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('priority') is-invalid @enderror select2"
                                            id="priority"
                                            name="priority"
                                            required>
                                        <option value="1.0" {{ old('priority', '0.5') == '1.0' ? 'selected' : '' }}>1.0 — Muy alta</option>
                                        <option value="0.8" {{ old('priority', '0.5') == '0.8' ? 'selected' : '' }}>0.8 — Alta</option>
                                        <option value="0.5" {{ old('priority', '0.5') == '0.5' ? 'selected' : '' }}>0.5 — Media</option>
                                        <option value="0.3" {{ old('priority', '0.5') == '0.3' ? 'selected' : '' }}>0.3 — Baja</option>
                                        <option value="0.1" {{ old('priority', '0.5') == '0.1' ? 'selected' : '' }}>0.1 — Muy baja</option>
                                    </select>
                                    @error('priority')
                                        <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                    @else
                                        <small class="form-text text-muted">Importancia relativa respecto a otras URLs (0.0 - 1.0)</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- Frecuencia de cambio --}}
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">
                                        Frecuencia de cambio
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('changefreq') is-invalid @enderror select2"
                                            id="changefreq"
                                            name="changefreq"
                                            required>
                                        <option value="always"  {{ old('changefreq', 'weekly') == 'always'  ? 'selected' : '' }}>always — Siempre</option>
                                        <option value="hourly"  {{ old('changefreq', 'weekly') == 'hourly'  ? 'selected' : '' }}>hourly — Por hora</option>
                                        <option value="daily"   {{ old('changefreq', 'weekly') == 'daily'   ? 'selected' : '' }}>daily — Diario</option>
                                        <option value="weekly"  {{ old('changefreq', 'weekly') == 'weekly'  ? 'selected' : '' }}>weekly — Semanal</option>
                                        <option value="monthly" {{ old('changefreq', 'weekly') == 'monthly' ? 'selected' : '' }}>monthly — Mensual</option>
                                        <option value="yearly"  {{ old('changefreq', 'weekly') == 'yearly'  ? 'selected' : '' }}>yearly — Anual</option>
                                        <option value="never"   {{ old('changefreq', 'weekly') == 'never'   ? 'selected' : '' }}>never — Nunca</option>
                                    </select>
                                    @error('changefreq')
                                        <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                    @else
                                        <small class="form-text text-muted">Con qué frecuencia cambia el contenido de esta página</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- Notas --}}
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Notas</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                              id="notes"
                                              name="notes"
                                              rows="3"
                                              placeholder="Descripción opcional para identificar esta URL...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                    @else
                                        <small class="form-text text-muted">Campo opcional, solo para referencia interna</small>
                                    @enderror
                                </div>
                            </div>

                            {{-- Estado --}}
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <select class="form-select select2 @error('is_active') is-invalid @enderror"
                                            id="isActive"
                                            name="is_active">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('is_active', '1') == '0' ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                    @error('is_active')
                                        <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                    @else
                                        <small class="form-text text-muted">Solo las URLs activas se incluyen en el sitemap</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer gap-2">
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            Guardar
                        </button>
                        <a href="{{ route('settings.seo.static-urls.index') }}"
                           class="btn btn-light border w-100 py-2">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        Guía rápida
                    </h5>
                    <p class="card-text text-muted">
                        Las URLs estáticas son páginas que no se generan dinámicamente pero deben aparecer en el sitemap para que los motores de búsqueda las indexen.
                    </p>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">Prioridad</h6>
                    <div class="mb-2">
                        <span class="badge bg-info-subtle text-info mb-1">1.0 — Muy alta</span>
                        <p class="text-muted mb-0">Para la página principal y secciones clave.</p>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-info-subtle text-info mb-1">0.8 — Alta</span>
                        <p class="text-muted mb-0">Para secciones importantes como categorías.</p>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-info-subtle text-info mb-1">0.5 — Media</span>
                        <p class="text-muted mb-0">Valor por defecto, adecuado para la mayoría.</p>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-info-subtle text-info mb-1">0.3 — Baja</span>
                        <p class="text-muted mb-0">Páginas secundarias con poco tráfico.</p>
                    </div>
                    <div class="mb-0">
                        <span class="badge bg-light text-dark mb-1">0.1 — Muy baja</span>
                        <p class="text-muted mb-0">Páginas de escasa relevancia SEO.</p>
                    </div>
                </div>
                <hr>
                <div class="card-body">
                    <h6 class="card-title mb-3">Frecuencia de cambio</h6>
                    <ul class="text-muted mb-0">
                        <li class="mb-1"><strong>always</strong> — El contenido cambia en cada visita</li>
                        <li class="mb-1"><strong>hourly</strong> — Cambia cada hora</li>
                        <li class="mb-1"><strong>daily</strong> — Cambia a diario</li>
                        <li class="mb-1"><strong>weekly</strong> — Cambia semanalmente (recomendado)</li>
                        <li class="mb-1"><strong>monthly</strong> — Cambia mensualmente</li>
                        <li class="mb-1"><strong>yearly</strong> — Cambia anualmente</li>
                        <li class="mb-0"><strong>never</strong> — Contenido archivado, no cambia</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#formStaticUrl').validate({
        rules: {
            url: { required: true, maxlength: 2048 },
            priority: { required: true },
            changefreq: { required: true }
        },
        messages: {
            url: {
                required: 'La URL es obligatoria',
                maxlength: 'Máximo 2048 caracteres'
            },
            priority: { required: 'Selecciona una prioridad' },
            changefreq: { required: 'Selecciona la frecuencia de cambio' }
        },
        errorClass: 'error',
        highlight: function(element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        errorPlacement: function(error, element) {
            error.addClass('field-validation-error').insertAfter(element);
        },
        submitHandler: function(form) {
            form.submit();
        }
    });
});
</script>
@endpush
