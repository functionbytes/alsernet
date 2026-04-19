@extends('template::layouts.homepage')

@php
    Theme::layout('homepage');
    $siteTitle = theme_option('site_title', config('app.name'));
    $siteDesc  = theme_option('seo_description', theme_option('hero_description', ''));
    $ogImage   = theme_option('logo') ? RvMedia::getImageUrl(theme_option('logo')) : null;
@endphp

@section('title', $siteTitle)
@section('description', $siteDesc)

@section('seo_head')
    <link rel="canonical" href="{{ url('/') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $siteTitle }}">
    <meta property="og:description" content="{{ $siteDesc }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $siteTitle }}">
    <meta name="twitter:description" content="{{ $siteDesc }}">
@endsection

@section('content')

{{-- ======================================================
     HERO
     ====================================================== --}}
<section class="hero" style="background-image: url('{{ asset('themes/caixilhariablanco/images/hero-bg.jpg') }}');">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-xl-7">
                <div class="hero-content">
                    <div class="section-title dark-section">
                        <h3>{{ theme_option('hero_subtitle', 'Distribuidor oficial Salamander desde 2009') }}</h3>
                        <h1>{{ theme_option('hero_title', 'Expertos en ventanas PVC y portones automáticos') }}</h1>
                        <p>{{ theme_option('hero_description', 'Calidad certificada, instalación profesional en Alcobaça, Portugal.') }}</p>
                    </div>
                    <div class="hero-btn">
                        <a href="{{ theme_option('hero_btn_url', '/servicios') }}" class="btn-default">{{ theme_option('hero_btn_text', 'Ver servicios') }}</a>
                        <a href="{{ theme_option('contact_url', '/contacto') }}" class="btn-default btn-highlighted">Contactar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================
     FEATURES / BENEFICIOS
     ====================================================== --}}
<section class="about-us" style="padding: 70px 0;">
    <div class="container">
        <div class="row g-4">
            <div class="col-sm-6 col-lg-3">
                <div class="work-facility-item" style="display:block; position:relative; padding-right:0;">
                    <div class="about-us-item">
                        <div class="icon-box" style="width:60px; height:60px; display:flex; align-items:center; justify-content:center; background:var(--secondary-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                            <i class="fas fa-certificate fa-xl" style="color:var(--accent-color);"></i>
                        </div>
                        <div class="about-item-content">
                            <h3>Distribuidor oficial</h3>
                            <p>Salamander premium desde 2009</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="about-us-item">
                    <div class="icon-box" style="width:60px; height:60px; display:flex; align-items:center; justify-content:center; background:var(--secondary-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                        <i class="fas fa-tools fa-xl" style="color:var(--accent-color);"></i>
                    </div>
                    <div class="about-item-content">
                        <h3>Instalación profesional</h3>
                        <p>Equipo técnico certificado</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="about-us-item">
                    <div class="icon-box" style="width:60px; height:60px; display:flex; align-items:center; justify-content:center; background:var(--secondary-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                        <i class="fas fa-shield-alt fa-xl" style="color:var(--accent-color);"></i>
                    </div>
                    <div class="about-item-content">
                        <h3>Garantía total</h3>
                        <p>Respaldo completo en materiales y montaje</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="about-us-item">
                    <div class="icon-box" style="width:60px; height:60px; display:flex; align-items:center; justify-content:center; background:var(--secondary-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                        <i class="fas fa-headset fa-xl" style="color:var(--accent-color);"></i>
                    </div>
                    <div class="about-item-content">
                        <h3>Soporte 24/7</h3>
                        <p>Asistencia post-venta personalizada</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================
     SERVICIOS PRINCIPALES
     ====================================================== --}}
<section class="our-services">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <div class="section-title">
                    <h3>Lo que ofrecemos</h3>
                    <h2>Nuestros servicios</h2>
                    <p>Soluciones a medida para tu hogar</p>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="post-item">
                    <div class="post-featured-image image-anime">
                        <a href="{{ theme_option('hero_btn_url', '/servicios') }}">
                            <img src="{{ asset('themes/caixilhariablanco/images/contact-info-img-1.jpg') }}"
                                 alt="Ventanas PVC" loading="lazy">
                        </a>
                    </div>
                    <div class="post-item-content" style="margin-top:20px;">
                        <h2>Ventanas PVC</h2>
                        <p>Ventanas de alta eficiencia energética con perfiles Salamander premium. Doble y triple acristalamiento.</p>
                    </div>
                    <div class="post-item-btn">
                        <a href="{{ theme_option('hero_btn_url', '/servicios') }}">Más información</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="post-item">
                    <div class="post-featured-image image-anime">
                        <a href="{{ theme_option('hero_btn_url', '/servicios') }}">
                            <img src="{{ asset('themes/caixilhariablanco/images/contact-info-img-2.jpg') }}"
                                 alt="Portones automáticos" loading="lazy">
                        </a>
                    </div>
                    <div class="post-item-content" style="margin-top:20px;">
                        <h2>Portones automáticos</h2>
                        <p>Portones de garaje y acceso automáticos con sistemas de última generación y alta seguridad.</p>
                    </div>
                    <div class="post-item-btn">
                        <a href="{{ theme_option('hero_btn_url', '/servicios') }}">Más información</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="post-item">
                    <div class="post-featured-image image-anime">
                        <a href="{{ theme_option('hero_btn_url', '/servicios') }}">
                            <img src="{{ asset('themes/caixilhariablanco/images/contact-info-img-3.jpg') }}"
                                 alt="Estores y cortinas" loading="lazy">
                        </a>
                    </div>
                    <div class="post-item-content" style="margin-top:20px;">
                        <h2>Estores y cortinas</h2>
                        <p>Estores enrollables, venecianas y cortinas técnicas para protección solar y privacidad.</p>
                    </div>
                    <div class="post-item-btn">
                        <a href="{{ theme_option('hero_btn_url', '/servicios') }}">Más información</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================
     CONTADORES DE ESTADÍSTICAS
     ====================================================== --}}
<section class="our-faqs" style="padding: 80px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center" style="margin-bottom:60px;">
                <div class="section-title dark-section">
                    <h3>En números</h3>
                    <h2>Por qué elegir Caixilharia Blanco</h2>
                </div>
            </div>
        </div>
        <div class="row g-4 text-center">
            <div class="col-sm-6 col-lg-3">
                <div class="work-counter-item" style="width:100%; text-align:center;">
                    <h3 style="font-size:56px; color:var(--accent-color); font-weight:700;">
                        <span class="counter">15</span>+
                    </h3>
                    <p style="color:var(--white-color); font-size:16px; margin-top:8px;">Años de experiencia</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="work-counter-item" style="width:100%; text-align:center;">
                    <h3 style="font-size:56px; color:var(--accent-color); font-weight:700;">
                        <span class="counter">500</span>+
                    </h3>
                    <p style="color:var(--white-color); font-size:16px; margin-top:8px;">Proyectos completados</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="work-counter-item" style="width:100%; text-align:center;">
                    <h3 style="font-size:56px; color:var(--accent-color); font-weight:700;">
                        <span class="counter">98</span>%
                    </h3>
                    <p style="color:var(--white-color); font-size:16px; margin-top:8px;">Clientes satisfechos</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="work-counter-item" style="width:100%; text-align:center;">
                    <h3 style="font-size:56px; color:var(--accent-color); font-weight:700;">
                        <span class="counter">2009</span>
                    </h3>
                    <p style="color:var(--white-color); font-size:16px; margin-top:8px;">Año de fundación</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================
     CTA
     ====================================================== --}}
<section class="our-work" style="background:var(--secondary-color); padding: 80px 0;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="section-title">
                    <h3>Presupuesto gratuito</h3>
                    <h2>¿Listo para transformar tu hogar?</h2>
                    <p>Solicita un presupuesto sin compromiso y uno de nuestros especialistas te asesorará personalmente.</p>
                </div>
                <div class="our-work-btn">
                    <a href="{{ theme_option('contact_url', '/contacto') }}" class="btn-default">Contactar ahora</a>
                </div>
            </div>
            <div class="col-lg-6 text-center mt-5 mt-lg-0">
                <div class="image-anime">
                    <img src="{{ asset('themes/caixilhariablanco/images/all-images/cta-img1.png') }}"
                         alt="Contactar Caixilharia Blanco" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================
     INFORMACIÓN DE CONTACTO
     ====================================================== --}}
<section class="about-us" style="padding: 70px 0;">
    <div class="container">
        <div class="row justify-content-center g-4">
            <div class="col-sm-6 col-lg-3">
                <div class="footer-contact-item align-items-start">
                    <div class="icon-box" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; background:var(--accent-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                        <i class="fas fa-map-marker-alt" style="color:#fff;"></i>
                    </div>
                    <div class="footer-contact-content">
                        <h3 style="font-size:18px; margin-bottom:6px;">Dirección</h3>
                        <p style="color:var(--text-color);">
                            {{ theme_option('address', 'Estrada Nacional 8 N12, Alcobaça') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="footer-contact-item align-items-start">
                    <div class="icon-box" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; background:var(--accent-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                        <i class="fas fa-phone" style="color:#fff;"></i>
                    </div>
                    <div class="footer-contact-content">
                        <h3 style="font-size:18px; margin-bottom:6px;">Teléfono</h3>
                        <p style="color:var(--text-color);">
                            <a href="tel:{{ theme_option('phone', '+351913893833') }}"
                               style="color:var(--text-color);">
                                {{ theme_option('phone', '+351 913 893 833') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="footer-contact-item align-items-start">
                    <div class="icon-box" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; background:var(--accent-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                        <i class="fas fa-envelope" style="color:#fff;"></i>
                    </div>
                    <div class="footer-contact-content">
                        <h3 style="font-size:18px; margin-bottom:6px;">Email</h3>
                        <p style="color:var(--text-color);">
                            <a href="mailto:{{ theme_option('contact_email', 'info@caixilhariablanco.pt') }}"
                               style="color:var(--text-color);">
                                {{ theme_option('contact_email', 'info@caixilhariablanco.pt') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="footer-contact-item align-items-start">
                    <div class="icon-box" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center; background:var(--accent-color); border-radius:50%; margin-right:20px; flex-shrink:0;">
                        <i class="fas fa-clock" style="color:#fff;"></i>
                    </div>
                    <div class="footer-contact-content">
                        <h3 style="font-size:18px; margin-bottom:6px;">Horario</h3>
                        <p style="color:var(--text-color);">
                            {{ theme_option('working_hours', 'Lun-Vie 9:00-18:00 / Sáb 9:00-13:00') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================
     RESEÑAS GOOGLE
     ====================================================== --}}
@if(class_exists('Modules\Reviews\Models\Review'))
@php
    $featuredReviews = \Modules\Reviews\Models\Review::query()
        ->where(function ($q) {
            $q->whereHas('moderation', fn ($m) => $m->where('is_visible', true))
              ->orWhereDoesntHave('moderation');
        })
        ->with('moderation')
        ->orderByDesc('star_rating')
        ->orderByDesc('review_time')
        ->limit(6)
        ->get();
@endphp
@if($featuredReviews->isNotEmpty())
<section class="our-faqs" style="padding: 70px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center" style="margin-bottom:48px;">
                <div class="section-title dark-section">
                    <h3>Opiniones verificadas</h3>
                    <h2>Lo que dicen nuestros clientes</h2>
                </div>
            </div>
        </div>
        @include(Theme::getThemeNamespace() . '::partials.shortcodes.reviews', [
            'limit'            => 6,
            'show_rating'      => true,
            'reviews_override' => $featuredReviews,
        ])
    </div>
</section>
@endif
@endif

@endsection
