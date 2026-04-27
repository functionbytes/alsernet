@extends('layouts.theme')

@section('title', 'Detalle meta SEO')

@section('content')

    @include('core::components.card', ['title' => 'Meta SEO'])

    @include('core::components.alerts')

    <div class="row g-4 align-items-start">

        {{-- Columna izquierda --}}
        <div class="col-lg-8">

            <div class="card">

                {{-- Datos principales --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Datos principales</h6>
                    <p class="text-muted mb-4">Título, descripción, palabras clave y directivas de rastreo para motores de búsqueda.</p>

                    <div class="row g-0">
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Titulo SEO</small>
                                <span class="text-end fw-semibold">
                                    {{ $meta->title ?: '' }}<em class="text-muted fw-normal">{{ $meta->title ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Descripcion</small>
                                <span class="text-end">
                                    {{ $meta->description ?: '' }}<em class="text-muted">{{ $meta->description ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Palabras clave</small>
                                <span class="text-end">
                                    {{ $meta->keywords ?: '' }}<em class="text-muted">{{ $meta->keywords ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Robots</small>
                                @php
                                    $indexable  = $meta->isIndexable();
                                    $followable = $meta->isFollowable();
                                    if ($indexable && $followable) {
                                        $robotsBg = '#f5f6f8'; $robotsColor = '#2a3547';
                                    } elseif (!$indexable) {
                                        $robotsBg = '#fce8e8'; $robotsColor = '#b10100';
                                    } else {
                                        $robotsBg = '#f5f6f8'; $robotsColor = '#5a6a85';
                                    }
                                @endphp
                                <span class="badge rounded-pill px-3 py-1"
                                      style="background:{{ $robotsBg }};color:{{ $robotsColor }};">
                                    {{ $meta->robots ?: 'index,follow' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">URL canónica</small>
                                <span class="text-end text-break">
                                    {{ $meta->canonical_url ?: '' }}<em class="text-muted">{{ $meta->canonical_url ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-0">

                {{-- Open Graph --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Open Graph</h6>
                    <p class="text-muted mb-4">Controla cómo se muestra esta página al compartirla en plataformas externas. Afecta título, descripción e imagen de previsualización.</p>

                    <div class="row g-0">
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Titulo OG</small>
                                <span class="text-end">
                                    {{ $meta->og_title ?: '' }}<em class="text-muted">{{ $meta->og_title ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Descripcion OG</small>
                                <span class="text-end">
                                    {{ $meta->og_description ?: '' }}<em class="text-muted">{{ $meta->og_description ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Tipo OG</small>
                                <span class="text-end">
                                    {{ $meta->og_type ?: '' }}<em class="text-muted">{{ $meta->og_type ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Imagen OG</small>
                                <div class="text-end">
                                    @if($meta->og_image)
                                        <span class="d-block small text-break mb-2">{{ $meta->og_image }}</span>
                                        <img src="{{ $meta->og_image }}" class="img-fluid rounded" style="max-height:100px;" alt="Open Graph Image">
                                    @else
                                        <em class="text-muted">No definido</em>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-0">

                {{-- Twitter Card --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Twitter Card</h6>
                    <p class="text-muted mb-4">Define el formato de previsualización cuando el enlace se comparte en Twitter / X. El tipo <code>summary_large_image</code> muestra una imagen destacada.</p>

                    <div class="row g-0">
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Tipo de tarjeta</small>
                                <span class="text-end">
                                    {{ $meta->twitter_card ?: '' }}<em class="text-muted">{{ $meta->twitter_card ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Titulo</small>
                                <span class="text-end">
                                    {{ $meta->twitter_title ?: '' }}<em class="text-muted">{{ $meta->twitter_title ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Descripcion</small>
                                <span class="text-end">
                                    {{ $meta->twitter_description ?: '' }}<em class="text-muted">{{ $meta->twitter_description ? '' : 'No definido' }}</em>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 py-2">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <small class="text-muted flex-shrink-0" style="min-width:130px;">Imagen</small>
                                <div class="text-end">
                                    @if($meta->twitter_image)
                                        <span class="d-block small text-break mb-2">{{ $meta->twitter_image }}</span>
                                        <img src="{{ $meta->twitter_image }}" class="img-fluid rounded" style="max-height:100px;" alt="Twitter Card Image">
                                    @else
                                        <em class="text-muted">No definido</em>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-0">

                {{-- Vista previa en Google --}}
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-1">Vista previa en Google</h6>
                    <p class="text-muted mb-4">Simulación de cómo aparece esta página en los resultados de búsqueda orgánica. El título no debe superar 60 caracteres y la descripción 160.</p>

                    <div class="rounded border p-3" style="background:#f8f9fa;font-family:Arial,sans-serif;">
                        <div style="color:#5f6368;font-size:12px;line-height:1.4;margin-bottom:3px;">
                            {{ $meta->canonical_url ?? url('/') }}
                        </div>
                        <div style="color:#1a0dab;font-size:18px;line-height:1.3;margin-bottom:4px;">
                            {{ $meta->display_title ?? 'Sin título' }}
                        </div>
                        <div style="color:#4d5156;font-size:14px;line-height:1.58;">
                            {{ Str::limit($meta->display_description ?? 'Sin descripción', 160) }}
                        </div>
                    </div>
                </div>

            </div>

        </div>

        {{-- Columna derecha --}}
        <div class="col-lg-4">

            {{-- Acciones --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-1">Acciones</h6>
                    <p class="text-muted mb-3">Modifica o elimina la configuración de meta SEO de este recurso.</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.seo.metas.edit', $meta) }}" class="btn btn-primary w-100">
                            Editar meta
                        </a>
                        <a href="{{ route('settings.seo.metas.index') }}" class="btn btn-outline-secondary w-100">
                            Volver al listado
                        </a>
                        <button type="button" class="btn btn-outline-danger w-100 delete-btn"
                                data-bs-toggle="modal" data-bs-target="#delete-modal">
                            Eliminar meta
                        </button>
                    </div>
                </div>
            </div>

            {{-- Modelo asociado --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Modelo asociado</h6>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <small class="text-muted">Tipo</small>
                        <span class="fw-semibold">{{ $meta->short_type }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <small class="text-muted">ID</small>
                        <span class="fw-semibold">#{{ $meta->seoable_id }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                        <small class="text-muted">Nombre</small>
                        <span class="fw-semibold text-end ms-3">{{ $meta->seoable?->title ?? $meta->seoable?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                        <small class="text-muted">Creado</small>
                        <div class="text-end">
                            <span class="d-block fw-semibold">{{ $meta->created_at->format('d/m/Y H:i') }}</span>
                            <small class="text-muted">{{ $meta->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-start py-2">
                        <small class="text-muted">Actualizado</small>
                        <div class="text-end">
                            <span class="d-block fw-semibold">{{ $meta->updated_at->format('d/m/Y H:i') }}</span>
                            <small class="text-muted">{{ $meta->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Salud SEO --}}
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Salud SEO</h6>
                </div>
                <div class="card-body">
                    @php
                        $checks = [
                            ['label' => 'Titulo SEO',              'ok' => (bool) $meta->title],
                            ['label' => 'Descripcion',             'ok' => (bool) $meta->description],
                            ['label' => 'Open Graph configurado',  'ok' => (bool) ($meta->og_title && $meta->og_description)],
                            ['label' => 'Twitter Card configurado','ok' => (bool) ($meta->twitter_card && $meta->twitter_title)],
                            ['label' => 'Indexable',               'ok' => $meta->isIndexable()],
                        ];
                        $passed = collect($checks)->where('ok', true)->count();
                        $total  = count($checks);
                        $pct    = round($passed / $total * 100);
                    @endphp

                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">{{ $passed }}/{{ $total }} elementos</small>
                        <small class="fw-semibold" style="color:#b10100;">{{ $pct }}%</small>
                    </div>
                    <div class="rounded mb-3" style="height:5px;background:#f5f6f8;">
                        <div class="rounded" style="height:5px;width:{{ $pct }}%;background:#b10100;transition:width .4s;"></div>
                    </div>

                    <ul class="list-unstyled mb-0">
                        @foreach($checks as $check)
                        <li class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            @if($check['ok'])
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                      style="width:20px;height:20px;background:#f5f6f8;">
                                    <i class="fas fa-check" style="font-size:0.5rem;color:#2a3547;"></i>
                                </span>
                                <small class="text-dark">{{ $check['label'] }}</small>
                            @else
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                      style="width:20px;height:20px;background:#fce8e8;">
                                    <i class="fas fa-times" style="font-size:0.5rem;color:#b10100;"></i>
                                </span>
                                <small class="text-muted">{{ $check['label'] }}</small>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

        </div>

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-btn').on('click', function() {
        $('#delete-modal .modal-title').text('Eliminar meta SEO');
        $('#delete-form').attr('action', '{{ route('settings.seo.metas.destroy', $meta) }}');
    });
});
</script>
@endpush
