@extends('layouts.theme')

@section('page_title', 'Crear servidor de envío')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Nuevo servidor de envío',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Servidores de envío', 'url' => route('settings.mailing.sending-servers.index')],
            ['label' => 'Crear', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container">
        @include('Mailing::settings.sending-servers._form')
    </div>

@endsection
