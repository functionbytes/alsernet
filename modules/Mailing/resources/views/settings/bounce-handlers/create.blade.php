@extends('layouts.theme')

@section('page_title', 'Crear manejador de rebote')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Nuevo manejador de rebote',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Manejadores de rebote', 'url' => route('settings.mailing.bounce-handlers.index')],
            ['label' => 'Crear', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container">
        @include('Mailing::settings.bounce-handlers._form')
    </div>

@endsection
