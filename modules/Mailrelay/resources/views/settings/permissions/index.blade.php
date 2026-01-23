@extends('layouts.theme')

@section('title', 'Permisos del módulo Mailrelay')

@section('head')
<style>
    .permissions-table {
        font-size: 0.9rem;
    }
    .permissions-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .permissions-table .permission-name {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: #495057;
    }
    .permissions-table .section-header {
        background-color: #e9ecef;
        font-weight: 700;
    }
    .role-checkbox {
        cursor: pointer;
        width: 20px;
        height: 20px;
    }
    .select-all-checkbox {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }
    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-card.primary {
        border-left-color: #0d6efd;
    }
    .stat-card.success {
        border-left-color: #198754;
    }
    .stat-card.info {
        border-left-color: #0dcaf0;
    }
    .stat-card.warning {
        border-left-color: #ffc107;
    }
    .table-wrapper {
        max-height: 600px;
        overflow-y: auto;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold">Permisos del módulo Mailrelay</h2>
                    <p class="text-muted mb-0">Gestiona los permisos de acceso por rol de usuario</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" id="syncPermissionsBtn">
                        <i class="bi bi-arrow-repeat me-2"></i>Sincronizar permisos
                    </button>
                    <button type="submit" form="permissionsForm" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>Información importante:</strong> Los permisos se asignan por rol. Los cambios se aplicarán inmediatamente después de guardar.
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total permisos</p>
                            <h3 class="mb-0 fw-bold" id="totalPermissions">0</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-shield-lock text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Roles con acceso</p>
                            <h3 class="mb-0 fw-bold">4</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-people text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Permisos activos</p>
                            <h3 class="mb-0 fw-bold" id="activePermissions">0</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-check-circle text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Última sincronización</p>
                            <h6 class="mb-0 fw-semibold">{{ now()->format('d/m/Y H:i') }}</h6>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="permissionsForm" action="{{ route('settings.mailrelay.permissions.assign') }}" method="POST">
                        @csrf

                        <div class="table-wrapper">
                            <table class="table table-hover permissions-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 35%">Permiso</th>
                                        <th style="width: 35%">Descripción</th>
                                        <th class="text-center" style="width: 7.5%">
                                            Super Admin
                                            <br>
                                            <input type="checkbox" class="select-all-checkbox select-all-role" data-role="super-admin">
                                        </th>
                                        <th class="text-center" style="width: 7.5%">
                                            Admin
                                            <br>
                                            <input type="checkbox" class="select-all-checkbox select-all-role" data-role="admin">
                                        </th>
                                        <th class="text-center" style="width: 7.5%">
                                            Manager
                                            <br>
                                            <input type="checkbox" class="select-all-checkbox select-all-role" data-role="manager">
                                        </th>
                                        <th class="text-center" style="width: 7.5%">
                                            User
                                            <br>
                                            <input type="checkbox" class="select-all-checkbox select-all-role" data-role="user">
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="permissionsTableBody">
                                    <!-- Acceso general -->
                                    <tr class="section-header">
                                        <td colspan="6">
                                            <i class="bi bi-folder me-2"></i>
                                            <strong>Acceso general</strong>
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.access">
                                        <td>
                                            <code class="permission-name">mailrelay.access</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Acceso general al módulo Mailrelay</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.access][super-admin]" data-permission="mailrelay.access" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.access][admin]" data-permission="mailrelay.access" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.access][manager]" data-permission="mailrelay.access" data-role="manager" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.access][user]" data-permission="mailrelay.access" data-role="user">
                                        </td>
                                    </tr>

                                    <!-- Campañas -->
                                    <tr class="section-header">
                                        <td colspan="6">
                                            <i class="bi bi-megaphone me-2"></i>
                                            <strong>Campañas</strong>
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.campaigns.view">
                                        <td>
                                            <code class="permission-name">mailrelay.campaigns.view</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Ver listado de campañas</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.view][super-admin]" data-permission="mailrelay.campaigns.view" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.view][admin]" data-permission="mailrelay.campaigns.view" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.view][manager]" data-permission="mailrelay.campaigns.view" data-role="manager" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.view][user]" data-permission="mailrelay.campaigns.view" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.campaigns.create">
                                        <td>
                                            <code class="permission-name">mailrelay.campaigns.create</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Crear nuevas campañas</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.create][super-admin]" data-permission="mailrelay.campaigns.create" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.create][admin]" data-permission="mailrelay.campaigns.create" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.create][manager]" data-permission="mailrelay.campaigns.create" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.create][user]" data-permission="mailrelay.campaigns.create" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.campaigns.edit">
                                        <td>
                                            <code class="permission-name">mailrelay.campaigns.edit</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Editar campañas existentes</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.edit][super-admin]" data-permission="mailrelay.campaigns.edit" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.edit][admin]" data-permission="mailrelay.campaigns.edit" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.edit][manager]" data-permission="mailrelay.campaigns.edit" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.edit][user]" data-permission="mailrelay.campaigns.edit" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.campaigns.delete">
                                        <td>
                                            <code class="permission-name">mailrelay.campaigns.delete</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Eliminar campañas</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.delete][super-admin]" data-permission="mailrelay.campaigns.delete" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.delete][admin]" data-permission="mailrelay.campaigns.delete" data-role="admin">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.delete][manager]" data-permission="mailrelay.campaigns.delete" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.delete][user]" data-permission="mailrelay.campaigns.delete" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.campaigns.send">
                                        <td>
                                            <code class="permission-name">mailrelay.campaigns.send</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Enviar campañas a suscriptores</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.send][super-admin]" data-permission="mailrelay.campaigns.send" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.send][admin]" data-permission="mailrelay.campaigns.send" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.send][manager]" data-permission="mailrelay.campaigns.send" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.campaigns.send][user]" data-permission="mailrelay.campaigns.send" data-role="user">
                                        </td>
                                    </tr>

                                    <!-- Suscriptores -->
                                    <tr class="section-header">
                                        <td colspan="6">
                                            <i class="bi bi-people me-2"></i>
                                            <strong>Suscriptores</strong>
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.subscribers.view">
                                        <td>
                                            <code class="permission-name">mailrelay.subscribers.view</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Ver listado de suscriptores</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.view][super-admin]" data-permission="mailrelay.subscribers.view" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.view][admin]" data-permission="mailrelay.subscribers.view" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.view][manager]" data-permission="mailrelay.subscribers.view" data-role="manager" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.view][user]" data-permission="mailrelay.subscribers.view" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.subscribers.create">
                                        <td>
                                            <code class="permission-name">mailrelay.subscribers.create</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Crear nuevos suscriptores</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.create][super-admin]" data-permission="mailrelay.subscribers.create" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.create][admin]" data-permission="mailrelay.subscribers.create" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.create][manager]" data-permission="mailrelay.subscribers.create" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.create][user]" data-permission="mailrelay.subscribers.create" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.subscribers.edit">
                                        <td>
                                            <code class="permission-name">mailrelay.subscribers.edit</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Editar suscriptores existentes</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.edit][super-admin]" data-permission="mailrelay.subscribers.edit" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.edit][admin]" data-permission="mailrelay.subscribers.edit" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.edit][manager]" data-permission="mailrelay.subscribers.edit" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.edit][user]" data-permission="mailrelay.subscribers.edit" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.subscribers.delete">
                                        <td>
                                            <code class="permission-name">mailrelay.subscribers.delete</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Eliminar suscriptores</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.delete][super-admin]" data-permission="mailrelay.subscribers.delete" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.delete][admin]" data-permission="mailrelay.subscribers.delete" data-role="admin">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.delete][manager]" data-permission="mailrelay.subscribers.delete" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.subscribers.delete][user]" data-permission="mailrelay.subscribers.delete" data-role="user">
                                        </td>
                                    </tr>

                                    <!-- Importaciones -->
                                    <tr class="section-header">
                                        <td colspan="6">
                                            <i class="bi bi-upload me-2"></i>
                                            <strong>Importaciones</strong>
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.imports.view">
                                        <td>
                                            <code class="permission-name">mailrelay.imports.view</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Ver listado de importaciones</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.view][super-admin]" data-permission="mailrelay.imports.view" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.view][admin]" data-permission="mailrelay.imports.view" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.view][manager]" data-permission="mailrelay.imports.view" data-role="manager" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.view][user]" data-permission="mailrelay.imports.view" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.imports.create">
                                        <td>
                                            <code class="permission-name">mailrelay.imports.create</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Crear nuevas importaciones</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.create][super-admin]" data-permission="mailrelay.imports.create" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.create][admin]" data-permission="mailrelay.imports.create" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.create][manager]" data-permission="mailrelay.imports.create" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.create][user]" data-permission="mailrelay.imports.create" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.imports.delete">
                                        <td>
                                            <code class="permission-name">mailrelay.imports.delete</code>
                                            <span class="badge bg-warning ms-2">No en BD</span>
                                        </td>
                                        <td>Eliminar importaciones</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.delete][super-admin]" data-permission="mailrelay.imports.delete" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.delete][admin]" data-permission="mailrelay.imports.delete" data-role="admin">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.delete][manager]" data-permission="mailrelay.imports.delete" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.imports.delete][user]" data-permission="mailrelay.imports.delete" data-role="user">
                                        </td>
                                    </tr>

                                    <!-- Settings -->
                                    <tr class="section-header">
                                        <td colspan="6">
                                            <i class="bi bi-gear me-2"></i>
                                            <strong>Configuración</strong>
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.settings.view">
                                        <td>
                                            <code class="permission-name">mailrelay.settings.view</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Ver configuración del módulo</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.view][super-admin]" data-permission="mailrelay.settings.view" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.view][admin]" data-permission="mailrelay.settings.view" data-role="admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.view][manager]" data-permission="mailrelay.settings.view" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.view][user]" data-permission="mailrelay.settings.view" data-role="user">
                                        </td>
                                    </tr>
                                    <tr data-permission="mailrelay.settings.edit">
                                        <td>
                                            <code class="permission-name">mailrelay.settings.edit</code>
                                            <span class="badge bg-success ms-2">En BD</span>
                                        </td>
                                        <td>Editar configuración del módulo</td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.edit][super-admin]" data-permission="mailrelay.settings.edit" data-role="super-admin" checked>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.edit][admin]" data-permission="mailrelay.settings.edit" data-role="admin">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.edit][manager]" data-permission="mailrelay.settings.edit" data-role="manager">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="role-checkbox" name="permissions[mailrelay.settings.edit][user]" data-permission="mailrelay.settings.edit" data-role="user">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculate stats
    updateStats();

    // Select all by role (column)
    document.querySelectorAll('.select-all-role').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const role = this.dataset.role;
            const checked = this.checked;

            document.querySelectorAll(`.role-checkbox[data-role="${role}"]`).forEach(cb => {
                cb.checked = checked;
            });

            updateStats();
        });
    });

    // Individual checkbox change
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateStats();
            updateSelectAllCheckboxes();
        });
    });

    // Sync permissions button
    document.getElementById('syncPermissionsBtn').addEventListener('click', function() {
        if (confirm('¿Desea sincronizar los permisos con la base de datos? Esto creará todos los permisos que no existan.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('settings.mailrelay.permissions.sync') }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });

    function updateStats() {
        const totalPermissions = document.querySelectorAll('[data-permission]').length;
        const checkedCheckboxes = document.querySelectorAll('.role-checkbox:checked').length;

        document.getElementById('totalPermissions').textContent = totalPermissions;
        document.getElementById('activePermissions').textContent = checkedCheckboxes;
    }

    function updateSelectAllCheckboxes() {
        document.querySelectorAll('.select-all-role').forEach(selectAll => {
            const role = selectAll.dataset.role;
            const roleCheckboxes = document.querySelectorAll(`.role-checkbox[data-role="${role}"]`);
            const checkedCount = document.querySelectorAll(`.role-checkbox[data-role="${role}"]:checked`).length;

            selectAll.checked = roleCheckboxes.length === checkedCount;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < roleCheckboxes.length;
        });
    }

    // Initialize select-all checkboxes state
    updateSelectAllCheckboxes();
});
</script>
@endsection
