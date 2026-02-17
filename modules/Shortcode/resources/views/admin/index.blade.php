@extends('layouts.theme')

@section('title', 'Shortcodes')

@section('content')
    @include('core::components.card', ['title' => 'Shortcodes'])

    <div class="widget-content searchable-container list">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Shortcodes disponibles</h5>
                        <p class="small mb-0 text-muted">Codigos cortos que puedes usar en el contenido para generar componentes dinamicos</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('setting.shortcode.reference') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-book me-1"></i> Referencia completa
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Shortcode</th>
                                <th>Descripcion</th>
                                <th>Ejemplo</th>
                                <th class="text-center">Vista previa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>[button]</code></td>
                                <td><small class="text-muted">Boton con enlace y estilos configurables</small></td>
                                <td><code class="small">[button url="#" class="primary"]Texto[/button]</code></td>
                                <td class="text-center">@shortcode('[button url="#" class="primary"]Ejemplo[/button]')</td>
                            </tr>
                            <tr>
                                <td><code>[alert]</code></td>
                                <td><small class="text-muted">Mensaje de alerta con tipos (success, info, warning, danger)</small></td>
                                <td><code class="small">[alert type="info"]Mensaje[/alert]</code></td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info">info</span>
                                    <span class="badge bg-success-subtle text-success">success</span>
                                    <span class="badge bg-warning-subtle text-warning">warning</span>
                                    <span class="badge bg-danger-subtle text-danger">danger</span>
                                </td>
                            </tr>
                            <tr>
                                <td><code>[badge]</code></td>
                                <td><small class="text-muted">Etiqueta con estilo tipo badge</small></td>
                                <td><code class="small">[badge type="primary"]Texto[/badge]</code></td>
                                <td class="text-center">@shortcode('[badge type="primary"]Nuevo[/badge]')</td>
                            </tr>
                            <tr>
                                <td><code>[icon]</code></td>
                                <td><small class="text-muted">Icono de Bootstrap Icons con tamano y color</small></td>
                                <td><code class="small">[icon name="heart" size="24" /]</code></td>
                                <td class="text-center">@shortcode('[icon name="heart" size="20" color="danger" /]')</td>
                            </tr>
                            <tr>
                                <td><code>[columns]</code></td>
                                <td><small class="text-muted">Distribucion en columnas con grid Bootstrap</small></td>
                                <td><code class="small">[columns count="3"][column]...[/column][/columns]</code></td>
                                <td class="text-center"><span class="badge bg-light text-dark">Layout grid</span></td>
                            </tr>
                            <tr>
                                <td><code>[card]</code></td>
                                <td><small class="text-muted">Tarjeta con titulo opcional</small></td>
                                <td><code class="small">[card title="Titulo"]Contenido[/card]</code></td>
                                <td class="text-center"><span class="badge bg-light text-dark">Componente card</span></td>
                            </tr>
                            <tr>
                                <td><code>[quote]</code></td>
                                <td><small class="text-muted">Cita con autor y fuente</small></td>
                                <td><code class="small">[quote author="Autor"]Texto[/quote]</code></td>
                                <td class="text-center"><span class="badge bg-light text-dark">Blockquote</span></td>
                            </tr>
                            <tr>
                                <td><code>[accordion]</code></td>
                                <td><small class="text-muted">Acordeon desplegable con multiples secciones</small></td>
                                <td><code class="small">[accordion id="x"][accordion-item]...[/accordion]</code></td>
                                <td class="text-center"><span class="badge bg-light text-dark">Accordion</span></td>
                            </tr>
                            <tr>
                                <td><code>[youtube]</code></td>
                                <td><small class="text-muted">Video de YouTube embebido responsive</small></td>
                                <td><code class="small">[youtube id="VIDEO_ID" /]</code></td>
                                <td class="text-center"><span class="badge bg-light text-dark">Iframe 16:9</span></td>
                            </tr>
                            <tr>
                                <td><code>[image]</code></td>
                                <td><small class="text-muted">Imagen del modulo Media</small></td>
                                <td><code class="small">[image id="123" size="medium" /]</code></td>
                                <td class="text-center"><span class="badge bg-light text-dark">Img responsive</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Ejemplos en vivo --}}
        <div class="card mt-3">
            <div class="card-header p-4 border-bottom border-light">
                <h5 class="mb-1 fw-bold">Ejemplos en vivo</h5>
                <p class="small mb-0 text-muted">Prueba los shortcodes y ve el resultado renderizado</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-semibold mb-2">Botones</h6>
                        <div class="p-3 bg-light rounded">
                            @shortcode('[button url="#" class="primary"]Primario[/button]')
                            @shortcode('[button url="#" class="success"]Exito[/button]')
                            @shortcode('[button url="#" class="danger"]Peligro[/button]')
                        </div>
                        <pre class="mt-2 p-2 bg-dark text-light rounded small mb-0"><code>[button url="#" class="primary"]Primario[/button]</code></pre>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-semibold mb-2">Alertas</h6>
                        <div class="p-3 bg-light rounded">
                            @shortcode('[alert type="success"]Operacion exitosa[/alert]')
                            @shortcode('[alert type="warning"]Atencion requerida[/alert]')
                        </div>
                        <pre class="mt-2 p-2 bg-dark text-light rounded small mb-0"><code>[alert type="success"]Mensaje[/alert]</code></pre>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-semibold mb-2">Badges</h6>
                        <div class="p-3 bg-light rounded">
                            @shortcode('[badge type="primary"]Principal[/badge]')
                            @shortcode('[badge type="success"]Activo[/badge]')
                            @shortcode('[badge type="info" pill="true"]Pill[/badge]')
                        </div>
                        <pre class="mt-2 p-2 bg-dark text-light rounded small mb-0"><code>[badge type="primary" pill="true"]Texto[/badge]</code></pre>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-semibold mb-2">Cita</h6>
                        <div class="p-3 bg-light rounded">
                            @shortcode('[quote author="Albert Einstein"]La imaginacion es mas importante que el conocimiento.[/quote]')
                        </div>
                        <pre class="mt-2 p-2 bg-dark text-light rounded small mb-0"><code>[quote author="Autor"]Texto[/quote]</code></pre>
                    </div>
                    <div class="col-12 mb-4">
                        <h6 class="fw-semibold mb-2">Ejemplo complejo</h6>
                        <div class="p-3 bg-light rounded">
                            @shortcode('[columns count="3" gap="3"]
                                [column][card title="Basico"]Plan basico con funcionalidades esenciales.[/card][/column]
                                [column][card title="Profesional"]Plan profesional con todo incluido.[/card][/column]
                                [column][card title="Empresa"]Plan empresarial personalizado.[/card][/column]
                            [/columns]')
                        </div>
                        <pre class="mt-2 p-2 bg-dark text-light rounded small mb-0"><code>[columns count="3"][column][card title="..."]...[/card][/column][/columns]</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
