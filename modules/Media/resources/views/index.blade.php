@extends('layouts.theme')

@section('title', 'Gestor de Medios')

@push('styles')
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
        background: linear-gradient(90deg, var(--stat-color), var(--stat-color));
    }

    .stat-card:hover {
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        transform: translateY(-4px);
    }

    .stat-card.folders { --stat-color: #5D87FF; }
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
        border-color: #5D87FF;
    }

    .folder-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .folder-card:hover {
        box-shadow: 0 8px 24px rgba(93, 135, 255, 0.15) !important;
        transform: translateY(-4px);
        border-color: #5D87FF !important;
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
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #4dabf7 0%, #228be6 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%);
    }

    .bg-gradient-purple {
        background: linear-gradient(135deg, #cc5de8 0%, #9c36b5 100%);
    }

    .bg-gradient-dark {
        background: linear-gradient(135deg, #495057 0%, #343a40 100%);
    }

    .bg-gradient-secondary {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
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
        border-color: #5D87FF;
        color: #5D87FF;
        box-shadow: 0 2px 8px rgba(93, 135, 255, 0.15);
    }

    .upload-zone {
        border: 2px dashed #5D87FF;
        border-radius: 12px;
        background: linear-gradient(135deg, #f0f4ff 0%, #f8f9ff 100%);
        transition: all 0.3s ease;
    }

    .upload-zone:hover {
        border-color: #3E5BDB;
        background: linear-gradient(135deg, #e8f0ff 0%, #f0f4ff 100%);
        box-shadow: 0 4px 16px rgba(93, 135, 255, 0.12);
    }

    .upload-zone.drag-over {
        border-color: #3E5BDB;
        background: linear-gradient(135deg, #e0e8ff 0%, #e8f0ff 100%);
        box-shadow: 0 6px 20px rgba(93, 135, 255, 0.2);
    }

    /* Upload Zone Modern */
    .upload-zone-modern {
        background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        border: 2px dashed #e0e7ff;
        transition: all 0.3s ease;
    }

    .upload-zone-modern:hover {
        border-color: #5D87FF;
        background: linear-gradient(135deg, #f0f4ff 0%, #f8f9ff 100%);
        box-shadow: 0 4px 16px rgba(93, 135, 255, 0.1);
    }

    .upload-zone-modern.drag-active {
        border-color: #5D87FF;
        background: linear-gradient(135deg, #e8f0ff 0%, #f0f4ff 100%);
        box-shadow: 0 6px 20px rgba(93, 135, 255, 0.2);
        transform: scale(1.01);
    }

    .upload-icon-wrapper {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #5D87FF 0%, #4570ea 100%);
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
        background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
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
        background: linear-gradient(135deg, #f0f4ff 0%, #f8f9ff 100%);
        border-color: #5D87FF !important;
        box-shadow: 0 4px 12px rgba(93, 135, 255, 0.15);
        transform: translateY(-2px);
    }

    .folder-file-item i {
        transition: all 0.3s ease;
    }

    .folder-file-item:hover i {
        color: #5D87FF !important;
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
        color: #5D87FF;
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
        color: #5D87FF;
        border-bottom-color: rgba(93, 135, 255, 0.3);
        background: rgba(93, 135, 255, 0.05) !important;
    }

    .user-profile-tab .nav-link.active {
        color: #5D87FF;
        border-bottom-color: #5D87FF;
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
        color: #5D87FF;
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
        border: 3px solid #5D87FF !important;
        background: linear-gradient(135deg, #f0f4ff 0%, #fafbff 100%);
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
        background: #5D87FF;
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
        background: linear-gradient(135deg, #f0f4ff 0%, #fafbff 100%) !important;
        border-bottom: 2px solid #5D87FF !important;
    }

    /* Enhanced sidebar-like navigation */
    .user-profile-tab {
        background: linear-gradient(135deg, #fafbff 0%, #ffffff 100%);
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
        background: #5D87FF;
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

@section('content')
<div id="mediaManagerApp">


    @include('core::components.card', ['title' => 'Gestor de Medios'])

    <div v-if="loading" class="d-flex justify-content-center align-items-center" style="height: 400px;">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
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
                <div v-if="selectedItems.length === 0" class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Gestor de Medios</h5>
                        <p class="small mb-0 text-muted">Organiza y gestiona todos tus archivos y carpetas en un solo lugar</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        {{-- Filesystem Selector --}}
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 text-muted small"><i class="fas fa-hdd me-1"></i>Almacenamiento:</label>
                            <select v-model="activeDisk" @change="changeActiveDisk" class="form-select form-select-sm" style="min-width: 150px;">
                                @foreach($availableDisks as $disk)
                                <option value="{{ $disk['name'] }}">{{ $disk['label'] }} ({{ $disk['driver'] }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button v-if="currentView === 'all'" @click="showNewFolderModal" class="btn btn-primary">
                            <i class="fas fa-folder-plus me-1"></i>Nueva Carpeta
                        </button>
                    </div>
                </div>

                {{-- Selection Toolbar (replaces header when items are selected) --}}
                <div v-else class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <button @click="clearSelection" class="btn btn-sm btn-light rounded-circle" style="width: 36px; height: 36px; padding: 0;" title="Cancelar selección">
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
                            <button @click="bulkRestore" class="btn btn-success">
                               Restaurar
                            </button>
                        </template>
                        <template v-else>
                            <button @click="bulkMove" class="btn btn-outline-primary">
                                Mover
                            </button>
                            <button @click="bulkDownload" class="btn btn-outline-info" v-if="hasOnlyFiles">
                                Descargar
                            </button>
                            <button @click="bulkDelete" class="btn btn-outline-danger">
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
            <ul class="nav nav-pills user-profile-tab border-bottom" id="media-view-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'all'}"
                        @click="switchView('all')"
                        type="button"
                        role="tab">

                        <span class="d-none d-md-block">Mis Archivos</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'recent'}"
                        @click="switchView('recent')"
                        type="button"
                        role="tab">

                        <span class="d-none d-md-block">Recientes</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'favorites'}"
                        @click="switchView('favorites')"
                        type="button"
                        role="tab">
                        <span class="d-none d-md-block">Favoritos</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        :class="{'active': currentView === 'trash'}"
                        @click="switchView('trash')"
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
                    class="upload-zone-modern rounded-3 p-4"
                    :class="{ 'drag-active': isDragging }"
                    @dragover.prevent="handleDragOver"
                    @dragleave.prevent="handleDragLeave"
                    @drop.prevent="handleDrop">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="upload-icon-wrapper">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h6 class="fw-bold mb-1">Subir archivos</h6>
                            <p class="mb-3 text-muted small">
                                <i class="fas fa-hand-pointer me-1"></i>Arrastra archivos aquí o usa los botones
                                <span class="d-block d-sm-inline ms-0 ms-sm-2">
                                    <i class="fas fa-info-circle me-1"></i>Límite: <strong>100MB</strong> por archivo
                                </span>
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <button @click="showUploadModal" class="btn btn-primary btn-sm rounded-pill px-3">
                                    <i class="fas fa-upload me-1"></i>Seleccionar archivos
                                </button>
                                <button @click="showUploadFromUrlModal" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    <i class="fas fa-link me-1"></i>Desde URL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Toolbar --}}
            <div class="card-body border-bottom">
                <div class="row align-items-center g-2">
                    {{-- Trash View Actions --}}
                    <div v-if="currentView === 'trash'" class="col-auto">
                        <button class="btn btn-danger" @click="emptyTrash" :disabled="files.length === 0 && folders.length === 0">
                            <i class="fas fa-trash-can me-2"></i>Vaciar Papelera
                        </button>
                    </div>

                    {{-- Standard View Actions --}}
                    <div v-else class="col-auto">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Ordenar">
                                <i class="fas fa-sort me-1"></i>Ordenar
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" @click.prevent="applySortBy('name')"><i class="fas fa-sort-a-z me-2"></i>Nombre</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="applySortBy('created_at')"><i class="fas fa-calendar me-2"></i>Fecha</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="applySortBy('size')"><i class="fas fa-code me-2"></i>Tamaño</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="toggleSortOrder()"><i class="me-2" :class="sortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>@{{ sortOrder === 'asc' ? 'Ascendente' : 'Descendente' }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div v-if="currentView !== 'trash'" class="col-auto">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Filtrar">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" @click.prevent="filterByType('all')"><i class="fas fa-file me-2"></i>Todos los archivos</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="filterByType('image')"><i class="fas fa-image me-2"></i>Imágenes</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="filterByType('video')"><i class="fas fa-video me-2"></i>Videos</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="filterByType('document')"><i class="fas fa-file-alt me-2"></i>Documentos</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="filterByType('archive')"><i class="fas fa-file-archive me-2"></i>Archivos</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <button @click="loadList" class="btn btn-outline-primary" title="Recargar">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Breadcrumbs & Search --}}
            <div class="card-body border-bottom">
                <div class="row align-items-center">
                    {{-- Breadcrumbs - Only visible in "all" view --}}
                    <div v-if="currentView === 'all'" class="col-md-6">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="#" class="text-primary text-decoration-none" @click.prevent="navigateToFolder(0)">
                                        <i class="fas fa-home me-1"></i>Inicio
                                    </a>
                                </li>
                                <li v-for="(item, idx) in breadcrumbs" :key="idx" class="breadcrumb-item" :class="{ active: idx === breadcrumbs.length - 1 }">
                                    <a v-if="idx !== breadcrumbs.length - 1" href="#" class="text-primary text-decoration-none" @click.prevent="navigateToFolder(item.id)">
                                        @{{ item.name }}
                                    </a>
                                    <span v-else class="fw-semibold">@{{ item.name }}</span>
                                </li>
                            </ol>
                        </nav>
                    </div>

                    {{-- View Title for special views --}}
                    <div v-else class="col-md-6">
                        <h6 class="mb-0 fw-bold text-muted">
                            <i v-if="currentView === 'recent'" class="fas fa-clock me-2"></i>
                            <i v-if="currentView === 'favorites'" class="fas fa-star me-2"></i>
                            <i v-if="currentView === 'trash'" class="fas fa-trash me-2"></i>
                            @{{ getViewTitle }}
                        </h6>
                    </div>

                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                            <input type="text" v-model="searchQuery" @input="performSearch" class="form-control" placeholder="Buscar archivos y carpetas..." />
                            <button v-if="searchQuery" @click="clearSearch" class="btn btn-outline-danger" title="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Content Area --}}
            <div class="card-body" style="min-height: 400px;">
                {{-- Empty State --}}
                <div v-if="files.length === 0 && folders.length === 0" class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i v-if="currentView === 'all'" class="fas fa-folder fs-1 text-muted"></i>
                        <i v-if="currentView === 'recent'" class="fas fa-clock fs-1 text-muted"></i>
                        <i v-if="currentView === 'favorites'" class="fas fa-star fs-1 text-muted"></i>
                        <i v-if="currentView === 'trash'" class="fas fa-trash fs-1 text-muted"></i>
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
                             @click="handleCardClick($event, 'folder', folder)"
                             @contextmenu.prevent="showContextMenu($event, 'folder', folder)"
                             style="cursor: pointer;">
                            {{-- Selection Checkbox --}}
                            <div class="selection-checkbox"
                                 :class="{ 'visible': selectedItems.length > 0 }"
                                 @click.stop>
                                <input type="checkbox"
                                       :checked="isItemSelected('folder', folder.id)"
                                       @change="toggleSelection('folder', folder.id, folder)">
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
                                    <div class="ms-auto" @click.stop>
                                        <div class="dropdown dropstart">
                                            <a href="javascript:void(0)" class="link text-dark p-2" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <template v-if="currentView === 'trash'">
                                                    <li><a class="dropdown-item" href="#" @click.prevent="restoreFolder(folder)">Restaurar</a></li>
                                                </template>
                                                <template v-else>
                                                    <li><a class="dropdown-item" href="#" @click.prevent="navigateToFolder(folder.id)">Abrir</a></li>
                                                    <li><a class="dropdown-item" href="#" @click.prevent="renameFolder(folder)">Renombrar</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="#" @click.prevent="deleteFolder(folder)">Eliminar</a></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                {{-- Folder Preview with better design --}}
                                <div class="folder-preview rounded-3 bg-light-subtle p-3">
                                    <div v-if="folder.files_count === 0" class="text-center py-3">
                                        <i class="fas fa-folder-open fs-1 text-muted opacity-25 mb-2"></i>
                                        <p class="text-muted small mb-0 fw-medium">Carpeta vacía</p>
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
                    <div v-for="file in files" :key="'file-' + file.id" class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                        <div class="card media-card border h-100 shadow-sm no-select position-relative"
                             :class="{ 'selected': isItemSelected('file', file.id) }"
                             @click="handleCardClick($event, 'file', file)"
                             @contextmenu.prevent="showContextMenu($event, 'file', file)">
                            {{-- Selection Checkbox --}}
                            <div class="selection-checkbox"
                                 :class="{ 'visible': selectedItems.length > 0 }"
                                 @click.stop>
                                <input type="checkbox"
                                       :checked="isItemSelected('file', file.id)"
                                       @change="toggleSelection('file', file.id, file)">
                            </div>
                            {{-- File Preview/Icon Area --}}
                            <div class="file-preview-area position-relative">
                                {{-- Image Preview --}}
                                <div v-if="file.type === 'image'" class="file-image-preview">
                                    <img :src="`{{ url('media') }}/${file.url.replace(/^media\//, '')}`"
                                         :alt="file.name"
                                         class="w-100 h-100"
                                         style="object-fit: cover;">
                                    <div class="file-type-badge">
                                        <i class="fas fa-image"></i>
                                    </div>
                                </div>

                                {{-- Video Preview --}}
                                <div v-else-if="file.type === 'video'" class="file-icon-preview bg-gradient-danger">
                                    <i class="fas fa-play-circle display-4 text-white"></i>
                                    <div class="file-type-badge bg-danger">
                                        <i class="fas fa-video"></i>
                                    </div>
                                </div>

                                {{-- PDF Preview --}}
                                <div v-else-if="file.type === 'pdf'" class="file-icon-preview bg-gradient-danger">
                                    <i class="fas fa-file-pdf display-4 text-danger"></i>
                                    <div class="file-type-badge bg-danger">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                </div>

                                {{-- Audio Preview --}}
                                <div v-else-if="file.type === 'audio'" class="file-icon-preview bg-gradient-purple">
                                    <i class="fas fa-file-audio display-4 text-purple"></i>
                                    <div class="file-type-badge bg-purple">
                                        <i class="fas fa-music"></i>
                                    </div>
                                </div>

                                {{-- Document Preview --}}
                                <div v-else-if="file.type === 'document'" class="file-icon-preview bg-gradient-info">
                                    <i class="fas fa-file-word display-4 text-info"></i>
                                    <div class="file-type-badge bg-info">
                                        <i class="fas fa-file-word"></i>
                                    </div>
                                </div>

                                {{-- Spreadsheet Preview --}}
                                <div v-else-if="file.type === 'spreadsheet'" class="file-icon-preview bg-gradient-success">
                                    <i class="fas fa-file-excel display-4 text-success"></i>
                                    <div class="file-type-badge bg-success">
                                        <i class="fas fa-file-excel"></i>
                                    </div>
                                </div>

                                {{-- Archive Preview --}}
                                <div v-else-if="file.type === 'archive'" class="file-icon-preview bg-gradient-warning">
                                    <i class="fas fa-file-archive display-4 text-warning"></i>
                                    <div class="file-type-badge bg-warning">
                                        <i class="fas fa-file-archive"></i>
                                    </div>
                                </div>

                                {{-- Code Preview --}}
                                <div v-else-if="file.type === 'code'" class="file-icon-preview bg-gradient-dark">
                                    <i class="fas fa-file-code display-4 text-dark"></i>
                                    <div class="file-type-badge bg-black">
                                        <i class="fas fa-code"></i>
                                    </div>
                                </div>

                                {{-- Generic File Preview --}}
                                <div v-else class="file-icon-preview bg-gradient-secondary">
                                    <i class="fas fa-file display-4 text-muted"></i>
                                    <div class="file-type-badge bg-secondary">
                                        <i class="fas fa-file"></i>
                                    </div>
                                </div>

                                {{-- Options Menu --}}
                                <div class="file-options-menu" @click.stop>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0;" type="button" data-bs-toggle="dropdown" title="Más opciones">
                                            <i class="fas fa-ellipsis-v" style="font-size: 0.875rem;"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <li><a class="dropdown-item" href="#" @click.prevent="renameFile(file)">
                                                <i class="fas fa-edit me-2 text-primary"></i>Renombrar
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" @click.prevent="copyFile(file)">
                                                <i class="fas fa-copy me-2 text-info"></i>Hacer copia
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" @click.prevent="copyFileLink(file)">
                                                <i class="fas fa-link me-2 text-success"></i>Copiar link
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" @click.prevent="shareFile(file)">
                                                <i class="fas fa-share-alt me-2 text-warning"></i>Compartir
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" @click.prevent="toggleFavorite(file)">
                                                <i class="fas fa-star me-2 text-warning"></i>Favoritos
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" @click.prevent="showProperties(file)">
                                                <i class="fas fa-info-circle me-2 text-muted"></i>Propiedades
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" @click.prevent="deleteFile(file)">
                                                <i class="fas fa-trash me-2"></i>Eliminar
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="card-title mb-2 text-truncate fw-semibold text-center" style="font-size: 0.9rem;" :title="file.name">
                                    @{{ file.name }}
                                </h6>
                                <div class="d-flex justify-content-center align-items-center gap-2 mb-3 flex-wrap">
                                    <span class="badge bg-light text-dark border px-3 py-2">
                                        <i class="fas fa-hdd me-1"></i>@{{ file.human_size }}
                                    </span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2">
                                        @{{ getFileExtension(file.name) }}
                                    </span>
                                </div>
                                <div class="d-flex gap-2 mt-auto">
                                    <template v-if="currentView === 'trash'">
                                        <button @click="restoreFile(file)" class="btn btn-sm btn-success w-100 rounded-pill" title="Restaurar">
                                            Restaurar
                                        </button>
                                    </template>
                                    <template v-else>
                                        <a :href="`{{ url('media') }}/${file.url.replace(/^media\//, '')}`" target="_blank" class="btn btn-sm btn-primary w-100 rounded-pill" title="Descargar">
                                            Descargar
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pagination --}}
            <div v-if="pagination.total > pagination.per_page" class="card-footer border-top">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === pagination.current_page }">
                            <a class="page-link" href="#" @click.prevent="goToPage(page)">@{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-folder-plus me-2"></i>Nueva Carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la carpeta</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="newFolderName"
                            @keyup.enter="confirmCreateFolder"
                            placeholder="Ingresa el nombre de la carpeta"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="confirmCreateFolder">
                        <i class="fas fa-check me-1"></i>Crear Carpeta
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Renombrar Carpeta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo nombre</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="renameFolderData.name"
                            @keyup.enter="confirmRenameFolder"
                            placeholder="Ingresa el nuevo nombre"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="confirmRenameFolder">
                        <i class="fas fa-check me-1"></i>Renombrar
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Renombrar Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevo nombre</label>
                        <input
                            type="text"
                            class="form-control"
                            v-model="renameFileData.name"
                            @keyup.enter="confirmRenameFile"
                            placeholder="Ingresa el nuevo nombre"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="confirmRenameFile">
                        <i class="fas fa-check me-1"></i>Renombrar
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-copy me-2"></i>Hacer Copia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">¿Deseas crear una copia de este archivo?</p>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle me-2"></i>Se creará una copia con el sufijo "_copia"</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="confirmCopyFile()">
                        <i class="fas fa-check me-1"></i>Crear Copia
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-link me-2"></i>Copiar Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold mb-2">Link del archivo</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" v-model="copyLinkData.url" readonly>
                        <button class="btn btn-outline-primary" type="button" @click="copyToClipboard(copyLinkData.url)">
                            <i class="fas fa-copy me-1"></i>Copiar
                        </button>
                    </div>
                    <small class="text-muted">El link ha sido copiado al portapapeles</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i>Listo
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2"></i>Propiedades del Archivo</h5>
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
                            <span class="text-muted" style="font-size: 0.85rem;">@{{ filePropertiesData.uid }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i>Cerrar
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-trash me-2"></i>Eliminar Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">¿Estás seguro de que deseas eliminar este archivo?</p>
                    <div class="alert alert-warning mb-0">
                        <small><i class="fas fa-exclamation-triangle me-2"></i>Esta acción no se puede deshacer</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" @click="confirmDeleteFile">
                       Eliminar
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-star me-2"></i>Favoritos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">@{{ favoriteMessage }}</p>
                    <div class="alert alert-info mb-0">
                        <small><i class="fas fa-info-circle me-2"></i>Esta funcionalidad está en desarrollo</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i>Entendido
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
                    <h5 class="modal-title fw-bold"><i class="fas fa-link me-2"></i>Subir desde URL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL del archivo</label>
                        <input
                            type="url"
                            class="form-control"
                            v-model="uploadUrlData.url"
                            @keyup.enter="confirmUploadFromUrl"
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" @click="confirmUploadFromUrl" :disabled="!uploadUrlData.url">
                        <i class="fas fa-upload me-1"></i>Subir desde URL
                    </button>
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
                        <i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Subir Archivos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Drag & Drop Zone --}}
                    <div class="upload-drop-zone mb-4 p-5 text-center border border-2 border-dashed rounded-3"
                         :class="{'border-primary bg-primary bg-opacity-10': isDragging}"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleFilesDrop">
                        <div class="mb-3">
                            <i class="fas fa-cloud-upload-alt text-primary" style="font-size: 3.5rem;"></i>
                        </div>
                        <h5 class="fw-semibold mb-2">Arrastra archivos aquí</h5>
                        <p class="text-muted mb-3">o haz clic para seleccionar archivos</p>
                        <input type="file"
                               ref="fileInput"
                               multiple
                               @change="handleFilesSelect"
                               class="d-none">
                        <button type="button"
                                class="btn btn-primary rounded-pill px-4"
                                @click="$refs.fileInput.click()">
                            <i class="fas fa-plus me-2"></i>Seleccionar Archivos
                        </button>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
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
                                    @click="clearUploadQueue">
                                <i class="fas fa-trash me-1"></i>Limpiar todo
                            </button>
                        </div>

                        <div class="list-group">
                            <div v-for="(fileItem, index) in uploadQueue"
                                 :key="index"
                                 class="list-group-item border rounded-3 mb-2">
                                <div class="d-flex align-items-start gap-3">
                                    {{-- File Icon --}}
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                             style="width: 48px; height: 48px;">
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
                                                    class="btn btn-sm btn-light rounded-circle ms-2"
                                                    @click="removeFromQueue(index)"
                                                    :disabled="fileItem.uploading"
                                                    style="width: 32px; height: 32px;">
                                                <i class="fas fa-times text-danger"></i>
                                            </button>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div v-if="fileItem.uploading || fileItem.progress > 0" class="mt-2">
                                            <div class="progress" style="height: 8px;">
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
                        <i class="fas fa-file-upload text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="text-muted mb-0">No hay archivos seleccionados</p>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button"
                            class="btn btn-primary"
                            @click="startUpload"
                            :disabled="uploadQueue.length === 0 || isUploading">
                        <i class="fas fa-upload me-1"></i>
                        <span v-if="isUploading">Subiendo...</span>
                        <span v-else>Subir @{{ uploadQueue.length }} archivo@{{ uploadQueue.length !== 1 ? 's' : '' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Context Menu (Right Click) --}}
    <div id="contextMenu" class="context-menu" @click.stop>
        {{-- Folder Context Menu --}}
        <div v-if="contextMenu.type === 'folder'" class="context-menu-content">
            <div v-if="currentView === 'trash'">
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('restore')">
                    <i class="fas fa-undo"></i>
                    <span>Restaurar</span>
                </a>
            </div>
            <div v-else>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('open')">
                    <i class="fas fa-folder-open"></i>
                    <span>Abrir</span>
                </a>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('rename')">
                    <i class="fas fa-edit"></i>
                    <span>Renombrar</span>
                </a>
                <div class="context-menu-divider"></div>
                <a href="#" class="context-menu-item danger" @click.prevent="handleContextAction('delete')">
                    <i class="fas fa-trash"></i>
                    <span>Eliminar</span>
                </a>
            </div>
        </div>

        {{-- File Context Menu --}}
        <div v-if="contextMenu.type === 'file'" class="context-menu-content">
            <div v-if="currentView === 'trash'">
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('restore')">
                    <i class="fas fa-undo"></i>
                    <span>Restaurar</span>
                </a>
            </div>
            <div v-else>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('download')">
                    <i class="fas fa-download"></i>
                    <span>Descargar</span>
                </a>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('rename')">
                    <i class="fas fa-edit"></i>
                    <span>Renombrar</span>
                </a>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('copy')">
                    <i class="fas fa-copy"></i>
                    <span>Hacer copia</span>
                </a>
                <div class="context-menu-divider"></div>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('copyLink')">
                    <i class="fas fa-link"></i>
                    <span>Copiar enlace</span>
                </a>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('share')">
                    <i class="fas fa-share"></i>
                    <span>Compartir</span>
                </a>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('favorite')">
                    <i class="fas fa-star"></i>
                    <span>@{{ contextMenu.item?.is_favorite ? 'Quitar de' : 'Agregar a' }} favoritos</span>
                </a>
                <div class="context-menu-divider"></div>
                <a href="#" class="context-menu-item" @click.prevent="handleContextAction('properties')">
                    <i class="fas fa-info-circle"></i>
                    <span>Propiedades</span>
                </a>
                <a href="#" class="context-menu-item danger" @click.prevent="handleContextAction('delete')">
                    <i class="fas fa-trash"></i>
                    <span>Eliminar</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
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
                selectionMode: false
            }
        },
        computed: {
            visiblePages() {
                const pages = [];
                const current = this.pagination.current_page;
                const last = this.pagination.last_page;

                for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                    pages.push(i);
                }

                return pages;
            },
            getViewTitle() {
                const titles = {
                    'all': 'Mis Archivos',
                    'recent': 'Archivos Recientes',
                    'favorites': 'Archivos Favoritos',
                    'trash': 'Papelera'
                };
                return titles[this.currentView] || 'Mis Archivos';
            },
            hasOnlyFiles() {
                return this.selectedItems.every(item => item.type === 'file');
            }
        },
        methods: {
            async loadMedia(folderId = 0, page = 1) {
                this.loading = true;
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=${folderId}&page=${page}&search=${this.searchQuery}`);
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
                } catch (error) {
                    toastr.error('Error al cargar archivos', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async changeActiveDisk() {
                try {
                    const response = await fetch('{{ route('media.set-disk') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            disk: this.activeDisk
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        toastr.success(data.message, 'Éxito');
                        // Reload media list with new disk
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
                this.loadMedia(this.currentFolderId);
            },
            clearSearch() {
                this.searchQuery = '';
                this.loadMedia(this.currentFolderId);
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

                        const response = await fetch('{{ route("media.upload") }}', {
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
                    this.loadMedia(this.currentFolderId);
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
                    const response = await fetch('{{ route("media.folder.create") }}', {
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
                        this.loadMedia(this.currentFolderId);
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
                    const response = await fetch(`{{ url('manager/media/folder') }}/${this.renameFolderData.id}/rename`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ name: this.renameFolderData.name })
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalRenameFolder')).hide();
                        this.loadMedia(this.currentFolderId);
                        toastr.success('Carpeta renombrada exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al renombrar carpeta', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al renombrar carpeta', 'Error');
                }
            },
            renameFile(file) {
                this.renameFileData = { id: file.id, name: file.name };
                new bootstrap.Modal(document.getElementById('modalRenameFile')).show();
            },
            async confirmRenameFile() {
                if (!this.renameFileData.name.trim()) return;

                try {
                    const response = await fetch(`{{ url('manager/media/file') }}/${this.renameFileData.id}/rename`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ name: this.renameFileData.name })
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalRenameFile')).hide();
                        this.loadMedia(this.currentFolderId);
                        toastr.success('Archivo renombrado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al renombrar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al renombrar archivo', 'Error');
                }
            },
            async deleteFolder(folder) {
                if (!confirm(`¿Estás seguro de eliminar la carpeta "${folder.name}"?`)) return;

                try {
                    const response = await fetch(`{{ url('manager/media/folder') }}/${folder.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        this.loadMedia(this.currentFolderId);
                        toastr.success('Carpeta eliminada exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al eliminar carpeta', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al eliminar carpeta', 'Error');
                }
            },
            async deleteFile(file) {
                if (!confirm(`¿Estás seguro de eliminar el archivo "${file.name}"?`)) return;

                try {
                    const response = await fetch(`{{ url('manager/media/file') }}/${file.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        this.loadMedia(this.currentFolderId);
                        toastr.success('Archivo eliminado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al eliminar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al eliminar archivo', 'Error');
                }
            },
            renameFile(file) {
                const newName = prompt(`Nuevo nombre para "${file.name}":`, file.name);
                if (newName && newName !== file.name) {
                    this.confirmRenameFile(file, newName);
                }
            },
            async confirmRenameFile(file, newName) {
                try {
                    const response = await fetch(`{{ url('manager/media/file') }}/${file.id}/rename`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ name: newName })
                    });

                    if (response.ok) {
                        this.loadMedia(this.currentFolderId);
                        toastr.success('Archivo renombrado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al renombrar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al renombrar archivo', 'Error');
                }
            },
            async copyFile(file) {
                try {
                    const response = await fetch(`{{ url('manager/media/file') }}/${file.id}/copy`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        toastr.success('Archivo copiado exitosamente', 'Éxito');
                        this.loadMedia(this.currentFolderId);
                    } else {
                        toastr.error('Error al copiar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al copiar archivo', 'Error');
                }
            },
            copyFileLink(file) {
                const url = `{{ url('/storage') }}/${file.url}`;
                navigator.clipboard.writeText(url).then(() => {
                    toastr.success('Link copiado al portapapeles', 'Éxito');
                }).catch(err => {
                    toastr.error('Error al copiar link', 'Error');
                    prompt('Copiar link:', url);
                });
            },
            renameFile(file) {
                this.renameFileData = { id: file.id, name: file.name };
                new bootstrap.Modal(document.getElementById('modalRenameFile')).show();
            },
            async confirmRenameFile() {
                if (!this.renameFileData.name.trim()) return;

                try {
                    const response = await fetch(`{{ url('manager/media/file') }}/${this.renameFileData.id}/rename`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ name: this.renameFileData.name })
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalRenameFile')).hide();
                        this.loadMedia(this.currentFolderId);
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
                    const response = await fetch(`/manager/media/file/${this.copyFileData.id}/copy`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalCopyFile')).hide();
                        this.loadMedia(this.currentFolderId);
                        toastr.success('Archivo copiado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al copiar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al copiar archivo', 'Error');
                }
            },
            copyFileLink(file) {
                this.copyLinkData.url = `{{ url('/media') }}/${file.url.replace(/^media\//, '')}`;
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
                    const response = await fetch(`{{ url('manager/media/file') }}/${this.deleteFileData.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('modalConfirmDelete')).hide();
                        this.loadMedia(this.currentFolderId);
                        toastr.success('Archivo eliminado exitosamente', 'Éxito');
                    } else {
                        toastr.error('Error al eliminar archivo', 'Error');
                    }
                } catch (error) {
                    toastr.error('Error al eliminar archivo', 'Error');
                }
            },
            copyIndirectLink(file) {
                const indirectUrl = `{{ url('manager/media/file') }}/${file.id}`;
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
                window.location.href = `/media/${file.url.replace(/^media\//, '')}`;
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
                    this.loadMedia(this.currentFolderId);
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
                this.loadMedia(this.currentFolderId);
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
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=0&page=1&search=${this.searchQuery}&view=trash`);
                    const data = await response.json();

                    this.folders = data.folders || [];
                    this.files = data.files || [];
                    this.breadcrumbs = [];
                    this.pagination = data.pagination || this.pagination;
                } catch (error) {
                    toastr.error('Error al cargar papelera', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async loadRecent() {
                this.loading = true;
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=0&page=1&search=${this.searchQuery}&view=recent`);
                    const data = await response.json();

                    this.folders = data.folders || [];
                    this.files = data.files || [];
                    this.breadcrumbs = [];
                    this.pagination = data.pagination || this.pagination;
                } catch (error) {
                    toastr.error('Error al cargar archivos recientes', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async loadFavorites() {
                this.loading = true;
                try {
                    const response = await fetch(`{{ route('media.list') }}?folder_id=0&page=1&search=${this.searchQuery}&view=favorites`);
                    const data = await response.json();

                    this.folders = data.folders || [];
                    this.files = data.files || [];
                    this.breadcrumbs = [];
                    this.pagination = data.pagination || this.pagination;
                } catch (error) {
                    toastr.error('Error al cargar favoritos', 'Error');
                } finally {
                    this.loading = false;
                }
            },
            async emptyTrash() {
                if (!confirm('¿Estás seguro de que deseas vaciar la papelera? Esta acción eliminará permanentemente todos los archivos y carpetas en la papelera.')) {
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
                    const response = await fetch(`{{ url('manager/media/file') }}/${file.id}/toggle-favorite`, {
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
                    const response = await fetch(`{{ url('manager/media/file') }}/${file.id}/restore`, {
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
                    const response = await fetch(`{{ url('manager/media/folder') }}/${folder.id}/restore`, {
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
                    const response = await fetch('{{ route("media.upload-url") }}', {
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
                        this.loadMedia(this.currentFolderId);
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

                // Close modal and refresh
                bootstrap.Modal.getInstance(document.getElementById('modalEnhancedUpload')).hide();
                this.loadMedia(this.currentFolderId);

                // Clear queue after successful upload
                this.uploadQueue = [];

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

                        xhr.open('POST', '{{ route("media.upload") }}');
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
                if (!confirm(`¿Estás seguro de eliminar ${this.selectedItems.length} elementos?`)) {
                    return;
                }

                try {
                    const promises = this.selectedItems.map(async ({ type, id }) => {
                        if (type === 'folder') {
                            return await this.deleteFolder({ id });
                        } else {
                            return await this.deleteFile({ id });
                        }
                    });

                    await Promise.all(promises);
                    this.clearSelection();
                    await this.loadMedia(this.currentFolderId);
                    toastr.success('Elementos eliminados correctamente');
                } catch (error) {
                    console.error('Bulk delete error:', error);
                    toastr.error('Error al eliminar algunos elementos');
                }
            },

            async bulkRestore() {
                try {
                    const promises = this.selectedItems.map(async ({ type, id }) => {
                        if (type === 'folder') {
                            return await this.restoreFolder({ id });
                        } else {
                            return await this.restoreFile({ id });
                        }
                    });

                    await Promise.all(promises);
                    this.clearSelection();
                    await this.loadMedia(this.currentFolderId);
                    toastr.success('Elementos restaurados correctamente');
                } catch (error) {
                    console.error('Bulk restore error:', error);
                    toastr.error('Error al restaurar algunos elementos');
                }
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

            bulkMove() {
                // TODO: Implement bulk move with folder picker modal
                toastr.info('Función de mover múltiples elementos próximamente');
            }
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

            // Hide context menu when clicking anywhere else
            document.addEventListener('click', () => {
                this.hideContextMenu();
            });
        }
    }).mount('#mediaManagerApp');
</script>
@endpush
