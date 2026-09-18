@extends('layouts.theme')

@section('title', 'Etiquetas sociales')

@include('helpdesksocial::partials.admin-css')

@section('page_header')
    @include('core::components.card', ['title' => 'Etiquetas sociales'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Etiquetas sociales</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="resetTagForm()">
            <i class="fas fa-plus me-2"></i>Nueva etiqueta
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Color</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Usos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tags as $tag)
                        <tr data-tag-id="{{ $tag->id }}">
                            <td>
                                <span class="badge hso-tag-badge" data-tag-color="{{ $tag->color }}">
                                    {{ $tag->name }}
                                </span>
                            </td>
                            <td>
                                <span class="d-inline-block rounded-circle hso-tag-dot" data-tag-color="{{ $tag->color }}"></span>
                                <small class="text-muted ms-1">{{ $tag->color }}</small>
                            </td>
                            <td>{{ $tag->description ?? '-' }}</td>
                            <td>
                                @if($tag->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td>{{ $tag->comments()->count() }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editTag({{ $tag->id }}, '{{ $tag->name }}', '{{ $tag->slug }}', '{{ $tag->color }}', '{{ $tag->description }}', {{ $tag->is_active ? 1 : 0 }})" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-tags fa-2x mb-2 d-block"></i>
                                No hay etiquetas configuradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tags->links() }}
        </div>
    </div>

<div class="modal fade" id="tagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="tagForm" method="POST" action=""
                  data-store-url="{{ route('helpdesksocial.tags.store') }}"
                  data-update-url-template="{{ route('helpdesksocial.tags.update', '__ID__') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="tagModalLabel">Nueva etiqueta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        @include('core::components.slug-field', [
                            'value' => '',
                            'from' => '#tagForm input[name=name]',
                            'required' => true,
                        ])
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        @include('core::components.color-field', [
                            'name' => 'color',
                            'value' => '#90bb13',
                            'preview' => 'Etiqueta',
                            'previewFrom' => 'input[name=name]',
                        ])
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" checked id="tagIsActive">
                        <label class="form-check-label" for="tagIsActive">Activa</label>
                    </div>
                </div>
                <div class="modal-footer d-block">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('modules/helpdesksocial/js/social-tags-index.js') }}?v={{ filemtime(public_path('modules/helpdesksocial/js/social-tags-index.js')) }}"></script>
@endpush
