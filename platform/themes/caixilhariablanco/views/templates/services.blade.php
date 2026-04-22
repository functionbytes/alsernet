@extends('template::layouts.default')

@php Theme::set('page', $page); @endphp

@section('seo_head')
    @include(Theme::getThemeNamespace() . '::partials.seo-head')
@endsection

@section('content')


    {{-- Contenido CKEditor --}}
    @if($transContent)
    <section class="mt-60 mb-5">
        <div class="container">
            <div class="ck-content">
                {!! $transContent !!}
            </div>
        </div>
    </section>
    @endif

    {{-- Grid de servicios --}}
    <section class="our-services pb-5">
        <div class="container">
            <div class="row g-4">
                @foreach([
                    ['icon' => 'fas fa-window-maximize', 'title' => 'Ventanas PVC',         'desc' => 'Alta eficiencia energética y aislamiento acústico'],
                    ['icon' => 'fas fa-door-open',       'title' => 'Portones automáticos',  'desc' => 'Sistemas de automatización residencial e industrial'],
                    ['icon' => 'fas fa-layer-group',     'title' => 'Estores y persianas',   'desc' => 'Control de luz y privacidad con diseño elegante'],
                    ['icon' => 'fas fa-tools',           'title' => 'Instalación',           'desc' => 'Equipo certificado con garantía de instalación'],
                ] as $service)
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="mb-3">
                            <i class="{{ $service['icon'] }} fa-3x" style="color:var(--main-color, #90bb13);"></i>
                        </div>
                        <h5 class="fw-bold">{{ $service['title'] }}</h5>
                        <p class="text-muted small mb-0">{{ $service['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
