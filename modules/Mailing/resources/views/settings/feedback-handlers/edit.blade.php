@extends('layouts.theme')

@section('page_title', 'Editar manejador de retroalimentación')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Editar manejador de retroalimentación',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Manejadores de retroalimentación', 'url' => route('settings.mailing.feedback-handlers.index')],
            ['label' => $handler->name, 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container">
        @include('Mailing::settings.feedback-handlers._form', ['handler' => $handler])
    </div>

@endsection
