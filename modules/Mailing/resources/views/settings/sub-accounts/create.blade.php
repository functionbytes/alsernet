@extends('layouts.theme')

@section('page_title', 'Crear sub-cuenta')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Nueva sub-cuenta',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Sub-cuentas', 'url' => route('settings.mailing.sub-accounts.index')],
            ['label' => 'Crear', 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container">
        @include('Mailing::settings.sub-accounts._form')
    </div>

@endsection
