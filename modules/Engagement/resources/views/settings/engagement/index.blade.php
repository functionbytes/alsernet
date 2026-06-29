@extends('layouts.theme')

@section('title', 'Engagement — Configuración')

@section('content')
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #90bb13 0%, #7b0000 100%);">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="mb-0 text-white">
                    <i class="fas fa-sliders-h me-2"></i>Configuración de Engagement
                </h4>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                @foreach ($stats as $key => $stat)
                    @can($stat['permission'])
                        <div class="col-12 col-md-6 col-lg-4 col-xl">
                            <a href="{{ route($stat['route']) }}" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm hover-shadow transition-all">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                             style="width: 48px; height: 48px; background: var(--bs-{{ $stat['color'] }}); opacity: 0.15;">
                                            <i class="fas {{ $stat['icon'] }} fs-4 text-{{ $stat['color'] }}"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-muted">{{ $stat['label'] }}</h6>
                                            <span class="fs-4 fw-semibold">{{ $stat['count'] }}</span>
                                            <span class="text-muted small">activos</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endcan
                @endforeach
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-12 col-lg-8">
                    <h5 class="mb-3">
                        <i class="fas fa-book-open me-2 text-primary"></i>Guía rápida
                    </h5>
                    <div class="accordion" id="engagementGuide">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guideTriggers">
                                    <i class="fas fa-bolt me-2 text-warning"></i>Reglas de activación
                                </button>
                            </h2>
                            <div id="guideTriggers" class="accordion-collapse collapse" data-bs-parent="#engagementGuide">
                                <div class="accordion-body">
                                    Define cuándo mostrar mensajes, banners o redirigir a visitantes según su score, eventos o contexto. Usa variantes A/B para probar diferentes acciones.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guidePersonalizations">
                                    <i class="fas fa-code me-2 text-info"></i>Personalización DOM
                                </button>
                            </h2>
                            <div id="guidePersonalizations" class="accordion-collapse collapse" data-bs-parent="#engagementGuide">
                                <div class="accordion-body">
                                    Modifica elementos de tu sitio web en tiempo real según el perfil del visitante. Cambia textos, atributos o inserta contenido sin tocar el código fuente.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guidePlatforms">
                                    <i class="fas fa-plug me-2 text-success"></i>Integraciones
                                </button>
                            </h2>
                            <div id="guidePlatforms" class="accordion-collapse collapse" data-bs-parent="#engagementGuide">
                                <div class="accordion-body">
                                    Conecta tu tienda PrestaShop, Shopify, WooCommerce o ERP para sincronizar catálogo de productos y recibir eventos de compra en tiempo real.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guideAutomation">
                                    <i class="fas fa-robot me-2 text-primary"></i>Automation
                                </button>
                            </h2>
                            <div id="guideAutomation" class="accordion-collapse collapse" data-bs-parent="#engagementGuide">
                                <div class="accordion-body">
                                    Crea flujos automáticos con nodos de mensaje, preguntas, condiciones y delays. Actúa sobre visitantes sin intervención manual.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guideGoals">
                                    <i class="fas fa-bullseye me-2 text-danger"></i>Objetivos de conversión
                                </button>
                            </h2>
                            <div id="guideGoals" class="accordion-collapse collapse" data-bs-parent="#engagementGuide">
                                <div class="accordion-body">
                                    Define metas de negocio y visualiza funnels de conversión. Mide cuántos visitantes completan cada paso y dónde abandonan.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#guideEmail">
                                    <i class="fas fa-envelope me-2 text-primary"></i>Campañas de email
                                </button>
                            </h2>
                            <div id="guideEmail" class="accordion-collapse collapse" data-bs-parent="#engagementGuide">
                                <div class="accordion-body">
                                    Diseña y programa campañas de email marketing con segmentación avanzada. Integra Mailchimp o SendGrid para enviar a tus listas de contactos.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <h5 class="mb-3">
                        <i class="fas fa-link me-2 text-primary"></i>Enlaces directos
                    </h5>
                    <div class="list-group">
                        @can('engagement.platforms.view')
                            <a href="{{ route('settings.engagement.webhook-logs.page') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-history me-2 text-muted"></i>Webhook logs</span>
                                <i class="fas fa-chevron-right small text-muted"></i>
                            </a>
                        @endcan
                        @can('engagement.manage')
                            <a href="{{ route('settings.engagement.audit-logs.page') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-clipboard-list me-2 text-muted"></i>Audit log</span>
                                <i class="fas fa-chevron-right small text-muted"></i>
                            </a>
                        @endcan
                        @can('engagement.email_campaigns.view')
                            <a href="{{ route('settings.engagement.email-campaigns.page') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-envelope me-2 text-muted"></i>Campañas de email</span>
                                <i class="fas fa-chevron-right small text-muted"></i>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
