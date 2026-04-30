@extends('layouts.theme')

@section('title', 'Gestor de Medios')

@push('styles')
<link rel="stylesheet" href="{{ asset('modules/Media/css/media-manager.css') }}">
<style>
    /* Media Manager Custom Styles */

    .stat-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: #b10100, var(--stat-color));
    }

    .stat-card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        transform: translateY(-4px);
    }

    .stat-card.folders { --stat-color: #b10100; }
    .stat-card.files { --stat-color: #13C672; }
    .stat-card.storage { --stat-color: #FEC90F; }

    .media-card {
        transition: all 0.3s ease;
        height: 100%;
        border: 1px solid #f0f0f0;
        border-radius: 10px;
    }

    .media-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        transform: translateY(-4px);
        border-color: #b10100;
    }

    .folder-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .folder-card:hover {
        box-shadow: 0 8px 24px rgba(93, 135, 255, 0.15) !important;
        transform: translateY(-4px);
        border-color: #b10100 !important;
    }

    .folder-preview {
        min-height: 120px;
        border-top: 1px solid #f0f0f0;
        padding-top: 1rem;
    }

    /* File Preview Area */
    .file-preview-area {
        min-height: 180px;
        border-bottom: 1px solid #f0f0f0;
        overflow: hidden;
        border-radius: 10px 10px 0 0;
    }

    .file-image-preview {
        height: 180px;
        width: 100%;
        overflow: hidden;
        position: relative;
        background: #f8f9fa;
    }

    .file-image-preview img {
        transition: transform 0.3s ease;
    }

    .media-card:hover .file-image-preview img {
        transform: scale(1.05);
    }

    .file-icon-preview {
        min-height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: all 0.3s ease;
    }

    .file-icon-preview i {
        transition: transform 0.3s ease;
        opacity: 0.9;
    }

    .media-card:hover .file-icon-preview i {
        transform: scale(1.1);
        opacity: 1;
    }

    /* Type Badge */
    .file-type-badge {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(93, 135, 255, 0.95);
        color: white;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .file-type-badge i {
        font-size: 0.875rem;
    }

    /* Gradient Backgrounds */
    .bg-gradient-danger {
        background: #b10100;
    }

    .bg-gradient-success {
        background: #b10100;
    }

    .bg-gradient-info {
        background: #b10100;
    }

    .bg-gradient-warning {
        background: #b10100;
    }

    .bg-gradient-purple {
        background: #b10100;
    }

    .bg-gradient-dark {
        background: #b10100;
    }

    .bg-gradient-secondary {
        background: #b10100;
    }

    .text-purple {
        color: #cc5de8;
    }

    .file-options-menu {
        position: absolute;
        top: 8px;
        right: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 10;
    }

    .media-card:hover .file-options-menu {
        opacity: 1;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e8e8e8;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.875rem;
    }

    .action-btn:hover {
        background: #f0f4ff;
        border-color: #b10100;
        color: #b10100;
        box-shadow: 0 2px 8px rgba(93, 135, 255, 0.15);
    }

    .upload-zone {
        border: 2px dashed #b10100;
        border-radius: 12px;
        background: #b10100;
        transition: all 0.3s ease;
    }

    .upload-zone:hover {
        border-color: #3E5BDB;
        background: #b10100;
        box-shadow: 0 4px 16px rgba(93, 135, 255, 0.12);
    }

    .upload-zone.drag-over {
        border-color: #3E5BDB;
        background: #b10100;
        box-shadow: 0 6px 20px rgba(93, 135, 255, 0.2);
    }

    /* Upload Zone Modern */
    .upload-zone-modern {
        background: #b10100;
        border: 2px dashed #e0e7ff;
        transition: all 0.3s ease;
    }

    .upload-zone-modern:hover {
        border-color: #b10100;
        background: #b10100;
        box-shadow: 0 4px 16px rgba(93, 135, 255, 0.1);
    }

    .upload-zone-modern.drag-active {
        border-color: #b10100;
        background: #b10100;
        box-shadow: 0 6px 20px rgba(93, 135, 255, 0.2);
        transform: scale(1.01);
    }

    .upload-icon-wrapper {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #b10100;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(93, 135, 255, 0.3);
    }

    .upload-icon-wrapper i {
        font-size: 28px;
        color: white;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
    }

    .card-header {
        background: #b10100;
        border-color: #f0f0f0 !important;
    }

    .card-header .btn-group .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .card-header .btn-group .btn:hover {
        transform: translateY(-1px);
    }

    .card {
        border-color: #f0f0f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border-radius: 12px;
    }

    .card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .card.hover-img {
        overflow: visible !important;
    }

    .card.hover-img .position-relative:first-child {
        overflow: hidden;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    /* Folder Card Styles */
    .folder-icon-wrapper {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 2px solid;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .folder-card {
        transition: all 0.3s ease;
    }

    .folder-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    }

    .folder-file-item {
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        background: #f8f9fa;
        border-color: #e8e8e8 !important;
    }

    .folder-file-item:hover {
        background: #b10100;
        border-color: #b10100 !important;
        box-shadow: 0 4px 12px rgba(93, 135, 255, 0.15);
        transform: translateY(-2px);
    }

    .folder-file-item i {
        transition: all 0.3s ease;
    }

    .folder-file-item:hover i {
        color: #b10100 !important;
    }

    /* Dropdown for folder options */
    .dropstart .dropdown-menu {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .dropdown-item {
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background: #f0f4ff;
        color: #b10100;
    }

    .dropdown-item.text-danger:hover {
        background: #ffe8e8 !important;
    }

    /* Navigation Pills - Modernize Style */
    .user-profile-tab {
        border-bottom: 1px solid #e9ecef !important;
    }

    .user-profile-tab .nav-item {
        margin-bottom: -1px;
    }

    .user-profile-tab .nav-link {
        color: #5A6A85;
        font-weight: 500;
        padding: 1rem 1.5rem;
        border: none;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
        background: transparent !important;
    }

    .user-profile-tab .nav-link:hover {
        color: #b10100;
        border-bottom-color: rgba(93, 135, 255, 0.3);
        background: rgba(93, 135, 255, 0.05) !important;
    }

    .user-profile-tab .nav-link.active {
        color: #b10100;
        border-bottom-color: #b10100;
        background: transparent !important;
        font-weight: 600;
    }

    .user-profile-tab .nav-link i {
        color: inherit;
        transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
        .user-profile-tab .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }

        .user-profile-tab .nav-link i {
            font-size: 1.25rem !important;
        }
    }

    /* Context Menu (Right Click) */
    .context-menu {
        position: fixed;
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        min-width: 200px;
        padding: 0.5rem 0;
        display: none;
    }

    .context-menu.show {
        display: block;
    }

    .context-menu-item {
        padding: 0.6rem 1.2rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #495057;
        text-decoration: none;
        font-size: 0.875rem;
    }

    .context-menu-item:hover {
        background: #f0f4ff;
        color: #b10100;
    }

    .context-menu-item.danger:hover {
        background: #ffe8e8;
        color: #dc3545;
    }

    .context-menu-divider {
        height: 1px;
        background: #e9ecef;
        margin: 0.5rem 0;
    }

    .context-menu-item i {
        width: 16px;
        text-align: center;
    }

    /* Prevent text selection during right click */
    .no-select {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    /* Multiple Selection - Visual only (no checkboxes) */
    .selection-checkbox {
        display: none; /* Ocultar completamente los checkboxes */
    }

    .card.selected {
        border: 3px solid #b10100 !important;
        background: #b10100;
        box-shadow: 0 0 0 4px rgba(93, 135, 255, 0.15),
                    0 8px 24px rgba(93, 135, 255, 0.25) !important;
        transform: translateY(-2px);
    }

    .card.selected .card-body {
        background: transparent;
    }

    /* Círculo azul de fondo para el check */
    .card.selected::before {
        content: '';
        position: absolute;
        top: 12px;
        left: 12px;
        width: 28px;
        height: 28px;
        background: #b10100;
        border-radius: 50%;
        z-index: 20;
        box-shadow: 0 2px 8px rgba(93, 135, 255, 0.5);
        animation: checkmarkAppear 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Icono de check */
    .card.selected::after {
        content: '\2713'; /* Unicode checkmark */
        position: absolute;
        top: 12px;
        left: 12px;
        width: 28px;
        height: 28px;
        color: white;
        font-size: 16px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 21;
        pointer-events: none;
        animation: checkmarkAppear 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes checkmarkAppear {
        0% {
            transform: scale(0);
            opacity: 0;
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Animación suave para la selección */
    .media-card,
    .folder-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Modo de selección activo - Indicador visual en hover */
    .media-card:not(.selected):hover,
    .folder-card:not(.selected):hover {
        cursor: pointer;
        border-color: rgba(93, 135, 255, 0.4);
    }

    /* Indicador sutil de que se puede seleccionar (cuando hay selecciones activas) */
    body.selection-mode .card:not(.selected)::before {
        content: '';
        position: absolute;
        top: 12px;
        left: 12px;
        width: 28px;
        height: 28px;
        border: 2px solid rgba(93, 135, 255, 0.4);
        border-radius: 50%;
        z-index: 20;
        opacity: 0;
        transition: opacity 0.2s ease;
        background: white;
    }

    body.selection-mode .card:not(.selected):hover::before {
        opacity: 1;
    }

    /* Selection header styling */
    .card-header {
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    .card-header:has(.text-primary) {
        background: #b10100 !important;
        border-bottom: 2px solid #b10100 !important;
    }

    /* Enhanced sidebar-like navigation */
    .user-profile-tab {
        background: #b10100;
    }

    .user-profile-tab .nav-link {
        position: relative;
        padding: 1.25rem 2rem;
    }

    .user-profile-tab .nav-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        background: #b10100;
        border-radius: 0 4px 4px 0;
        transition: all 0.3s ease;
    }

    .user-profile-tab .nav-link.active::before {
        width: 4px;
        height: 60%;
    }

    .user-profile-tab .nav-link:hover:not(.active) {
        background: rgba(93, 135, 255, 0.05);
    }

    /* Badge for counts */
    .nav-link .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        margin-left: 0.5rem;
        font-weight: 600;
    }

    /* Responsive sidebar layout */
    @media (min-width: 1200px) {
        .media-layout-container {
            display: flex;
            gap: 0;
        }

        .media-sidebar {
            width: 280px;
            flex-shrink: 0;
            border-right: 1px solid #e9ecef;
        }

        .user-profile-tab {
            flex-direction: column !important;
            border-bottom: none !important;
            border-right: 1px solid #e9ecef;
        }

        .user-profile-tab .nav-link {
            justify-content: flex-start !important;
            border-radius: 0;
            border-bottom: none !important;
            padding: 1rem 1.5rem;
        }

        .user-profile-tab .nav-link span {
            display: block !important;
        }

        .media-content {
            flex: 1;
            min-width: 0;
        }
    }
</style>
@endpush

@if($pickerMode)
@push('css')
<style>
    aside.side-mini-panel, header.topbar { display: none !important; }
    .page-wrapper { margin-left: 0 !important; }
    .body-wrapper { padding-top: 0 !important; }
</style>
@endpush
@endif

@section('page_header')
    @include('core::components.card', ['title' => 'Gestor de Medios'])
@endsection

@section('content')
@if($pickerMode)
<div id="media-picker-banner" class="alert alert-primary mb-3 d-flex align-items-center gap-2 py-2">
    <i class="fas fa-hand-pointer"></i>
    <span>Haz clic en el archivo que deseas insertar.</span>
</div>
@endif
<div id="mediaManagerApp">


    <div v-if="loading" class="d-flex justify-content-center align-items-center media-loading-container">
        <div class="spinner-border text-primary media-loading-spinner" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div v-else>

        {{-- Separator --}}
        <hr class="my-0">

        {{-- Main Card --}}
        <div class="card">
            {{-- Header Section - Switches between normal header and selection toolbar --}}
            <div class="card-header p-4 border-bottom border-light">
                {{-- Normal Header --}}
                <div v-show="selectedItems.length === 0" class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Gestor de medios</h5>
                        <p class="small mb-0 text-muted">Organiza y gestiona todos tus archivos y carpetas en un solo lugar</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        {{-- Filesystem Selector --}}
                        <div class="d-flex align-items-center gap-2">
                            <select id="mediaDiskSelect" class="form-select media-disk-select">
                                @foreach($availableDisks as $disk)
                                <option value="{{ $disk['name'] }}">{{ $disk['label'] }} ({{ $disk['driver'] }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button v-if="currentView === 'all'" v-on:click="showNewFolderModal" class="btn btn-primary mb-1 w-100">
                            <i class="fas fa-folder-plus"></i>
                        </button>
                    </div>
                </div>

                {{-- Selection Toolbar (replaces header when items are selected) --}}
                <div v-if="selectedItems.length > 0" class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <button v-on:click="clearSelection" class="btn btn-sm btn-light rounded-circle media-cancel-btn" title="Cancelar selección">
                            <i class="fas fa-times"></i>
                        </button>
                        <div>
                            <h5 class="mb-0 fw-bold text-primary">
                                @{{ selectedItems.length }} elemento@{{ selectedItems.length !== 1 ? 's' : '' }} seleccionado@{{ selectedItems.length !== 1 ? 's' : '' }}
                            </h5>
                            <p class="small mb-0 text-muted">Acciones disponibles para la selección</p>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <template v-if="currentView === 'trash'">
                            <button v-on:click="bulkRestore" class="btn btn-success">
                               Restaurar
                            </button>
                        </template>
                        <template v-else>
                            <button v-on:click="bulkMove" class="btn btn-outline-primary">
                                Mover
                            </button>
                            <button v-on:click="bulkDownload" class="btn btn-outline-info" v-if="hasOnlyFiles">
                                Descargar
                            </button>
                            <button v-on:click="batchRename" class="btn btn-outline-secondary" v-if="hasOnlyFiles">
                                Renombrar por lotes
                            </button>
                            <button v-on:click="bulkDelete" class="btn btn-outline-danger">
                                Eliminar
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Responsive Layout Container --}}
            <div class="media-layout-container">
                {{-- Sidebar Navigation --}}
                <div class="media-sidebar">
                    {{-- Navigation Pills --}}
            <ul class="nav nav-tabs border-0 user-profile-tab" id="media-view-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'all'}"
                        v-on:click="switchView('all')"
                        type="button"
                        role="tab">

                        <span class="d-none d-md-block">Mis archivos</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'recent'}"
                        v-on:click="switchView('recent')"
                        type="button"
                        role="tab">

                        <span class="d-none d-md-block">Recientes</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'favorites'}"
                        v-on:click="switchView('favorites')"
                        type="button"
                        role="tab">
                        <span class="d-none d-md-block">Favoritos</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'trash'}"
                        v-on:click="switchView('trash')"
                        type="button"
                        role="tab">
                        <span class="d-none d-md-block">Papelera</span>
                    </button>
                </li>
            </ul>
                </div>

                {{-- Main Content Area --}}
                <div class="media-content">
            {{-- Upload Zone - Only visible in "all" view --}}
            <div v-if="currentView === 'all'" class="card-body border-bottom">
                <div
                    class="upload-zone-modern rounded-3 p-4 card w-100 bg-primary-subtle overflow-hidden shadow-none"
                    :class="{ 'drag-active': isDragging }"
                    v-on:dragover.prevent="handleDragOver"
                    v-on:dragleave.prevent="handleDragLeave"
                    v-on:drop.prevent="handleDrop">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="fw-bold mb-1">Arrastra y suelta archivos aquí</h6>
                            <p class="mb-3 text-muted">
                                Suelta tus archivos en esta zona o selecciónalos desde tu dispositivo.
                                <span class="d-block mt-1">
                                    Formatos: imágenes, documentos y archivos comprimidos. Máximo <strong>100 MB</strong> por archivo.
                                </span>
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <button v-on:click="showUploadModal" class="btn btn-primary px-3">
                                    <i class="fas fa-upload me-1"></i> Seleccionar archivos
                                </button>
                                <label class="btn btn-outline-primary px-3 mb-0">
                                    <i class="fas fa-folder-plus me-1"></i> Subir carpeta
                                    <input type="file" webkitdirectory directory multiple class="d-none" @change="handleFolderUpload($event)">
                                </label>
                                <button v-on:click="showUploadFromUrlModal" class="btn btn-outline-primary px-3">
                                    <i class="fas fa-link me-1"></i> Importar desde URL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Toolbar: Search + Sort + Filter --}}
            <div class="card-body border-bottom">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                    <div class="flex-fill">
                        <div class="input-group h-100">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" v-model="searchQuery" v-on:input="performSearch" class="form-control border-start-0 ps-0 media-search-input" placeholder="Buscar archivos y carpetas..." />
                            <button v-if="searchQuery" v-on:click="clearSearch" class="btn btn-outline-secondary" title="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <template v-if="currentView !== 'trash'">
                        <div class="flex-shrink-0 media-filter-select">
                            <select id="mediaSortBy" class="form-select select2 h-100">
                                <option value="name">Nombre</option>
                                <option value="created_at">Fecha</option>
                                <option value="size">Tamaño</option>
                            </select>
                        </div>
                        <div class="flex-shrink-0 media-filter-select">
                            <select id="mediaFilterType" class="form-select select2 h-100">
                                <option value="all">Todos los tipos</option>
                                <option value="image">Imágenes</option>
                                <option value="video">Videos</option>
                                <option value="document">Documentos</option>
                                <option value="archive">Archivos</option>
                            </select>
                        </div>
                    </template>
                    <div v-if="currentView === 'trash'" class="flex-shrink-0">
                        <button class="btn btn-danger h-100" v-on:click="emptyTrash" :disabled="files.length === 0 && folders.length === 0">
                            Vaciar papelera
                        </button>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button v-on:click="loadList" class="btn btn-primary" title="Buscar">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" v-on:click="openDuplicatesModal" title="Encontrar duplicados">
                            <i class="fas fa-clone me-1"></i>Duplicados
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" v-on:click="openHeatmap" title="Actividad reciente">
                            <i class="fas fa-chart-line me-1"></i>Actividad
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" v-on:click="setView('recently_deleted')" title="Últimos eliminados">
                            <i class="fas fa-clock-rotate-left me-1"></i>Recientes eliminados
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" v-on:click="openActivityLog" v-if="isAdmin" title="Registro de actividad">
                            <i class="fas fa-list me-1"></i>Registro
                        </button>
                        {{-- Tags filter dropdown --}}
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-tags me-1"></i>Tags <span v-if="selectedTagIds.length" class="badge bg-primary ms-1">@{{ selectedTagIds.length }}</span>
                            </button>
                            <ul class="dropdown-menu p-2" style="min-width: 250px; max-height: 300px; overflow-y: auto">
                                <li v-for="tag in availableTags" :key="tag.id" class="mb-1">
                                    <label class="d-flex align-items-center gap-2 mb-0" >
                                        <input type="checkbox" :value="tag.id" v-model="selectedTagIds" @change="filterByTags">
                                        <span class="media-tag-pill" :style="{ backgroundColor: tag.color }">@{{ tag.name }}</span>
                                    </label>
                                </li>
                                <li v-if="!availableTags.length" class="text-muted small px-2">Sin tags creados</li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item small" href="#" @click.prevent="openTagsManager">+ Crear tag</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Breadcrumbs --}}
            <div class="card-body border-bottom">
                <div v-if="currentView === 'all'">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="#" class="text-primary text-decoration-none" v-on:click.prevent="navigateToFolder(0)">
                                    Inicio
                                </a>
                            </li>
                            <li v-for="(item, idx) in breadcrumbs" :key="idx" class="breadcrumb-item" :class="{ active: idx === breadcrumbs.length - 1 }">
                                <a v-if="idx !== breadcrumbs.length - 1" href="#" class="text-primary text-decoration-none" v-on:click.prevent="navigateToFolder(item.id)">
                                    @{{ item.name }}
                                </a>
                                <span v-else class="fw-semibold">@{{ item.name }}</span>
                            </li>
                        </ol>
                    </nav>
                </div>
                <div v-else>
                    <h6 class="mb-0 fw-bold text-muted">
                        @{{ getViewTitle }}
                    </h6>
                </div>
            </div>

            {{-- Trash warning banner --}}
            <div v-if="currentView === 'trash' || currentView === 'recently_deleted'" class="card-body border-bottom py-2">
                <div class="alert alert-warning d-flex align-items-center mb-0 py-2">
                    <i class="fas fa-triangle-exclamation me-2"></i>
                    Archivos eliminados. Se borrarán permanentemente después de 30 días.
                </div>
            </div>

            {{-- Storage Widget --}}
            <div v-if="quotaEnabled || storageUsed > 0" class="card-body border-bottom py-2">
                <div class="media-storage-widget d-flex align-items-center gap-2 p-2 bg-light rounded">
                    <i class="fas fa-hdd text-muted"></i>
                    <div class="flex-grow-1">
                        <div class="small text-muted">Almacenamiento</div>
                        <div class="progress media-quota-progress">
                            <div class="progress-bar" :class="quotaBarClass" :style="{ width: quotaPercent + '%' }"></div>
                        </div>
                        <div class="small">@{{ formatBytes(storageUsed) }}<span v-if="storageTotal"> / @{{ formatBytes(storageTotal) }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Content Area --}}
            <div class="card-body media-content-area">
                {{-- Empty State --}}
                <div v-if="files.length === 0 && folders.length === 0" class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 media-empty-circle">
                        <i v-if="currentView === 'all'" class="fas fa-folder fs-9 text-muted"></i>
                        <i v-if="currentView === 'recent'" class="fas fa-clock fs-9 text-muted"></i>
                        <i v-if="currentView === 'favorites'" class="fas fa-star fs-9 text-muted"></i>
                        <i v-if="currentView === 'trash'" class="fas fa-trash fs-9 text-muted"></i>
                    </div>
                    <h5 class="fw-bold">
                        <span v-if="searchQuery">No se encontraron resultados</span>
                        <span v-else-if="currentView === 'all'">Carpeta vacía</span>
                        <span v-else-if="currentView === 'recent'">No hay archivos recientes</span>
                        <span v-else-if="currentView === 'favorites'">No tienes archivos favoritos</span>
                        <span v-else-if="currentView === 'trash'">La papelera está vacía</span>
                    </h5>
                    <p class="text-muted mb-4">
                        <span v-if="searchQuery">Intenta con otros términos de búsqueda</span>
                        <span v-else-if="currentView === 'all'">Sube archivos o crea carpetas para comenzar</span>
                        <span v-else-if="currentView === 'recent'">Los archivos que modifiques aparecerán aquí</span>
                        <span v-else-if="currentView === 'favorites'">Marca archivos como favoritos para verlos aquí</span>
                        <span v-else-if="currentView === 'trash'">Los archivos eliminados aparecerán aquí</span>
                    </p>
                </div>

                {{-- Files/Folders Grid --}}
                <div v-else class="row g-3">
                    {{-- Folders Card Style --}}
                    <div v-for="folder in folders" :key="'folder-' + folder.id" class="col-md-6 col-xl-4 col-xxl-3">
                        <div class="card h-100 border-0 shadow-sm folder-card no-select position-relative"
                             :class="{ 'selected': isItemSelected('folder', folder.id) }"
                             v-on:click="handleCardClick($event, 'folder', folder)"
                             >
                            <div class="dropdown position-absolute top-0 end-0 m-1" @click.stop>
                                <button type="button" class="btn btn-sm btn-link text-muted p-1" data-bs-toggle="dropdown" aria-label="Acciones">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <template v-if="currentView === 'trash'">
                                        <li><a class="dropdown-item" href="#" v-on:click.prevent="restoreFolder(folder)">Restaurar</a></li>
                                    </template>
                                    <template v-else>
                                        <li><a class="dropdown-item" href="#" v-on:click.prevent="navigateToFolder(folder.id)">Abrir</a></li>
                                        <li><a class="dropdown-item" href="#" v-on:click.prevent="renameFolder(folder)">Renombrar</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" v-on:click.prevent="deleteFolder(folder)">Eliminar</a></li>
                                    </template>
                                </ul>
                            </div>
                            {{-- Selection Checkbox --}}
                            <div class="selection-checkbox"
                                 :class="{ 'visible': selectedItems.length > 0 }"
                                 v-on:click.stop>
                                <input type="checkbox"
                                       :checked="isItemSelected('folder', folder.id)"
                                       v-on:change="toggleSelection('folder', folder.id, folder)">
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="folder-icon-wrapper me-2" :style="{ backgroundColor: folder.color + '20', borderColor: folder.color }">
                                                <i class="fas fa-folder" :style="{ color: folder.color || '#FFA726' }"></i>
                                            </div>
                                            <h5 class="card-title mb-0 text-truncate fw-semibold" :title="folder.name">@{{ folder.name }}</h5>
                                        </div>

                                        {{-- Folder Stats --}}
                                        <div class="d-flex gap-3 mb-2">
                                            <small class="text-muted d-flex align-items-center">
                                                <i class="fas fa-file me-1"></i>
                                                @{{ folder.files_count }} archivo@{{ folder.files_count !== 1 ? 's' : '' }}
                                            </small>
                                            <small class="text-muted d-flex align-items-center" v-if="folder.children_count > 0">
                                                <i class="fas fa-folder me-1"></i>
                                                @{{ folder.children_count }} carpeta@{{ folder.children_count !== 1 ? 's' : '' }}
                                            </small>
                                        </div>

                                        {{-- Folder Date --}}
                                        <small class="text-muted d-flex align-items-center">
                                            <i class="fas fa-clock me-1"></i>
                                            @{{ folder.created_at }}
                                        </small>
                                    </div>
                                    <div class="ms-auto" v-on:click.stop>
                                        <div class="dropdown dropstart">
                                            <a href="javascript:void(0)" class="link text-dark p-2" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <template v-if="currentView === 'trash'">
                                                    <li><a class="dropdown-item" href="#" v-on:click.prevent="restoreFolder(folder)">Restaurar</a></li>
                                                </template>
                                                <template v-else>
                                                    <li><a class="dropdown-item" href="#" v-on:click.prevent="navigateToFolder(folder.id)">Abrir</a></li>
                                                    <li><a class="dropdown-item" href="#" v-on:click.prevent="renameFolder(folder)">Renombrar</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="#" v-on:click.prevent="deleteFolder(folder)">Eliminar</a></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                {{-- Folder Preview with better design --}}
                                <div class="folder-preview rounded-3 bg-light-subtle p-3">
                                    <div v-if="folder.files_count === 0" class="text-center py-3">
                                        <i class="fas fa-folder-open fs-1 text-muted opacity-25 mb-2"></i>
                                        <p class="text-muted mb-0 fw-medium">Carpeta vacía</p>
                                    </div>
                                    <div v-else>
                                        <div class="row g-2 mb-2">
                                            <div v-for="n in Math.min(folder.files_count, 4)" :key="n" class="col-6">
                                                <div class="bg-white rounded-2 border border-light p-2 text-center">
                                                    <i class="fas fa-file-alt fs-4 text-primary opacity-50"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="folder.files_count > 4" class="text-center">
                                            <small class="text-muted">+@{{ folder.files_count - 4 }} más</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Files --}}
                    <div v-for="file in files" :key="'file-' + file.id" class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                        <div class="card hover-img rounded-2 no-select position-relative"
                             :class="{ 'selected': isItemSelected('file', file.id) }"
                             v-on:click="handleCardClick($event, 'file', file)"
                             v-on:dblclick="openPreview(file)"
                             >
                            <div class="position-relative">
                                {{-- Image Preview --}}
                                <template v-if="file.type === 'image'">
                                    <img :src="file.public_url"
                                         :alt="file.name"
                                         class="card-img-top rounded-0 media-image-preview"
                                         v-on:error="$event.target.style.display='none'">
                                </template>

                                {{-- Non-image file icon preview --}}
                                <template v-else>
                                    <div class="d-flex align-items-center justify-content-center rounded-0 media-icon-preview"
                                         :class="{
                                             'bg-danger-subtle': file.type === 'video' || file.type === 'pdf',
                                             'bg-info-subtle': file.type === 'document',
                                             'bg-success-subtle': file.type === 'spreadsheet',
                                             'bg-warning-subtle': file.type === 'archive',
                                             'bg-secondary-subtle': !['video','pdf','document','spreadsheet','archive','audio'].includes(file.type),
                                             'bg-primary-subtle': file.type === 'audio'
                                         }">
                                        <i class="display-4"
                                           :class="{
                                               'fas fa-play-circle text-danger': file.type === 'video',
                                               'fas fa-file-pdf text-danger': file.type === 'pdf',
                                               'fas fa-file-audio text-primary': file.type === 'audio',
                                               'fas fa-file-word text-info': file.type === 'document',
                                               'fas fa-file-excel text-success': file.type === 'spreadsheet',
                                               'fas fa-file-archive text-warning': file.type === 'archive',
                                               'fas fa-file-code text-dark': file.type === 'code',
                                               'fas fa-file text-muted': !['video','pdf','audio','document','spreadsheet','archive','code'].includes(file.type)
                                           }"></i>
                                    </div>
                                </template>

                                {{-- Options Menu (floating button) --}}
                                <div v-on:click.stop class="position-absolute bottom-0 end-0 mb-n3 me-3 media-dropdown-overlay">
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="bg-primary rounded-circle text-white d-inline-flex align-items-center justify-content-center media-dropdown-btn" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <template v-if="currentView === 'trash'">
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="restoreFile(file)">Restaurar</a></li>
                                            </template>
                                            <template v-else>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="renameFile(file)">Renombrar</a></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="copyFile(file)">Hacer copia</a></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="copyFileLink(file)">Copiar link</a></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="toggleFavorite(file)">Favoritos</a></li>
                                                <li v-if="isImage(file)"><a class="dropdown-item" href="#" v-on:click.prevent="openImageEditor(file)">Editar imagen</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="openVersions(file)">Versiones</a></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="openAccessLogs(file)">Ver accesos</a></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="openShare(file)">Compartir</a></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="openExpiration(file)">Establecer expiración</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="showProperties(file)">Propiedades</a></li>
                                                <li><a class="dropdown-item" href="#" v-on:click.prevent="deleteFile(file)">Eliminar</a></li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-3 p-4">
                                <h6 class="fw-semibold fs-4 text-truncate mb-2" :title="file.name">@{{ file.name }}</h6>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted fs-3">@{{ file.human_size }}</span>
                                    <span class="badge bg-primary-subtle text-primary">@{{ getFileExtension(file.name) }}</span>
                                </div>
                                <div v-if="file.tags && file.tags.length" class="d-flex flex-wrap gap-1 mt-1">
                                    <span v-for="tag in file.tags" :key="tag.id" class="media-tag-pill-sm" :style="{ backgroundColor: tag.color }">@{{ tag.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Infinite scroll sentinel --}}
            <div v-if="hasMore" ref="scrollSentinel" class="py-4 text-center text-muted">
                <span v-if="loadingMore" class="spinner-border spinner-border-sm"></span>
                <span v-else class="small">Scroll para cargar más...</span>
            </div>
                </div>{{-- End media-content --}}
            </div>{{-- End media-layout-container --}}
        </div>
    </div>

    {{-- Modals --}}
    {{-- New Folder Modal --}}
    <div class="modal fade" id="modalNewFolder" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Nueva carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la carpeta</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="newFolderName"
                            v-on:keyup.enter="confirmCreateFolder"
                            placeholder="Ingresa el nombre de la carpeta"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" v-on:click="confirmCreateFolder">
                        Crear carpeta
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rename Folder Modal --}}
    <div class="modal fade" id="modalRenameFolder" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Renombrar carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo nombre</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="renameFolderData.name"
                            v-on:keyup.enter="confirmRenameFolder"
                            placeholder="Ingresa el nuevo nombre"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" v-on:click="confirmRenameFolder">
                        Renombrar
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rename File Modal --}}
    <div class="modal fade" id="modalRenameFile" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Renombrar archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo nombre</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="renameFileData.name"
                            v-on:keyup.enter="confirmRenameFile"
                            placeholder="Ingresa el nuevo nombre"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" v-on:click="confirmRenameFile">
                        Renombrar
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Copy File Modal --}}
    <div class="modal fade" id="modalCopyFile" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Hacer copia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">¿Deseas crear una copia de este archivo?</p>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle me-2"></i>Se creará una copia con el sufijo "_copia"</small>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" v-on:click="confirmCopyFile()">
                        Crear copia
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Copy Link Modal --}}
    <div class="modal fade" id="modalCopyLink" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Copiar link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold mb-2">Link del archivo</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" v-model="copyLinkData.url" readonly>
                        <button class="btn btn-info" type="button" v-on:click="copyToClipboard(copyLinkData.url)">
                            <i class="fas fa-copy me-1"></i>
                        </button>
                    </div>
                    <small class="text-muted">El link ha sido copiado al portapapeles</small>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" data-bs-dismiss="modal">
                        Listo
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- File Properties Modal --}}
    <div class="modal fade" id="modalFileProperties" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Propiedades del archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Nombre:</span>
                            <span>@{{ filePropertiesData.name }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Tamaño:</span>
                            <span>@{{ filePropertiesData.human_size }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Tipo:</span>
                            <span>@{{ filePropertiesData.mime_type }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Cargado:</span>
                            <span>@{{ filePropertiesData.created_at }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">ID:</span>
                            <span class="text-muted media-uid-text">@{{ filePropertiesData.uid }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-danger">
                    <h5 class="modal-title fw-bold">Eliminar archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">¿Estás seguro de que deseas eliminar este archivo?</p>
                    <div class="alert alert-warning mb-0">
                        <small><i class="fas fa-exclamation-triangle me-2"></i>Esta acción no se puede deshacer</small>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-danger w-100 mb-2" v-on:click="confirmDeleteFile">
                        Eliminar
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Favorite Toggle Modal --}}
    <div class="modal fade" id="modalFavorite" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Favoritos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">@{{ favoriteMessage }}</p>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle me-2"></i>Esta funcionalidad está en desarrollo</small>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" data-bs-dismiss="modal">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Upload from URL Modal --}}
    <div class="modal fade" id="modalUploadFromUrl" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Subir desde URL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL del archivo</label>
                        <input
                            type="url"
                            class="form-control"
                            v-model="uploadUrlData.url"
                            v-on:keyup.enter="confirmUploadFromUrl"
                            placeholder="https://ejemplo.com/archivo.pdf"
                            autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del archivo (opcional)</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="uploadUrlData.filename"
                            placeholder="deja vacío para usar el nombre original">
                    </div>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle me-2"></i>Se descargarán archivos de hasta 100MB</small>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" v-on:click="confirmUploadFromUrl" :disabled="!uploadUrlData.url">
                        Subir desde URL
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview Lightbox Modal --}}
    <div class="modal fade" id="mediaPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@{{ previewItem?.name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0 media-preview-body">
                    <img v-if="previewType === 'image'" :src="previewUrl" class="img-fluid">
                    <iframe v-else-if="previewType === 'pdf'" :src="previewUrl" class="media-preview-iframe"></iframe>
                    <video v-else-if="previewType === 'video'" :src="previewUrl" controls class="w-100"></video>
                    <audio v-else-if="previewType === 'audio'" :src="previewUrl" controls class="w-100 my-4"></audio>
                    <div v-else class="p-4">
                        <i class="fas fa-file fa-4x text-muted mb-3"></i>
                        <p>Vista previa no disponible para este tipo de archivo.</p>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <a :href="previewUrl" target="_blank" class="btn btn-primary w-100 mb-2">Abrir en nueva pestaña</a>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Multiple Upload Modal --}}
    <div class="modal fade" id="modalEnhancedUpload" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        Subir archivos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Drag & Drop Zone --}}
                    <div class="upload-drop-zone mb-4 p-5 text-center border border-2 border-dashed rounded-3"
                         :class="{'border-primary bg-primary bg-opacity-10': isDragging}"
                         v-on:dragover.prevent="isDragging = true"
                         v-on:dragleave.prevent="isDragging = false"
                         v-on:drop.prevent="handleFilesDrop">
                        <div class="mb-3">

                        </div>
                        <h5 class="fw-semibold mb-2">Arrastra archivos aquí</h5>
                        <p class="text-muted mb-3">o haz clic para seleccionar archivos</p>
                        <input type="file"
                               ref="fileInput"
                               multiple
                               v-on:change="handleFilesSelect"
                               class="d-none">
                        <button type="button"
                                class="btn btn-primary rounded-pill px-4"
                                v-on:click="$refs.fileInput.click()"> Seleccionar Archivos
                        </button>
                        <div class="mt-3">
                            <small class="text-muted">
                                Tamaño máximo: 100MB por archivo
                            </small>
                        </div>
                    </div>

                    {{-- File Preview List --}}
                    <div v-if="uploadQueue.length > 0" class="file-preview-list">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-semibold mb-0">
                                Archivos seleccionados (@{{ uploadQueue.length }})
                            </h6>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                    v-on:click="clearUploadQueue">
                                <i class="fas fa-trash me-1"></i>
                            </button>
                        </div>

                        <div class="list-group">
                            <div v-for="(fileItem, index) in uploadQueue"
                                 :key="index"
                                 class="list-group-item border rounded-3 mb-2">
                                <div class="d-flex align-items-start gap-3">
                                    {{-- File Icon --}}
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center media-file-icon">
                                            <i :class="getUploadFileIcon(fileItem.file)" class="fs-4"></i>
                                        </div>
                                    </div>

                                    {{-- File Info --}}
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-start justify-content-between mb-1">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-semibold text-truncate">@{{ fileItem.file.name }}</h6>
                                                <small class="text-muted">@{{ formatFileSize(fileItem.file.size) }}</small>
                                            </div>
                                            <button type="button"
                                                    class="btn btn-sm btn-light rounded-circle ms-2 media-remove-btn"
                                                    v-on:click="removeFromQueue(index)"
                                                    :disabled="fileItem.uploading"
                                                    >
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div v-if="fileItem.uploading || fileItem.progress > 0" class="mt-2">
                                            <div class="progress media-progress-bar">
                                                <div class="progress-bar"
                                                     :class="{
                                                         'bg-success': fileItem.progress === 100,
                                                         'bg-primary': fileItem.progress < 100,
                                                         'progress-bar-striped progress-bar-animated': fileItem.uploading
                                                     }"
                                                     :style="{width: fileItem.progress + '%'}">
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <small class="text-muted">
                                                    <span v-if="fileItem.uploading">Subiendo...</span>
                                                    <span v-else-if="fileItem.progress === 100" class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Completado
                                                    </span>
                                                </small>
                                                <small class="text-muted fw-semibold">@{{ fileItem.progress }}%</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div v-else class="text-center py-4">
                        <i class="fas fa-file-upload text-muted mb-3 media-empty-icon"></i>
                        <p class="text-muted mb-0">No hay archivos seleccionados</p>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button"
                            class="btn btn-primary w-100 mb-2"
                            v-on:click="startUpload"
                            :disabled="uploadQueue.length === 0 || isUploading">
                        <span v-if="isUploading">Subiendo...</span>
                        <span v-else>Subir @{{ uploadQueue.length }} archivo@{{ uploadQueue.length !== 1 ? 's' : '' }}</span>
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Duplicates Modal --}}
    <div class="modal fade" id="duplicatesModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Detector de duplicados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Modo</label>
                        <select class="form-select" v-model="duplicatesMode" @change="loadDuplicates">
                            <option value="exact">Hash exacto (idénticos)</option>
                            <option value="similar">Visualmente similares (pHash)</option>
                        </select>
                    </div>
                    <div v-if="duplicatesLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2">Buscando duplicados...</p>
                    </div>
                    <div v-else-if="!duplicatesGroups.length" class="text-muted text-center py-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p>No se encontraron duplicados.</p>
                    </div>
                    <div v-else>
                        <p class="text-muted">Se encontraron @{{ duplicatesGroups.length }} grupos de duplicados.</p>
                        <div v-for="(group, idx) in duplicatesGroups" :key="idx" class="mb-3 p-3 border rounded">
                            <strong class="d-block mb-2">Grupo @{{ idx + 1 }} (@{{ group.length }} archivos)</strong>
                            <div class="row g-2">
                                <div v-for="f in group" :key="f.id" class="col-md-3 col-sm-6">
                                    <div class="media-dup-card border rounded p-2">
                                        <img v-if="f.public_url" :src="f.public_url" class="w-100 rounded mb-1" style="height:80px;object-fit:cover;">
                                        <i v-else class="fas fa-file fa-2x text-muted d-block text-center mb-1"></i>
                                        <div class="small text-truncate" :title="f.name">@{{ f.name }}</div>
                                        <button class="btn btn-sm btn-outline-danger mt-1 w-100" @click="deleteFile(f)">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Image Editor Modal --}}
    <div class="modal fade" id="imageEditorModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar imagen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-light" @click="cropperAction('rotate', -90)"><i class="fas fa-undo me-1"></i>Rotar -90°</button>
                        <button class="btn btn-sm btn-light" @click="cropperAction('rotate', 90)"><i class="fas fa-redo me-1"></i>Rotar +90°</button>
                        <button class="btn btn-sm btn-light" @click="cropperAction('scaleX', -1)"><i class="fas fa-arrows-alt-h me-1"></i>Flip H</button>
                        <button class="btn btn-sm btn-light" @click="cropperAction('scaleY', -1)"><i class="fas fa-arrows-alt-v me-1"></i>Flip V</button>
                        <button class="btn btn-sm btn-light" @click="cropperAction('reset')"><i class="fas fa-sync me-1"></i>Reset</button>
                    </div>
                    <img id="imageEditorImg" :src="editorImageUrl" class="w-100" style="max-height:60vh;">
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" @click="saveEditedImage">Guardar como nueva imagen</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tags Manager Modal --}}
    <div class="modal fade" id="tagsManagerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Gestionar tags</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo tag</label>
                        <div class="d-flex gap-2">
                            <input type="text" v-model="newTagName" class="form-control" placeholder="Nombre" @keyup.enter="createTag">
                            <input type="color" v-model="newTagColor" class="form-control form-control-color" style="max-width:60px">
                            <button class="btn btn-primary" @click="createTag">Crear</button>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <div v-if="!availableTags.length" class="text-muted small">No hay tags creados aún.</div>
                        <div v-for="tag in availableTags" :key="tag.id" class="d-flex align-items-center justify-content-between mb-2">
                            <span class="media-tag-pill" :style="{ backgroundColor: tag.color }">@{{ tag.name }}</span>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteTag(tag)">Eliminar</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Versions Modal --}}
    <div class="modal fade" id="versionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Historial de versiones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="!fileVersions.length" class="text-center text-muted py-3">
                        No hay versiones previas de este archivo.
                    </div>
                    <div v-else class="media-version-timeline">
                        <div v-for="v in fileVersions" :key="v.id" class="media-version-item">
                            <div class="fw-medium">Versión @{{ v.version_number }} · @{{ formatBytes(v.size) }}</div>
                            <div class="small text-muted">@{{ formatDate(v.created_at) }}</div>
                            <div v-if="v.notes" class="small">@{{ v.notes }}</div>
                            <button class="btn btn-sm btn-outline-primary mt-2" @click="restoreVersion(v)">Restaurar esta versión</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Access Logs Modal --}}
    <div class="modal fade" id="accessLogsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Registro de acceso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="!accessLogs.length" class="text-center text-muted py-3">No hay registros de acceso.</div>
                    <table v-else class="table table-sm">
                        <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>IP</th></tr></thead>
                        <tbody>
                            <tr v-for="log in accessLogs" :key="log.id">
                                <td>@{{ formatDate(log.created_at) }}</td>
                                <td>@{{ log.user_name || 'Anónimo' }}</td>
                                <td><span class="badge bg-info">@{{ log.action }}</span></td>
                                <td>@{{ log.ip_address }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Share Modal --}}
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Compartir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item"><a class="nav-link" :class="{active: shareTab==='link'}" href="#" @click.prevent="shareTab='link'">Link público</a></li>
                        <li class="nav-item"><a class="nav-link" :class="{active: shareTab==='user'}" href="#" @click.prevent="shareTab='user'">Con usuarios</a></li>
                    </ul>
                    <div v-if="shareTab==='link'">
                        <label class="form-label fw-semibold">Duración</label>
                        <select class="form-select mb-3" v-model="shareTtl">
                            <option :value="60">1 hora</option>
                            <option :value="1440">24 horas</option>
                            <option :value="10080">7 días</option>
                        </select>
                        <button class="btn btn-primary w-100" @click="generateShareLink">Generar link</button>
                        <div v-if="generatedShareUrl" class="mt-3">
                            <div class="input-group">
                                <input type="text" class="form-control" :value="generatedShareUrl" readonly>
                                <button class="btn btn-outline-secondary" @click="copyShareUrl"><i class="fas fa-copy"></i></button>
                            </div>
                            <div id="shareQrCode" class="mt-3 text-center"></div>
                        </div>
                    </div>
                    <div v-else-if="shareTab==='user'">
                        <label class="form-label fw-semibold">User ID</label>
                        <input type="number" class="form-control mb-2" v-model.number="shareWithUserId" placeholder="ID del usuario">
                        <label class="form-label fw-semibold">Rol</label>
                        <select class="form-select mb-3" v-model="shareRole">
                            <option value="view">Ver</option>
                            <option value="comment">Comentar</option>
                            <option value="edit">Editar</option>
                        </select>
                        <button class="btn btn-primary w-100" @click="shareWithUser">Compartir</button>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Heatmap Modal --}}
    <div class="modal fade" id="heatmapModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Actividad últimos 90 días</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="media-heatmap">
                        <div v-for="day in heatmapDays" :key="day.date" class="media-heatmap-cell" :class="'level-'+day.level" :title="day.date+': '+day.count+' archivos'"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-3 small text-muted align-items-center">
                        <span>Menos</span>
                        <div class="d-flex gap-1">
                            <span class="media-heatmap-cell level-0"></span>
                            <span class="media-heatmap-cell level-1"></span>
                            <span class="media-heatmap-cell level-2"></span>
                            <span class="media-heatmap-cell level-3"></span>
                            <span class="media-heatmap-cell level-4"></span>
                        </div>
                        <span>Más</span>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Activity Log Modal --}}
    <div class="modal fade" id="activityLogModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registro de actividad Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 d-flex gap-2">
                        <select class="form-select" v-model="activityLogFilter" @change="loadActivityLog" style="max-width:200px">
                            <option value="">Todas las acciones</option>
                            <option value="created">Creado</option>
                            <option value="updated">Actualizado</option>
                            <option value="deleted">Eliminado</option>
                        </select>
                    </div>
                    <div v-if="!activityLogEntries.length" class="text-center text-muted py-4">
                        <i class="fas fa-list fa-2x mb-2 opacity-25"></i>
                        <p>Sin registros de actividad.</p>
                    </div>
                    <table v-else class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in activityLogEntries" :key="entry.id">
                                <td class="small">@{{ formatDate(entry.created_at) }}</td>
                                <td>@{{ entry.causer_name || 'Sistema' }}</td>
                                <td><span class="badge bg-secondary">@{{ entry.description }}</span></td>
                                <td class="small">@{{ entry.log_name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Expiration Modal --}}
    <div class="modal fade" id="expirationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Establecer expiración</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Fecha y hora de expiración</label>
                    <input type="datetime-local" class="form-control" v-model="expiresAt">
                    <p class="text-muted small mt-2">El archivo dejará de estar disponible después de esta fecha.</p>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" @click="saveExpiration">Guardar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script data-pagespeed-no-defer src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script data-pagespeed-no-defer src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script data-pagespeed-no-defer src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
<script>
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                loading: true,
                currentFolderId: 0,
                folders: [],
                files: [],
                rootFolders: [],
                breadcrumbs: [],
                searchQuery: '',
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 50,
                    total: 0
                },
                totalFolders: 0,
                totalFiles: 0,
                totalSize: '0 MB',
                lastActivity: 'N/A',
                newFolderName: '',
                renameFolderData: { id: null, name: '' },
                renameFileData: { id: null, name: '' },
                copyLinkData: { url: '' },
                filePropertiesData: {},
                deleteFileData: { id: null, name: '' },
                copyFileData: { id: null, name: '' },
                favoriteMessage: '',
                viewMode: 'grid',
                sortBy: 'name',
                sortOrder: 'asc',
                filterType: 'all',
                currentView: 'all',
                uploadUrlData: { url: '', filename: '' },
                activeDisk: '{{ $activeDisk }}',
                // Enhanced upload modal
                uploadQueue: [],
                isDragging: false,
                isUploading: false,
                // Context menu
                contextMenu: {
                    visible: false,
                    type: null, // 'file' or 'folder'
                    item: null,
                    x: 0,
                    y: 0
                },
                // Multiple selection
                selectedItems: [],  // Array of { type: 'file'|'folder', id: number }
                selectionMode: false,
                pickerMode: {{ $pickerMode ? 'true' : 'false' }},
                // Preview lightbox
                previewItem: null,
                previewItemName: '',
                previewType: '',
                previewUrl: '',
                // Storage quota
                storageUsed: 0,
                storageTotal: 0,
                quotaEnabled: false,
                // Duplicates
                duplicatesMode: 'exact',
                duplicatesLoading: false,
                duplicatesGroups: [],
                // Image editor
                editorImageUrl: '',
                editorItem: null,
                cropperInstance: null,
                // Tags
                availableTags: [],
                selectedTagIds: [],
                newTagName: '',
                newTagColor: '#6c757d',
                // Versions
                fileVersions: [],
                currentVersionedItem: null,
                // Access logs
                accessLogs: [],
                // Share
                shareTab: 'link',
                shareTtl: 1440,
                shareWithUserId: null,
                shareRole: 'view',
                shareItem: null,
                generatedShareUrl: '',
                // Expiration
                expirationItem: null,
                expiresAt: '',
                // Infinite scroll
                hasMore: true,
                loadingMore: false,
                currentPage: 1,
                scrollObserver: null,
                // Heatmap
                heatmapDays: [],
                // Activity log
                activityLogFilter: '',
                activityLogEntries: [],
                isAdmin: {{ auth()->user()?->can('media.manage') ? 'true' : 'false' }},
            }
        },
        computed: {
            getViewTitle() {
                const titles = {
                    'all': 'Mis Archivos',
                    'recent': 'Archivos Recientes',
                    'favorites': 'Archivos Favoritos',
                    'trash': 'Papelera',
                    'recently_deleted': 'Eliminados recientemente',
                };
                return titles[this.currentView] || 'Mis Archivos';
            },
            hasOnlyFiles() {
                return this.selectedItems.every(item => item.type === 'file');
            },
            quotaPercent() {
                if (!this.storageTotal) return 0;
                return Math.min(100, Math.round(this.storageUsed / this.storageTotal * 100));
            },
            quotaBarClass() {
                if (this.quotaPercent > 90) return 'bg-danger';
                if (this.quotaPercent > 70) return 'bg-warning';
                return 'bg-success';
            }
        },
        methods: {
            async loadMedia(folderId = 0, page = 1) {
                this.loading = true;
                this.currentPage = 1;
                this.hasMore = true;
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=${folderId}&page=1&per_page=30&search=${this.searchQuery}`);
                    const data = await response.json();

                    this.folders = data.folders || [];
                    this.files = data.files || [];
                    this.breadcrumbs = data.breadcrumbs || [];
                    this.pagination = data.pagination || this.pagination;
                    this.totalFolders = data.stats?.total_folders || 0;
                    this.totalFiles = data.stats?.total_files || 0;
                    this.totalSize = data.stats?.total_size || '0 MB';
                    this.lastActivity = data.stats?.last_activity || 'N/A';
                    this.rootFolders = data.root_folders || [];
                    this.currentFolderId = folderId;
                    this.hasMore = (data.pagination?.current_page ?? 0) < (data.pagination?.last_page ?? 0);
                } catch (error) {
                    toastr.error('Error al cargar archivos', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async changeActiveDisk(disk) {
                if (disk) {
                    this.activeDisk = disk;
                }

                try {
                    const response = await fetch('{{ route('media.set-disk') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            disk: this.activeDisk
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.currentView = 'all';
                        this.currentFolderId = 0;
                        this.searchQuery = '';
                        this.selectedItems = [];
                        await this.loadMedia(0);
                    } else {
                        toastr.error(data.message || 'Error al cambiar disco', 'Error');
                    }
                } catch (error) {
                    console.error('Error changing active disk:', error);
                    toastr.error('Error al cambiar el disco de almacenamiento', 'Error');
                }
            },
            navigateToFolder(folderId) {
                this.loadMedia(folderId);
            },
            goToPage(page) {
                if (page >= 1 && page <= this.pagination.last_page) {
                    this.loadMedia(this.currentFolderId, page);
                }
            },
            performSearch() {
                this.loadList();
            },
            clearSearch() {
                this.searchQuery = '';
                this.loadList();
            },
            async handleFileUpload(event) {
                const files = event.target.files;
                if (!files.length) return;

                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                try {
                    for (let file of files) {
                        const formData = new FormData();
                        // Enviar null si folder_id es 0 (carpeta raíz)
                        if (this.currentFolderId > 0) {
                            formData.append('folder_id', this.currentFolderId);
                        }
                        formData.append('file', file);

                        const response = await fetch('{{ route("media.files.upload") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });

                        if (!response.ok) {
                            toastr.error(`Error al cargar ${file.name}`, 'Error');
                        }
                    }

                    // Recargar después de subir todos
                    this.loadList();
                    this.$refs.fileInput.value = '';
                    toastr.success('Archivos cargados exitosamente', 'Éxito');
                } catch (error) {
                    toastr.error('Error al cargar archivos', 'Error');
                }
            },
            showNewFolderModal() {
                this.newFolderName = '';
                new bootstrap.Modal(document.getElementById('modalNewFolder')).show();
            },
            async confirmCreateFolder() {
                if (!this.newFolderName.trim()) return;

                try {
                    const response = await fetch('{{ route("media.folders.create") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            name: this.newFolderName,
                            parent_id: this.currentFolderId
                        })
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalNewFolder')).hide();
                        this.loadList();
                        toastr.success('Carpeta creada exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al crear carpeta', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al crear carpeta', 'Error');
                }
            },
            renameFolder(folder) {
                this.renameFolderData = { id: folder.id, name: folder.name };
                new bootstrap.Modal(document.getElementById('modalRenameFolder')).show();
            },
            async confirmRenameFolder() {
                if (!this.renameFolderData.name.trim()) return;

                try {
                    const response = await fetch(`{{ url('panel/media/folders') }}/${this.renameFolderData.id}/rename`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ name: this.renameFolderData.name })
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalRenameFolder')).hide();
                        this.loadList();
                        toastr.success('Carpeta renombrada exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al renombrar carpeta', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al renombrar carpeta', 'Error');
                }
            },
            async deleteFolder(folder) {
                

                try {
                    const response = await fetch(`{{ url('panel/media/folders') }}/${folder.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        this.loadList();
                        toastr.success('Carpeta eliminada exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al eliminar carpeta', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al eliminar carpeta', 'Error');
                }
            },
            async deleteFile(file) {
                

                try {
                    const response = await fetch(`{{ url('panel/media/files') }}/${file.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        this.loadList();
                        toastr.success('Archivo eliminado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al eliminar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al eliminar archivo', 'Error');
                }
            },
            async copyFile(file) {
                try {
                    const response = await fetch(`{{ url('panel/media/files') }}/${file.id}/copy`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        toastr.success('Archivo copiado exitosamente', 'Éxito');
                        this.loadList();
                    } else {
                        toastr.error('Error al copiar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al copiar archivo', 'Error');
                }
            },
            renameFile(file) {
                this.renameFileData = { id: file.id, name: file.name };
                new bootstrap.Modal(document.getElementById('modalRenameFile')).show();
            },
            async confirmRenameFile() {
                if (!this.renameFileData.name.trim()) return;

                try {
                    const response = await fetch(`{{ url('panel/media/files') }}/${this.renameFileData.id}/rename`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ name: this.renameFileData.name })
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalRenameFile')).hide();
                        this.loadList();
                        toastr.success('Archivo renombrado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al renombrar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al renombrar archivo', 'Error');
                }
            },
            copyFile(file) {
                this.copyFileData = file;
                new bootstrap.Modal(document.getElementById('modalCopyFile')).show();
            },
            async confirmCopyFile() {
                try {
                    const response = await fetch(`/panel/media/files/${this.copyFileData.id}/copy`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalCopyFile')).hide();
                        this.loadList();
                        toastr.success('Archivo copiado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al copiar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al copiar archivo', 'Error');
                }
            },
            copyFileLink(file) {
                this.copyLinkData.url = file.public_url || `{{ url('/media') }}/${file.url}`;
                new bootstrap.Modal(document.getElementById('modalCopyLink')).show();
            },
            copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    toastr.success('Link copiado al portapapeles', 'Éxito');
                }).catch(err => {
                    toastr.error('Error al copiar link', 'Error');
                });
            },
            deleteFile(file) {
                this.deleteFileData = file;
                new bootstrap.Modal(document.getElementById('modalConfirmDelete')).show();
            },
            async confirmDeleteFile() {
                try {
                    const response = await fetch(`{{ url('panel/media/files') }}/${this.deleteFileData.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalConfirmDelete')).hide();
                        this.loadList();
                        toastr.success('Archivo eliminado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al eliminar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al eliminar archivo', 'Error');
                }
            },
            copyIndirectLink(file) {
                const indirectUrl = `{{ url('panel/media/files') }}/${file.id}`;
                navigator.clipboard.writeText(indirectUrl).then(() => {
                    toastr.success('Enlace indirecto copiado al portapapeles', 'Éxito');
                }).catch(err => {
                    toastr.error('Error al copiar enlace', 'Error');
                });
            },
            shareFile(file) {
                toastr.info(`Funcionalidad de compartir en desarrollo para: ${file.name}`, 'Información');
            },
            downloadFile(file) {
                window.location.href = file.public_url || `/media/${file.url}`;
            },
            toggleFavorite(file) {
                this.favoriteMessage = `¿Deseas marcar "${file.name}" como favorito?`;
                new bootstrap.Modal(document.getElementById('modalFavorite')).show();
            },
            showProperties(file) {
                this.filePropertiesData = file;
                new bootstrap.Modal(document.getElementById('modalFileProperties')).show();
            },
            getFileIcon(type) {
                const icons = {
                    'image': 'fas fa-image',
                    'video': 'fas fa-video',
                    'audio': 'fas fa-music',
                    'pdf': 'fas fa-file-pdf',
                    'document': 'fas fa-file-alt',
                    'spreadsheet': 'fas fa-file-excel',
                    'archive': 'fas fa-file-archive',
                    'code': 'fas fa-code'
                };
                return icons[type] || 'fas fa-file';
            },
            getFileIconClass(file) {
                // Mapeo detallado por extensión de archivo
                const ext = this.getFileExtension(file.name).toLowerCase();

                const iconMap = {
                    // Documentos PDF
                    'pdf': 'fas fa-file-pdf',

                    // Documentos de Word
                    'doc': 'fas fa-file-word text-primary',
                    'docx': 'fas fa-file-word text-primary',
                    'odt': 'fas fa-file-word text-primary',

                    // Hojas de cálculo Excel
                    'xls': 'fas fa-file-excel text-success',
                    'xlsx': 'fas fa-file-excel text-success',
                    'ods': 'fas fa-file-excel text-success',
                    'csv': 'fas fa-file-csv text-success',

                    // Presentaciones PowerPoint
                    'ppt': 'fas fa-file-powerpoint',
                    'pptx': 'fas fa-file-powerpoint',
                    'odp': 'fas fa-file-powerpoint',

                    // Imágenes
                    'jpg': 'fas fa-file-image text-info',
                    'jpeg': 'fas fa-file-image text-info',
                    'png': 'fas fa-file-image text-info',
                    'gif': 'fas fa-file-image text-info',
                    'svg': 'fas fa-file-image text-info',
                    'webp': 'fas fa-file-image text-info',
                    'bmp': 'fas fa-file-image text-info',
                    'ico': 'fas fa-file-image text-info',

                    // Videos
                    'mp4': 'fas fa-file-video text-purple',
                    'avi': 'fas fa-file-video text-purple',
                    'mov': 'fas fa-file-video text-purple',
                    'wmv': 'fas fa-file-video text-purple',
                    'flv': 'fas fa-file-video text-purple',
                    'webm': 'fas fa-file-video text-purple',
                    'mkv': 'fas fa-file-video text-purple',

                    // Audio
                    'mp3': 'fas fa-file-audio text-warning',
                    'wav': 'fas fa-file-audio text-warning',
                    'ogg': 'fas fa-file-audio text-warning',
                    'flac': 'fas fa-file-audio text-warning',
                    'aac': 'fas fa-file-audio text-warning',
                    'm4a': 'fas fa-file-audio text-warning',

                    // Archivos comprimidos
                    'zip': 'fas fa-file-archive text-secondary',
                    'rar': 'fas fa-file-archive text-secondary',
                    '7z': 'fas fa-file-archive text-secondary',
                    'tar': 'fas fa-file-archive text-secondary',
                    'gz': 'fas fa-file-archive text-secondary',
                    'bz2': 'fas fa-file-archive text-secondary',

                    // Código
                    'html': 'fas fa-file-code',
                    'css': 'fas fa-file-code text-primary',
                    'js': 'fas fa-file-code text-warning',
                    'json': 'fas fa-file-code text-success',
                    'xml': 'fas fa-file-code text-warning',
                    'php': 'fas fa-file-code text-purple',
                    'py': 'fas fa-file-code text-info',
                    'java': 'fas fa-file-code',
                    'cpp': 'fas fa-file-code text-primary',
                    'c': 'fas fa-file-code text-primary',
                    'rb': 'fas fa-file-code',
                    'go': 'fas fa-file-code text-info',
                    'ts': 'fas fa-file-code text-primary',
                    'tsx': 'fas fa-file-code text-primary',
                    'vue': 'fas fa-file-code text-success',

                    // Texto plano
                    'txt': 'fas fa-file-alt text-muted',
                    'md': 'fas fa-file-alt text-dark',
                    'log': 'fas fa-file-alt text-muted',
                };

                return iconMap[ext] || 'fas fa-file text-muted';
            },
            getFileExtension(filename) {
                if (!filename) return '';
                const parts = filename.split('.');
                return parts.length > 1 ? parts.pop().toUpperCase() : '';
            },
            viewTrash() {
                toastr.info('Funcionalidad de papelera en desarrollo', 'Información');
            },
            viewRecent() {
                toastr.info('Funcionalidad de recientes en desarrollo', 'Información');
            },
            toggleViewMode(mode) {
                this.viewMode = mode;
            },
            applySortBy(field) {
                this.sortBy = field;
                this.applySorting();
            },
            toggleSortOrder() {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
                this.applySorting();
            },
            applySorting() {
                const sortField = {
                    'name': (a, b) => a.name.localeCompare(b.name),
                    'created_at': (a, b) => new Date(a.created_at) - new Date(b.created_at),
                    'size': (a, b) => (a.size || 0) - (b.size || 0)
                };

                if (sortField[this.sortBy]) {
                    this.files.sort(sortField[this.sortBy]);
                    this.folders.sort(sortField[this.sortBy]);

                    if (this.sortOrder === 'desc') {
                        this.files.reverse();
                        this.folders.reverse();
                    }
                }
            },
            filterByType(type) {
                this.filterType = type;
                this.applyFilter();
            },
            applyFilter() {
                // Reload data with filter
                const typeMap = {
                    'image': 'image',
                    'video': 'video',
                    'document': 'document',
                    'archive': 'archive',
                    'all': ''
                };

                const filterValue = typeMap[this.filterType] || '';

                // Client-side filtering of already loaded files
                if (filterValue === '') {
                    // Show all files
                    this.loadList();
                } else {
                    // Filter files by type
                    const typeFilterMap = {
                        'image': (file) => file.type === 'image',
                        'video': (file) => file.type === 'video',
                        'document': (file) => file.type === 'document',
                        'archive': (file) => file.type === 'archive'
                    };

                    if (typeFilterMap[filterValue]) {
                        // Create a copy and filter
                        const filteredFiles = this.files.filter(typeFilterMap[filterValue]);
                        console.log(`Filtered files: ${filteredFiles.length}`);
                        // Update display
                        this.files = filteredFiles;
                    }
                }
            },
            loadList() {
                if (this.currentView === 'trash' || this.currentView === 'recently_deleted') {
                    this.loadTrash();
                } else if (this.currentView === 'recent') {
                    this.loadRecent();
                } else if (this.currentView === 'favorites') {
                    this.loadFavorites();
                } else {
                    this.loadMedia(this.currentFolderId);
                }
            },
            async switchView(view) {
                this.currentView = view;
                this.currentFolderId = 0;

                if (view === 'trash') {
                    await this.loadTrash();
                } else if (view === 'recent') {
                    await this.loadRecent();
                } else if (view === 'favorites') {
                    await this.loadFavorites();
                } else {
                    await this.loadMedia(0);
                }
            },
            async loadTrash() {
                this.loading = true;
                this.currentPage = 1;
                this.hasMore = true;
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=0&page=1&per_page=30&search=${this.searchQuery}&view=trash`);
                    const data = await response.json();

                    this.folders = data.folders || [];
                    this.files = data.files || [];
                    this.breadcrumbs = [];
                    this.pagination = data.pagination || this.pagination;
                    this.hasMore = (data.pagination?.current_page ?? 0) < (data.pagination?.last_page ?? 0);
                } catch (error) {
                    toastr.error('Error al cargar papelera', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async loadRecent() {
                this.loading = true;
                this.currentPage = 1;
                this.hasMore = true;
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=0&page=1&per_page=30&search=${this.searchQuery}&view=recent`);
                    const data = await response.json();

                    this.folders = data.folders || [];
                    this.files = data.files || [];
                    this.breadcrumbs = [];
                    this.pagination = data.pagination || this.pagination;
                    this.hasMore = (data.pagination?.current_page ?? 0) < (data.pagination?.last_page ?? 0);
                } catch (error) {
                    toastr.error('Error al cargar archivos recientes', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async loadFavorites() {
                this.loading = true;
                this.currentPage = 1;
                this.hasMore = true;
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=0&page=1&per_page=30&search=${this.searchQuery}&view=favorites`);
                    const data = await response.json();

                    this.folders = data.folders || [];
                    this.files = data.files || [];
                    this.breadcrumbs = [];
                    this.pagination = data.pagination || this.pagination;
                    this.hasMore = (data.pagination?.current_page ?? 0) < (data.pagination?.last_page ?? 0);
                } catch (error) {
                    toastr.error('Error al cargar favoritos', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async emptyTrash() {
                if (false) {
                    return;
                }

                try {
                    const response = await fetch('{{ route("media.trash.empty") }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        this.loadTrash();
                        toastr.success('Papelera vaciada exitosamente', 'Éxito');
                    } else {
                        const data = await response.json();
                        toastr.error(data.message || 'Error al vaciar la papelera', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al vaciar la papelera', 'Error');
                }
            },
            async toggleFavorite(file) {
                try {
                    const response = await fetch(`{{ url('panel/media/files') }}/${file.id}/toggle-favorite`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        file.is_favorite = data.is_favorite;
                        toastr.success(data.message, 'Éxito');
                        // Reload if we're in favorites view and the file was unfavorited
                        if (this.currentView === 'favorites' && !data.is_favorite) {
                            this.loadFavorites();
                        }
                    } else {
                        toastr.error('Error al actualizar favorito', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al actualizar favorito', 'Error');
                }
            },
            async restoreFile(file) {
                try {
                    const response = await fetch(`{{ url('panel/media/files') }}/${file.id}/restore`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        this.loadTrash();
                        toastr.success('Archivo restaurado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al restaurar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al restaurar archivo', 'Error');
                }
            },
            async restoreFolder(folder) {
                try {
                    const response = await fetch(`{{ url('panel/media/folders') }}/${folder.id}/restore`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        this.loadTrash();
                        toastr.success('Carpeta restaurada exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al restaurar carpeta', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al restaurar carpeta', 'Error');
                }
            },
            showUploadFromUrlModal() {
                this.uploadUrlData = { url: '', filename: '' };
                new bootstrap.Modal(document.getElementById('modalUploadFromUrl')).show();
            },
            async confirmUploadFromUrl() {
                if (!this.uploadUrlData.url.trim()) return;

                try {
                    const response = await fetch('{{ route("media.files.upload-url") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            url: this.uploadUrlData.url,
                            filename: this.uploadUrlData.filename,
                            folder_id: this.currentFolderId > 0 ? this.currentFolderId : null
                        })
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalUploadFromUrl')).hide();
                        this.loadList();
                        toastr.success('Archivo cargado exitosamente', 'Éxito');
                    } else {
                        const error = await response.json();
                        toastr.error('Error: ' + (error.message || 'No se pudo descargar el archivo'), 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al descargar el archivo desde la URL', 'Error');
                }
            },
            // Enhanced Upload Modal Methods
            showUploadModal() {
                this.uploadQueue = [];
                new bootstrap.Modal(document.getElementById('modalEnhancedUpload')).show();
            },
            handleDragOver(event) {
                this.isDragging = true;
            },
            handleDragLeave(event) {
                this.isDragging = false;
            },
            handleDrop(event) {
                this.isDragging = false;
                const files = event.dataTransfer.files;
                this.addFilesToQueue(files);
            },
            handleFilesSelect(event) {
                const files = event.target.files;
                this.addFilesToQueue(files);
                // Reset input so same file can be selected again
                event.target.value = '';
            },
            handleFilesDrop(event) {
                this.isDragging = false;
                const files = event.dataTransfer.files;
                this.addFilesToQueue(files);
            },
            addFilesToQueue(files) {
                const maxSize = 100 * 1024 * 1024; // 100MB in bytes

                Array.from(files).forEach(file => {
                    if (file.size > maxSize) {
                        toastr.warning(`El archivo "${file.name}" excede el tamaño máximo de 100MB`, 'Advertencia');
                        return;
                    }

                    // Check if file already exists in queue
                    const exists = this.uploadQueue.some(item =>
                        item.file.name === file.name && item.file.size === file.size
                    );

                    if (exists) {
                        toastr.info(`El archivo "${file.name}" ya está en la cola`, 'Información');
                        return;
                    }

                    this.uploadQueue.push({
                        file: file,
                        progress: 0,
                        uploading: false
                    });
                });

                if (files.length > 0) {
                    toastr.success(`${files.length} archivo(s) agregado(s) a la cola`, 'Éxito');
                }
            },
            removeFromQueue(index) {
                const fileName = this.uploadQueue[index].file.name;
                this.uploadQueue.splice(index, 1);
                toastr.info(`"${fileName}" eliminado de la cola`, 'Información');
            },
            clearUploadQueue() {
                this.uploadQueue = [];
                toastr.info('Cola de subida limpiada', 'Información');
            },
            async startUpload() {
                if (this.uploadQueue.length === 0 || this.isUploading) return;

                this.isUploading = true;

                // Upload files sequentially
                for (let i = 0; i < this.uploadQueue.length; i++) {
                    await this.uploadFile(i);
                }

                this.isUploading = false;
                this.uploadQueue = [];

                bootstrap.Modal.getInstance(document.getElementById('modalEnhancedUpload')).hide();
                this.loadList();
                toastr.success('Todos los archivos se han subido exitosamente', 'Éxito');
            },
            async uploadFile(index) {
                const item = this.uploadQueue[index];
                item.uploading = true;
                item.progress = 0;

                const formData = new FormData();
                formData.append('file', item.file);
                if (this.currentFolderId > 0) {
                    formData.append('folder_id', this.currentFolderId);
                }

                try {
                    const xhr = new XMLHttpRequest();

                    // Track upload progress
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            item.progress = Math.round((e.loaded / e.total) * 100);
                        }
                    });

                    // Handle completion
                    await new Promise((resolve, reject) => {
                        xhr.addEventListener('load', () => {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                item.progress = 100;
                                item.uploading = false;
                                resolve();
                            } else {
                                reject(new Error(`Upload failed with status ${xhr.status}`));
                            }
                        });

                        xhr.addEventListener('error', () => {
                            reject(new Error('Network error during upload'));
                        });

                        xhr.open('POST', '{{ route("media.files.upload") }}');
                        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                        xhr.send(formData);
                    });

                } catch (error) {
                    item.uploading = false;
                    item.progress = 0;
                    toastr.error(`Error al subir "${item.file.name}"`, 'Error');
                    throw error;
                }
            },
            getUploadFileIcon(file) {
                const ext = file.name.split('.').pop().toLowerCase();
                const iconMap = {
                    // PDF
                    'pdf': 'fas fa-file-pdf text-danger',

                    // Word
                    'doc': 'fas fa-file-word text-primary',
                    'docx': 'fas fa-file-word text-primary',

                    // Excel
                    'xls': 'fas fa-file-excel text-success',
                    'xlsx': 'fas fa-file-excel text-success',
                    'csv': 'fas fa-file-csv text-success',

                    // PowerPoint
                    'ppt': 'fas fa-file-powerpoint text-danger',
                    'pptx': 'fas fa-file-powerpoint text-danger',

                    // Images
                    'jpg': 'fas fa-file-image text-info',
                    'jpeg': 'fas fa-file-image text-info',
                    'png': 'fas fa-file-image text-info',
                    'gif': 'fas fa-file-image text-info',
                    'svg': 'fas fa-file-image text-info',
                    'webp': 'fas fa-file-image text-info',

                    // Videos
                    'mp4': 'fas fa-file-video text-purple',
                    'avi': 'fas fa-file-video text-purple',
                    'mov': 'fas fa-file-video text-purple',
                    'webm': 'fas fa-file-video text-purple',

                    // Audio
                    'mp3': 'fas fa-file-audio text-warning',
                    'wav': 'fas fa-file-audio text-warning',
                    'ogg': 'fas fa-file-audio text-warning',

                    // Archives
                    'zip': 'fas fa-file-archive text-secondary',
                    'rar': 'fas fa-file-archive text-secondary',
                    '7z': 'fas fa-file-archive text-secondary',

                    // Code
                    'html': 'fas fa-file-code text-danger',
                    'css': 'fas fa-file-code text-primary',
                    'js': 'fas fa-file-code text-warning',
                    'php': 'fas fa-file-code text-purple',
                    'json': 'fas fa-file-code text-success',
                    'xml': 'fas fa-file-code text-warning'
                };

                return iconMap[ext] || 'fas fa-file text-muted';
            },
            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';

                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));

                return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
            },

            // Context Menu Methods
            showContextMenu(event, type, item) {
                this.contextMenu.type = type;
                this.contextMenu.item = item;
                this.contextMenu.x = event.clientX;
                this.contextMenu.y = event.clientY;

                const menu = document.getElementById('contextMenu');
                menu.style.left = event.clientX + 'px';
                menu.style.top = event.clientY + 'px';
                menu.style.display = 'block';
            },

            hideContextMenu() {
                const menu = document.getElementById('contextMenu');
                menu.style.display = 'none';
                this.contextMenu.type = null;
                this.contextMenu.item = null;
            },

            async handleContextAction(action) {
                const item = this.contextMenu.item;
                const type = this.contextMenu.type;

                this.hideContextMenu();

                if (!item) return;

                try {
                    switch (action) {
                        case 'open':
                            if (type === 'folder') {
                                this.navigateToFolder(item.id);
                            } else if (type === 'file') {
                                window.open(item.url, '_blank');
                            }
                            break;

                        case 'download':
                            if (type === 'file') {
                                this.downloadFile(item);
                            }
                            break;

                        case 'rename':
                            if (type === 'folder') {
                                this.showRenameFolderModal(item);
                            } else if (type === 'file') {
                                this.showRenameFileModal(item);
                            }
                            break;

                        case 'move':
                            if (type === 'folder') {
                                this.showMoveFolderModal(item);
                            } else if (type === 'file') {
                                this.showMoveFileModal(item);
                            }
                            break;

                        case 'copy':
                            if (type === 'file') {
                                await this.copyFile(item);
                            }
                            break;

                        case 'favorite':
                            if (type === 'file') {
                                await this.toggleFavorite(item);
                            }
                            break;

                        case 'delete':
                            if (type === 'folder') {
                                await this.deleteFolder(item);
                            } else if (type === 'file') {
                                await this.deleteFile(item);
                            }
                            break;

                        case 'restore':
                            if (type === 'folder') {
                                await this.restoreFolder(item);
                            } else if (type === 'file') {
                                await this.restoreFile(item);
                            }
                            break;

                        case 'details':
                            this.showFileDetails(item);
                            break;
                    }
                } catch (error) {
                    console.error('Context action error:', error);
                    toastr.error('Error al ejecutar la acción');
                }
            },

            // Multiple Selection Methods
            isItemSelected(type, id) {
                return this.selectedItems.some(item => item.type === type && item.id === id);
            },

            toggleSelection(type, id, item) {
                const index = this.selectedItems.findIndex(i => i.type === type && i.id === id);
                if (index > -1) {
                    this.selectedItems.splice(index, 1);
                } else {
                    this.selectedItems.push({ type, id, item });
                }
            },

            handleCardClick(event, type, item) {
                if (this.pickerMode && type === 'file') {
                    window.parent.postMessage({ type: 'media-picker-select', url: item.public_url, name: item.name }, '*');
                    return;
                }
                // If there are selected items, treat click as selection toggle
                if (this.selectedItems.length > 0) {
                    this.toggleSelection(type, item.id, item);
                } else {
                    // Normal behavior - navigate to folder or do nothing for files
                    if (type === 'folder') {
                        this.navigateToFolder(item.id);
                    }
                }
            },

            clearSelection() {
                this.selectedItems = [];
            },

            async bulkDelete() {
                if (false) {
                    return;
                }
                await this.bulkRequest('delete');
            },

            async bulkRestore() {
                await this.bulkRequest('restore');
            },

            async bulkRequest(action, folderId = null) {
                const fileIds = this.selectedItems.filter(i => i.type === 'file').map(i => i.id);
                const folderIds = this.selectedItems.filter(i => i.type === 'folder').map(i => i.id);
                const batches = [];
                if (fileIds.length) batches.push({ type: 'file', ids: fileIds });
                if (folderIds.length) batches.push({ type: 'folder', ids: folderIds });

                for (const batch of batches) {
                    try {
                        await $.ajax({
                            url: '{{ route("media.bulk-action") }}',
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            data: { action, type: batch.type, ids: batch.ids, folder_id: folderId }
                        });
                    } catch (e) {
                        toastr.error('Error al aplicar la acción masiva');
                        return;
                    }
                }

                toastr.success('Acción aplicada correctamente');
                this.clearSelection();
                this.loadList();
            },

            async bulkDownload() {
                const files = this.selectedItems.filter(item => item.type === 'file');

                for (const { item } of files) {
                    this.downloadFile(item);
                    // Add small delay between downloads to avoid browser blocking
                    await new Promise(resolve => setTimeout(resolve, 300));
                }

                toastr.success(`Descargando ${files.length} archivo(s)`);
            },

            async bulkMove(folderId = null) {
                if (folderId === null) {
                    toastr.info('Función de mover múltiples elementos próximamente');
                    return;
                }
                await this.bulkRequest('move', folderId);
            },

            openPreview(item) {
                if (item.is_folder) return;
                this.previewItem = item;
                this.previewItemName = item.name;
                this.previewType = item.type || '';
                this.previewUrl = item.public_url || item.url;
                const modal = new bootstrap.Modal(document.getElementById('mediaPreviewModal'));
                modal.show();
            },

            // Storage quota
            async loadQuota() {
                try {
                    const res = await $.get('{{ url("panel/media/quota") }}');
                    this.storageUsed = res.used || 0;
                    this.storageTotal = res.total || 0;
                    this.quotaEnabled = res.enabled || false;
                } catch (e) {
                    // Endpoint may not exist yet, ignore silently
                }
            },
            formatBytes(bytes) {
                if (!bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let i = 0;
                let size = bytes;
                while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
                return (Math.round(size * 100) / 100) + ' ' + units[i];
            },

            // Folder upload (webkitdirectory)
            async handleFolderUpload(event) {
                const files = Array.from(event.target.files);
                event.target.value = '';
                if (!files.length) return;

                const foldersMap = new Map();
                files.forEach(file => {
                    const parts = file.webkitRelativePath.split('/');
                    const folderPath = parts.slice(0, -1).join('/');
                    if (!foldersMap.has(folderPath)) foldersMap.set(folderPath, []);
                    foldersMap.get(folderPath).push(file);
                });

                toastr.info(`Subiendo ${files.length} archivos en ${foldersMap.size} carpetas...`);

                const folderIdMap = new Map();
                folderIdMap.set('', this.currentFolderId > 0 ? this.currentFolderId : null);

                const sortedPaths = [...foldersMap.keys()].sort();
                for (const path of sortedPaths) {
                    if (path === '') continue;
                    const parentPath = path.split('/').slice(0, -1).join('/');
                    const name = path.split('/').pop();
                    const parentId = folderIdMap.get(parentPath) || null;

                    try {
                        const res = await $.ajax({
                            url: '{{ route("media.folders.create") }}',
                            method: 'POST',
                            contentType: 'application/json',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            data: JSON.stringify({ name, parent_id: parentId })
                        });
                        folderIdMap.set(path, res.folder?.id || null);
                    } catch (e) {
                        folderIdMap.set(path, parentId);
                    }
                }

                let completed = 0;
                for (const [path, pathFiles] of foldersMap.entries()) {
                    const folderId = folderIdMap.get(path);
                    for (const file of pathFiles) {
                        const fd = new FormData();
                        fd.append('file', file);
                        if (folderId) fd.append('folder_id', folderId);
                        try {
                            await $.ajax({
                                url: '{{ route("media.files.upload") }}',
                                method: 'POST',
                                data: fd,
                                processData: false,
                                contentType: false,
                                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            });
                            completed++;
                        } catch (e) {
                            console.error('Upload failed', file.name, e);
                        }
                    }
                }

                toastr.success(`Completado: ${completed}/${files.length} archivos subidos`);
                this.loadFiles();
            },

            // Duplicates
            openDuplicatesModal() {
                new bootstrap.Modal(document.getElementById('duplicatesModal')).show();
                this.loadDuplicates();
            },
            async loadDuplicates() {
                this.duplicatesLoading = true;
                try {
                    const res = await $.get('{{ url("panel/media/duplicates") }}', { mode: this.duplicatesMode });
                    this.duplicatesGroups = res.groups || [];
                } catch (e) {
                    toastr.error('Error al cargar duplicados');
                } finally {
                    this.duplicatesLoading = false;
                }
            },

            // Image editor
            isImage(item) {
                return item && !item.is_folder && item.type === 'image';
            },
            openImageEditor(item) {
                this.editorItem = item;
                this.editorImageUrl = item.public_url || item.url;
                const modal = new bootstrap.Modal(document.getElementById('imageEditorModal'));
                modal.show();
                this.$nextTick(() => {
                    const img = document.getElementById('imageEditorImg');
                    if (this.cropperInstance) this.cropperInstance.destroy();
                    this.cropperInstance = new Cropper(img, { viewMode: 1, autoCrop: false });
                });
            },
            cropperAction(action, value) {
                if (!this.cropperInstance) return;
                switch (action) {
                    case 'rotate': this.cropperInstance.rotate(value); break;
                    case 'scaleX': this.cropperInstance.scaleX(value); break;
                    case 'scaleY': this.cropperInstance.scaleY(value); break;
                    case 'reset': this.cropperInstance.reset(); break;
                }
            },
            async saveEditedImage() {
                if (!this.cropperInstance || !this.editorItem) return;
                const canvas = this.cropperInstance.getCroppedCanvas();
                canvas.toBlob(async (blob) => {
                    const fd = new FormData();
                    fd.append('file', blob, this.editorItem.name);
                    if (this.editorItem.folder_id) fd.append('folder_id', this.editorItem.folder_id);

                    try {
                        await $.ajax({
                            url: '{{ route("media.files.upload") }}',
                            method: 'POST',
                            data: fd,
                            processData: false,
                            contentType: false,
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        });
                        toastr.success('Imagen editada guardada como nueva');
                        bootstrap.Modal.getInstance(document.getElementById('imageEditorModal')).hide();
                        this.loadFiles();
                    } catch (e) {
                        toastr.error('Error al guardar imagen editada');
                    }
                }, 'image/jpeg', 0.9);
            },

            loadFiles() {
                this.loadList();
            },

            // Tags methods
            async loadTags() {
                try {
                    const res = await $.get('/panel/media/tags');
                    this.availableTags = res.tags || res.data || res || [];
                } catch (e) {
                    // Tags endpoint may not exist yet
                }
            },
            async createTag() {
                if (!this.newTagName.trim()) return;
                try {
                    await $.ajax({
                        url: '/panel/media/tags',
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: { name: this.newTagName, color: this.newTagColor }
                    });
                    this.newTagName = '';
                    this.loadTags();
                    toastr.success('Tag creado');
                } catch (e) {
                    toastr.error('Error al crear tag');
                }
            },
            async deleteTag(tag) {
                
                try {
                    await $.ajax({
                        url: `/panel/media/tags/${tag.id}`,
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    });
                    this.loadTags();
                    toastr.success('Tag eliminado');
                } catch (e) {
                    toastr.error('Error al eliminar tag');
                }
            },
            openTagsManager() {
                new bootstrap.Modal(document.getElementById('tagsManagerModal')).show();
            },
            filterByTags() {
                this.loadList();
            },

            // Versions methods
            async openVersions(item) {
                this.currentVersionedItem = item;
                this.fileVersions = [];
                new bootstrap.Modal(document.getElementById('versionsModal')).show();
                try {
                    const res = await $.get(`/panel/media/files/${item.id}/versions`);
                    this.fileVersions = res.versions || [];
                } catch (e) {
                    // Endpoint stub - no versions yet
                }
            },
            async restoreVersion(version) {
                
                try {
                    await $.ajax({
                        url: `/panel/media/files/${this.currentVersionedItem.id}/versions/${version.id}/restore`,
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    });
                    toastr.success('Versión restaurada');
                    this.loadFiles();
                    bootstrap.Modal.getInstance(document.getElementById('versionsModal')).hide();
                } catch (e) {
                    toastr.error('Error al restaurar versión');
                }
            },

            // Access logs methods
            async openAccessLogs(item) {
                this.accessLogs = [];
                new bootstrap.Modal(document.getElementById('accessLogsModal')).show();
                try {
                    const res = await $.get(`/panel/media/files/${item.id}/access-logs`);
                    this.accessLogs = res.logs || res.data || [];
                } catch (e) {
                    // Endpoint stub
                }
            },

            // Share methods
            openShare(item) {
                this.shareItem = item;
                this.generatedShareUrl = '';
                new bootstrap.Modal(document.getElementById('shareModal')).show();
            },
            async generateShareLink() {
                if (!this.shareItem) return;
                try {
                    const res = await $.ajax({
                        url: `/panel/media/files/${this.shareItem.id}/share`,
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: { ttl_minutes: this.shareTtl }
                    });
                    this.generatedShareUrl = res.url || res.share_url || '';
                    if (this.generatedShareUrl) {
                        this.$nextTick(() => {
                            const container = document.getElementById('shareQrCode');
                            container.innerHTML = '';
                            new QRCode(container, { text: this.generatedShareUrl, width: 160, height: 160 });
                        });
                    }
                } catch (e) {
                    toastr.error('Error al generar link de compartir');
                }
            },
            copyShareUrl() {
                navigator.clipboard.writeText(this.generatedShareUrl);
                toastr.success('URL copiada');
            },
            async shareWithUser() {
                if (!this.shareItem) return;
                try {
                    await $.ajax({
                        url: '/panel/media/shares',
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: {
                            shareable_id: this.shareItem.id,
                            shareable_type: this.shareItem.is_folder ? 'folder' : 'file',
                            shared_with_user_id: this.shareWithUserId,
                            role: this.shareRole,
                        }
                    });
                    toastr.success('Compartido correctamente');
                    bootstrap.Modal.getInstance(document.getElementById('shareModal')).hide();
                } catch (e) {
                    toastr.error('Error al compartir');
                }
            },

            // Expiration methods
            openExpiration(item) {
                this.expirationItem = item;
                this.expiresAt = item.expires_at ? item.expires_at.substring(0, 16) : '';
                new bootstrap.Modal(document.getElementById('expirationModal')).show();
            },
            async saveExpiration() {
                if (!this.expirationItem) return;
                try {
                    await $.ajax({
                        url: `/panel/media/files/${this.expirationItem.id}/expiration`,
                        method: 'PUT',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: { expires_at: this.expiresAt }
                    });
                    toastr.success('Expiración guardada');
                    bootstrap.Modal.getInstance(document.getElementById('expirationModal')).hide();
                    this.loadFiles();
                } catch (e) {
                    toastr.error('Error al guardar expiración');
                }
            },

            // Batch rename
            async batchRename() {
                const pattern = prompt('Patrón de renombre (ej: foto_{index}.jpg). Usa {name}, {index}, {ext}');
                if (!pattern) return;

                const fileItems = this.selectedItems.filter(item => item.type === 'file');
                let i = 1;
                for (const selected of fileItems) {
                    const file = this.files.find(f => f.id === selected.id);
                    if (!file) { i++; continue; }

                    const ext = file.name.split('.').pop();
                    const name = file.name.replace(/\.[^.]+$/, '');
                    const newName = pattern
                        .replace('{name}', name)
                        .replace('{index}', i)
                        .replace('{ext}', ext);

                    try {
                        await $.ajax({
                            url: `/panel/media/files/${file.id}/rename`,
                            method: 'PUT',
                            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                            contentType: 'application/json',
                            data: JSON.stringify({ name: newName })
                        });
                    } catch (e) {
                        console.error('Rename failed for', file.name);
                    }
                    i++;
                }

                toastr.success(`${fileItems.length} archivos renombrados`);
                this.loadFiles();
            },

            // Utility
            formatDate(dateStr) {
                if (!dateStr) return '—';
                return new Date(dateStr).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
            },

            selectAll() {
                const allFiles = this.files.map(f => ({ type: 'file', id: f.id, item: f }));
                const allFolders = this.folders.map(f => ({ type: 'folder', id: f.id, item: f }));
                this.selectedItems = [...allFolders, ...allFiles];
            },

            // Infinite scroll
            async loadMore() {
                if (this.loadingMore || !this.hasMore) return;
                this.loadingMore = true;
                this.currentPage++;
                try {
                    const res = await $.get('{{ route("media.list") }}', {
                        folder_id: this.currentFolderId || 0,
                        page: this.currentPage,
                        per_page: 30,
                        view: this.currentView === 'recently_deleted' ? 'trash' : (this.currentView === 'all' ? '' : this.currentView),
                        search: this.searchQuery,
                    });
                    this.files = this.files.concat(res.files || []);
                    this.hasMore = (res.pagination?.current_page ?? 0) < (res.pagination?.last_page ?? 0);
                } catch (e) {
                    this.currentPage--;
                } finally {
                    this.loadingMore = false;
                }
            },

            // Feature 3: setView - adds recently_deleted support
            setView(view) {
                if (view === 'recently_deleted') {
                    this.currentView = 'recently_deleted';
                    this.currentFolderId = 0;
                    this.loadTrash();
                } else {
                    this.switchView(view);
                }
            },

            // Feature 2: Heatmap
            async openHeatmap() {
                new bootstrap.Modal(document.getElementById('heatmapModal')).show();
                const res = await $.get('{{ route("media.list") }}', { view: 'recent' }).catch(() => ({ files: [] }));
                const days = [];
                for (let i = 89; i >= 0; i--) {
                    const d = new Date();
                    d.setDate(d.getDate() - i);
                    days.push({ date: d.toISOString().slice(0, 10), count: 0, level: 0 });
                }
                (res.files || []).forEach(f => {
                    const iso = (f.created_at || '').slice(0, 10);
                    const day = days.find(x => x.date === iso);
                    if (day) day.count++;
                });
                const max = Math.max(1, ...days.map(d => d.count));
                days.forEach(d => { d.level = Math.min(4, Math.floor(d.count / (max / 4))); });
                this.heatmapDays = days;
            },

            // Feature 4: Activity log
            async openActivityLog() {
                new bootstrap.Modal(document.getElementById('activityLogModal')).show();
                await this.loadActivityLog();
            },
            async loadActivityLog() {
                try {
                    const res = await $.get('/panel/media/activity-log', { filter: this.activityLogFilter });
                    this.activityLogEntries = res.data || res.logs || [];
                } catch (e) {
                    // Endpoint may not exist yet — show empty state
                    this.activityLogEntries = [];
                }
            },
        },
        watch: {
            selectedItems(newVal) {
                // Toggle selection mode class on body
                if (newVal.length > 0) {
                    document.body.classList.add('selection-mode');
                } else {
                    document.body.classList.remove('selection-mode');
                }
            }
        },
        mounted() {
            this.loadMedia();
            this.loadQuota();
            this.loadTags();

            // Infinite scroll observer
            this.$nextTick(() => {
                if (this.$refs.scrollSentinel) {
                    this.scrollObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting && this.hasMore && !this.loadingMore) {
                                this.loadMore();
                            }
                        });
                    }, { rootMargin: '200px' });
                    this.scrollObserver.observe(this.$refs.scrollSentinel);
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Delete' && this.selectedItems.length > 0) {
                    e.preventDefault();
                    this.bulkDelete();
                } else if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    this.selectAll();
                } else if (e.ctrlKey && e.key === 'd') {
                    e.preventDefault();
                    this.selectedItems = [];
                } else if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
                    e.preventDefault();
                    document.querySelector('.media-search-input')?.focus();
                }
            });

            // Hide context menu when clicking anywhere else
            document.addEventListener('click', () => {
                this.hideContextMenu();
            });

            // Initialize selects after jQuery is ready
            const vm = this;

            const initSelects = () => {
                if (typeof jQuery === 'undefined') {
                    setTimeout(initSelects, 200);
                    return;
                }

                jQuery('#mediaDiskSelect').val(vm.activeDisk).on('change', function () {
                    vm.changeActiveDisk(jQuery(this).val());
                });

                jQuery('#mediaSortBy').select2({ width: '100%', minimumResultsForSearch: -1 }).on('change', function () {
                    vm.applySortBy(jQuery(this).val());
                });
                jQuery('#mediaFilterType').select2({ width: '100%', minimumResultsForSearch: -1 }).on('change', function () {
                    vm.filterByType(jQuery(this).val());
                });
            };

            setTimeout(initSelects, 500);
        }
    }).mount('#mediaManagerApp');
</script>
@endpush
