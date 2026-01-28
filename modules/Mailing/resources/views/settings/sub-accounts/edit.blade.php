@extends('layouts.theme')

@section('page_title', 'Editar sub-cuenta')

@section('content')

    {{-- Breadcrumb Card --}}
    @include('core::components.card', [
        'title' => 'Editar sub-cuenta',
        'breadcrumbs' => [
            ['label' => 'Dashboard', 'url' => url('/home')],
            ['label' => 'Configuración', 'url' => ''],
            ['label' => 'Sub-cuentas', 'url' => route('settings.mailing.sub-accounts.index')],
            ['label' => $subAccount->name, 'active' => true]
        ]
    ])

    <div class="widget-content searchable-container">
        @include('Mailing::settings.sub-accounts._form', ['subAccount' => $subAccount])
    </div>

@endsection
