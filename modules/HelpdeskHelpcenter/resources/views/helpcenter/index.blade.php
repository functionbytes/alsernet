@extends('layouts.theme')

@section('title', 'Centro de ayuda — Organizar')

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Organización'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Breadcrumb Navigation --}}
        <div class="card mb-3">
            <div class="card-body p-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" id="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="#" class="text-decoration-none" data-level="categories">
                                <i class="far fa-home me-1"></i> Categorías
                            </a>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        {{-- Main Content Card --}}
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold" id="content-title">
                            <i class="far fa-folder text-muted me-2"></i>
                            <span id="title-text">Categorías</span>
                            <span id="title-count" class="badge bg-primary-subtle text-primary ms-2">0</span>
                        </h5>
                        <p class="small mb-0 text-muted">Arrastra los elementos para reordenarlos</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="addItemBtn">
                        <i class="fas fa-plus me-1"></i>
                        <span id="addBtnText">Nueva categoría</span>
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Loading State -->
                <div id="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted mt-2">Cargando contenido...</p>
                </div>

                {{-- Empty State --}}
                <div id="empty-state" class="text-center py-5 d-none">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="far fa-folder-open fs-7"></i>
                        </div>
                        <h6 class="mb-1" id="empty-title">No hay categorías</h6>
                        <p class="text-muted mb-3" id="empty-description">
                            Crea tu primera categoría para comenzar
                        </p>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtnEmpty">
                            <i class="fas fa-plus me-1"></i>
                            <span id="addBtnEmptyText">Nueva categoría</span>
                        </button>
                    </div>
                </div>

                <!-- Items List -->
                <div id="items-container" class="d-none">
                    <div id="sortable-list" class="list-group"></div>
                </div>
            </div>
        </div>
    </div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="itemForm">
                <div class="modal-body">
                    <input type="hidden" id="itemId">
                    <input type="hidden" id="parentId">
                    <input type="hidden" id="isSection">

                    <div class="mb-3">
                        <label for="itemName" class="form-label fw-semibold">Nombre *</label>
                        <input type="text" class="form-control" id="itemName" required>
                    </div>

                    <div class="mb-3">
                        <label for="itemDescription" class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" id="itemDescription" rows="3"></textarea>
                    </div>

                    <div class="mb-3" id="imageField">
                        <label for="itemImage" class="form-label fw-semibold">URL de Imagen</label>
                        <input type="text" class="form-control" id="itemImage" placeholder="https://...">
                        <small class="text-muted">URL de la imagen para la categoría</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('modules/helpdeskhelpcenter/css/helpcenter-manager.css') }}?v={{ filemtime(public_path('modules/helpdeskhelpcenter/css/helpcenter-manager.css')) }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
{{-- Bootstrap minimo de datos (URLs de route()) que helpcenter-manager.js no
     puede resolver por su cuenta — toda la logica vive ahi. Las rutas con
     un ID variable (categoryDelete, sections, ...) llevan un placeholder
     "__ID__" que el JS sustituye en tiempo de ejecución. --}}
@php
    $hcApi = [
        'categories' => route('manager.helpcenter.api.categories'),
        'categoriesCreate' => route('manager.helpcenter.api.categories.create'),
        'categoriesReorder' => route('manager.helpcenter.api.categories.reorder'),
        'categoryDeleteTpl' => route('manager.helpcenter.api.categories.delete', ['id' => '__ID__']),
        'sectionsTpl' => route('manager.helpcenter.api.sections', ['id' => '__ID__']),
        'articlesTpl' => route('manager.helpcenter.api.articles', ['id' => '__ID__']),
        'articlesCreate' => route('manager.helpcenter.api.articles.create'),
        'articlesReorderTpl' => route('manager.helpcenter.api.articles.reorder', ['id' => '__ID__']),
        'articleDeleteTpl' => route('manager.helpcenter.api.articles.delete', ['id' => '__ID__']),
    ];
@endphp
<script>window.HelpcenterApi = @json($hcApi);</script>
<script src="{{ asset('modules/helpdeskhelpcenter/js/helpcenter-manager.js') }}?v={{ filemtime(public_path('modules/helpdeskhelpcenter/js/helpcenter-manager.js')) }}"></script>
@endpush
