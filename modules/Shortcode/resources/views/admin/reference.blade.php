@extends('layouts.theme')

@section('title', 'Referencia de shortcodes')

@section('page_header')
    @include('core::components.card', ['title' => 'Referencia de shortcodes'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Referencia de shortcodes</h5>
                        <p class="small mb-0 text-muted">Documentacion detallada de cada shortcode con atributos y ejemplos de uso</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(Route::has('settings.shortcodes.index'))
                            <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-cog me-1"></i> Gestionar shortcodes
                            </a>
                        @endif
                        <a href="{{ route('settings.shortcodes.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(!empty($shortcodes))
                    <div class="alert alert-info-subtle border-0 mb-4">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>{{ count($shortcodes) }} shortcodes</strong> registrados en el sistema.
                        Usa la sintaxis <code>[nombre atributo="valor"]contenido[/nombre]</code> o <code>[nombre atributo="valor" /]</code> para auto-cierre.
                    </div>
                @endif

                <div class="accordion" id="shortcodeReference">
                    {{-- Button --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ref-button">
                                <span class="badge bg-primary me-2">button</span> Boton con enlace
                            </button>
                        </h2>
                        <div id="ref-button" class="accordion-collapse collapse show" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[button url="/enlace" class="primary" target="_blank"]Texto del boton[/button]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>url</code></td><td>URL del enlace</td><td><code>#</code></td></tr>
                                        <tr><td><code>class</code></td><td>Clase del boton (primary, success, danger, etc.)</td><td><code>btn-primary</code></td></tr>
                                        <tr><td><code>target</code></td><td>Target del enlace (_blank, _self)</td><td>-</td></tr>
                                        <tr><td><code>id</code></td><td>ID del elemento</td><td>-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Alert --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-alert">
                                <span class="badge bg-primary me-2">alert</span> Mensaje de alerta
                            </button>
                        </h2>
                        <div id="ref-alert" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[alert type="success" dismissible="true"]Mensaje de alerta[/alert]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>type</code></td><td>Tipo: success, danger, warning, info</td><td><code>info</code></td></tr>
                                        <tr><td><code>dismissible</code></td><td>Permitir cerrar (true/false)</td><td><code>false</code></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Badge --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-badge">
                                <span class="badge bg-primary me-2">badge</span> Etiqueta badge
                            </button>
                        </h2>
                        <div id="ref-badge" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[badge type="primary" pill="true"]Nuevo[/badge]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>type</code></td><td>Tipo: primary, success, danger, warning, info</td><td><code>primary</code></td></tr>
                                        <tr><td><code>pill</code></td><td>Estilo redondeado (true/false)</td><td><code>false</code></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Icon --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-icon">
                                <span class="badge bg-primary me-2">icon</span> Icono Bootstrap
                            </button>
                        </h2>
                        <div id="ref-icon" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[icon name="heart" size="24" color="danger" /]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>name</code></td><td>Nombre del icono de Bootstrap Icons</td><td><code>circle</code></td></tr>
                                        <tr><td><code>size</code></td><td>Tamano en pixeles</td><td><code>24</code></td></tr>
                                        <tr><td><code>color</code></td><td>Color Bootstrap (primary, danger, etc.)</td><td>-</td></tr>
                                        <tr><td><code>class</code></td><td>Clases CSS adicionales</td><td>-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Columns --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-columns">
                                <span class="badge bg-primary me-2">columns</span> Distribucion en columnas
                            </button>
                        </h2>
                        <div id="ref-columns" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[columns count="3" gap="4"]
    [column]Contenido columna 1[/column]
    [column]Contenido columna 2[/column]
    [column]Contenido columna 3[/column]
[/columns]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos de columns</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>count</code></td><td>Numero de columnas</td><td><code>2</code></td></tr>
                                        <tr><td><code>gap</code></td><td>Espacio entre columnas (1-5)</td><td><code>3</code></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Card --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-card">
                                <span class="badge bg-primary me-2">card</span> Tarjeta
                            </button>
                        </h2>
                        <div id="ref-card" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[card title="Titulo" class="mb-3"]Contenido de la tarjeta[/card]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>title</code></td><td>Titulo de la tarjeta</td><td>-</td></tr>
                                        <tr><td><code>class</code></td><td>Clases CSS adicionales</td><td>-</td></tr>
                                        <tr><td><code>header_class</code></td><td>Clases del header</td><td>-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Quote --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-quote">
                                <span class="badge bg-primary me-2">quote</span> Cita
                            </button>
                        </h2>
                        <div id="ref-quote" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[quote author="Autor" cite="Fuente"]Texto de la cita[/quote]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>author</code></td><td>Autor de la cita</td><td>-</td></tr>
                                        <tr><td><code>cite</code></td><td>Fuente o referencia</td><td>-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Accordion --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-accordion">
                                <span class="badge bg-primary me-2">accordion</span> Acordeon
                            </button>
                        </h2>
                        <div id="ref-accordion" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[accordion id="miAcordeon"]
    [accordion-item title="Seccion 1" parent="miAcordeon" show="true"]
        Contenido de la seccion
    [/accordion-item]
    [accordion-item title="Seccion 2" parent="miAcordeon"]
        Mas contenido
    [/accordion-item]
[/accordion]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos de accordion</h6>
                                <table class="table table-sm table-bordered mb-3">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>id</code></td><td>ID del acordeon</td><td>Auto-generado</td></tr>
                                        <tr><td><code>class</code></td><td>Clases CSS adicionales</td><td>-</td></tr>
                                    </tbody>
                                </table>
                                <h6 class="fw-semibold">Atributos de accordion-item</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>title</code></td><td>Titulo del item</td><td><code>Accordion Item</code></td></tr>
                                        <tr><td><code>parent</code></td><td>ID del acordeon padre</td><td><code>accordion</code></td></tr>
                                        <tr><td><code>show</code></td><td>Mostrar abierto (true/false)</td><td><code>false</code></td></tr>
                                        <tr><td><code>id</code></td><td>ID del item</td><td>Auto-generado</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- YouTube --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-youtube">
                                <span class="badge bg-primary me-2">youtube</span> Video de YouTube
                            </button>
                        </h2>
                        <div id="ref-youtube" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[youtube id="VIDEO_ID" width="640" height="360" /]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>id</code></td><td>ID del video de YouTube (obligatorio)</td><td>-</td></tr>
                                        <tr><td><code>width</code></td><td>Ancho del video</td><td><code>560</code></td></tr>
                                        <tr><td><code>height</code></td><td>Alto del video</td><td><code>315</code></td></tr>
                                        <tr><td><code>title</code></td><td>Titulo del video (accesibilidad)</td><td>-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Image --}}
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ref-image">
                                <span class="badge bg-primary me-2">image</span> Imagen de Media
                            </button>
                        </h2>
                        <div id="ref-image" class="accordion-collapse collapse" data-bs-parent="#shortcodeReference">
                            <div class="accordion-body">
                                <h6 class="fw-semibold">Uso</h6>
                                <pre class="p-2 bg-dark text-light rounded small"><code>[image id="123" size="medium" class="rounded" alt="Descripcion" /]</code></pre>
                                <h6 class="fw-semibold mt-3">Atributos</h6>
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr><th>Atributo</th><th>Descripcion</th><th>Defecto</th></tr></thead>
                                    <tbody>
                                        <tr><td><code>id</code></td><td>ID del media (obligatorio)</td><td>-</td></tr>
                                        <tr><td><code>size</code></td><td>Tamano de la imagen</td><td><code>medium</code></td></tr>
                                        <tr><td><code>class</code></td><td>Clases CSS</td><td><code>img-fluid</code></td></tr>
                                        <tr><td><code>alt</code></td><td>Texto alternativo</td><td>-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
