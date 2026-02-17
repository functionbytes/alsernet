@extends('layouts.theme')

@section('title', 'Detalle meta SEO')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Meta SEO</h5>
                        <p class="mb-0 text-muted small">{{ class_basename($meta->seoable_type) }} #{{ $meta->seoable_id }}</p>
                    </div>
                    <a href="{{ route('setting.seo.metas.edit', $meta) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Titulo SEO</small>
                        <span>{{ $meta->title ?: '' }}<em class="text-muted">{{ $meta->title ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Descripcion</small>
                        <span>{{ $meta->description ?: '' }}<em class="text-muted">{{ $meta->description ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Palabras clave</small>
                        <span>{{ $meta->keywords ?: '' }}<em class="text-muted">{{ $meta->keywords ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Robots</small>
                        @php
                            $indexable = $meta->isIndexable();
                            $followable = $meta->isFollowable();
                            $badgeClass = $indexable && $followable ? 'bg-success-subtle text-success' : (!$indexable ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning');
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $meta->robots ?: 'index,follow' }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">URL canonica</small>
                        <span>{{ $meta->canonical_url ?: '' }}<em class="text-muted">{{ $meta->canonical_url ? '' : 'No definido' }}</em></span>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fab fa-facebook me-2"></i> Open Graph</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Titulo OG</small>
                        <span>{{ $meta->og_title ?: '' }}<em class="text-muted">{{ $meta->og_title ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Descripcion OG</small>
                        <span>{{ $meta->og_description ?: '' }}<em class="text-muted">{{ $meta->og_description ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Tipo OG</small>
                        <span>{{ $meta->og_type ?: '' }}<em class="text-muted">{{ $meta->og_type ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Imagen OG</small>
                        @if($meta->og_image)
                            <div>
                                <span class="d-block mb-2">{{ $meta->og_image }}</span>
                                <img src="{{ $meta->og_image }}" class="img-fluid rounded mt-2" style="max-height: 120px" alt="Open Graph Image">
                            </div>
                        @else
                            <em class="text-muted">No definido</em>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fab fa-twitter me-2"></i> Twitter Card</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Tipo de tarjeta</small>
                        <span>{{ $meta->twitter_card ?: '' }}<em class="text-muted">{{ $meta->twitter_card ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Titulo Twitter</small>
                        <span>{{ $meta->twitter_title ?: '' }}<em class="text-muted">{{ $meta->twitter_title ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Descripcion Twitter</small>
                        <span>{{ $meta->twitter_description ?: '' }}<em class="text-muted">{{ $meta->twitter_description ? '' : 'No definido' }}</em></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Imagen Twitter</small>
                        @if($meta->twitter_image)
                            <div>
                                <span class="d-block mb-2">{{ $meta->twitter_image }}</span>
                                <img src="{{ $meta->twitter_image }}" class="img-fluid rounded mt-2" style="max-height: 120px" alt="Twitter Card Image">
                            </div>
                        @else
                            <em class="text-muted">No definido</em>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fab fa-google me-2"></i> Vista previa en Google</h5>
                </div>
                <div class="card-body">
                    <div class="p-3 border rounded bg-white">
                        <div class="small" style="color: #202124; font-size: 14px;">{{ $meta->canonical_url ?? url('/') }}</div>
                        <div style="color: #1a0dab; font-size: 20px;" class="mb-1">{{ $meta->display_title ?? 'Sin titulo' }}</div>
                        <div style="color: #4d5156; font-size: 14px; line-height: 1.58;">{{ Str::limit($meta->display_description ?? 'Sin descripcion', 160) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-cogs me-2"></i> Acciones</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('setting.seo.metas.edit', $meta) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Editar meta
                        </a>
                        <a href="{{ route('setting.seo.metas.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver al listado
                        </a>
                        <hr class="my-2">
                        <button type="button" class="btn btn-outline-danger delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#delete-modal">
                            <i class="fas fa-trash me-1"></i> Eliminar meta
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-link me-2"></i> Modelo asociado</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Tipo</small>
                        <span>{{ $meta->short_type }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">ID</small>
                        <span>#{{ $meta->seoable_id }}</span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Nombre</small>
                        <span>{{ $meta->seoable?->title ?? $meta->seoable?->name ?? 'N/A' }}</span>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <small class="text-muted d-block">Creado</small>
                        <strong>{{ $meta->created_at->format('d/m/Y H:i') }}</strong>
                        <small class="text-muted d-block">({{ $meta->created_at->diffForHumans() }})</small>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Actualizado</small>
                        <strong>{{ $meta->updated_at->format('d/m/Y H:i') }}</strong>
                        <small class="text-muted d-block">({{ $meta->updated_at->diffForHumans() }})</small>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-heartbeat me-2"></i> Salud SEO</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-{{ $meta->title ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>
                            Titulo SEO
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-{{ $meta->description ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>
                            Descripcion
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-{{ ($meta->og_title && $meta->og_description) ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>
                            Open Graph configurado
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-{{ ($meta->twitter_card && $meta->twitter_title) ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>
                            Twitter Card configurado
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-{{ $meta->isIndexable() ? 'check-circle text-success' : 'times-circle text-danger' }} me-2"></i>
                            Indexable
                        </li>
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
        $('#delete-form').attr('action', '{{ route('setting.seo.metas.destroy', $meta) }}');
    });
});
</script>
@endpush
