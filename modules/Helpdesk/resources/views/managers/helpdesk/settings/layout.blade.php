@extends('layouts.theme')

@section('title', $title ?? 'Configuración de Helpdesk')

@push('styles')
<style>
    :root {
        --primary: #5D87FF;
        --primary-dark: #3E5BDB;
        --success: #13C672;
        --danger: #FA896B;
        --warning: #FEC90F;
        --info: #5DADE2;
        --light-bg: #f8f9fa;
        --card-border: #e0e0e0;
    }

    .settings-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(93, 135, 255, 0.2);
    }

    .settings-header h2 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .settings-header p {
        opacity: 0.95;
        margin: 0;
        font-size: 0.95rem;
    }

    .settings-tabs {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .settings-nav {
        border-bottom: 2px solid var(--light-bg);
        display: flex;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .settings-nav .nav-item {
        flex-shrink: 0;
    }

    .settings-nav .nav-link {
        padding: 1rem 1.5rem;
        border-bottom: 3px solid transparent;
        color: #666;
        font-weight: 500;
        transition: all 0.3s ease;
        white-space: nowrap;
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .settings-nav .nav-link:hover {
        color: var(--primary);
        background: rgba(93, 135, 255, 0.05);
    }

    .settings-nav .nav-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: rgba(93, 135, 255, 0.05);
    }

    .settings-nav .nav-link i {
        font-size: 1rem;
    }

    .settings-content {
        padding: 2rem;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid var(--card-border);
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .form-section h5 {
        font-weight: 700;
        color: #333;
        margin-bottom: 1rem;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-section h5 i {
        color: var(--primary);
        font-size: 1.1rem;
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .form-control,
    .form-select {
        border: 1px solid var(--card-border);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(93, 135, 255, 0.1);
    }

    .form-control::placeholder {
        color: #999;
    }

    .form-help {
        font-size: 0.85rem;
        color: #999;
        margin-top: 0.5rem;
        display: block;
    }

    .btn-save {
        background: var(--primary);
        border: none;
        padding: 0.75rem 2rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-save:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 135, 255, 0.3);
    }

    .btn-save:active {
        transform: translateY(0);
    }

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .settings-card {
        background: var(--light-bg);
        border: 1px solid var(--card-border);
        border-radius: 8px;
        padding: 1.5rem;
        transition: all 0.2s ease;
    }

    .settings-card:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(93, 135, 255, 0.1);
    }

    .settings-card h6 {
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #333;
    }

    .settings-card p {
        font-size: 0.9rem;
        color: #666;
        margin: 0;
    }

    .alert-info {
        background: rgba(93, 135, 255, 0.1);
        border: 1px solid rgba(93, 135, 255, 0.3);
        border-radius: 8px;
        color: #3E5BDB;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 1rem;
    }

    .breadcrumb-item + .breadcrumb-item::before {
        color: #999;
    }

    .breadcrumb-item a {
        color: var(--primary);
        text-decoration: none;
    }

    .breadcrumb-item a:hover {
        text-decoration: underline;
    }

    .breadcrumb-item.active {
        color: #666;
    }

    .sidebar-settings {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        position: sticky;
        top: 20px;
    }

    .sidebar-settings h6 {
        font-weight: 700;
        margin-bottom: 1rem;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        color: #666;
    }

    .sidebar-stats {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: var(--light-bg);
        border-radius: 6px;
        border-left: 3px solid var(--primary);
    }

    .stat-item small {
        color: #999;
        display: block;
    }

    .stat-item strong {
        font-size: 1.1rem;
        color: #333;
    }

    @media (max-width: 768px) {
        .settings-header {
            padding: 1.5rem;
        }

        .settings-header h2 {
            font-size: 1.5rem;
        }

        .settings-content {
            padding: 1.5rem;
        }

        .settings-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('manager.dashboard') }}">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('manager.helpdesk.tickets.index') }}">Helpdesk</a>
            </li>
            <li class="breadcrumb-item active">{{ $breadcrumb ?? 'Configuración' }}</li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="settings-header">
        <h2>
            <i class="fas {{ $icon ?? 'fa-cog' }} me-2"></i>
            {{ $title ?? 'Configuración de Helpdesk' }}
        </h2>
        <p>{{ $description ?? 'Administra la configuración del sistema' }}</p>
    </div>

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-9">
            <div class="settings-tabs">
                {{-- Tabs Navigation --}}
                <ul class="nav settings-nav" role="tablist">
                    @php
                        $currentTab = request()->get('tab', $defaultTab ?? 'general');
                    @endphp

                    @forelse($tabs ?? [] as $tabKey => $tabLabel)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $currentTab === $tabKey ? 'active' : '' }}"
                               href="{{ request()->url() }}?tab={{ $tabKey }}"
                               role="tab">
                                <i class="fas fa-{{ $tabIcons[$tabKey] ?? 'circle' }} me-1"></i>
                                {{ $tabLabel }}
                            </a>
                        </li>
                    @empty
                        {{-- Default tabs if none provided --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'general' ? 'active' : '' }}"
                               href="{{ request()->url() }}?tab=general">
                                <i class="fas fa-cog me-1"></i>
                                General
                            </a>
                        </li>
                    @endforelse
                </ul>

                {{-- Tab Content --}}
                <div class="settings-content">
                    @yield('backups-content')
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        @hasSection('sidebar-info')
        <div class="col-lg-3">
            <div class="sidebar-settings">
                @yield('sidebar-info')
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
