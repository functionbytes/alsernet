@extends('layouts.theme')

@section('page_title', 'Crear servidor de verificación')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Nuevo servidor de verificación',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Servidores de verificación', 'url' => route('settings.mailing.verification-servers.index')],
            ['label' => 'Crear', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container">
        @include('Mailing::settings.verification-servers._form')
    </div>

@endsection
