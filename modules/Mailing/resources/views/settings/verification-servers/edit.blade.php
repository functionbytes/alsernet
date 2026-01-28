@extends('layouts.theme')

@section('page_title', 'Editar servidor de verificación')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Editar servidor de verificación',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Servidores de verificación', 'url' => route('settings.mailing.verification-servers.index')],
            ['label' => $server->name, 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container">
        @include('Mailing::settings.verification-servers._form', ['server' => $server])
    </div>

@endsection
