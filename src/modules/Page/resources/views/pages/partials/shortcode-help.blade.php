{{--
    Panel de ayuda de shortcodes disponibles para el editor de páginas.
    Incluir dentro del card "Contenido", antes del editorWrapper.

    Variables requeridas:
      - $locale (string): locale activo del tab actual
--}}
<div class="accordion mb-2" id="shortcode-help-{{ $locale }}">
    <div class="accordion-item border">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2 bg-light"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#shortcode-list-{{ $locale }}"
                    aria-expanded="false">
                <i class="fas fa-code me-2 text-muted"></i>
                <span class="small fw-semibold">Shortcodes disponibles</span>
            </button>
        </h2>
        <div id="shortcode-list-{{ $locale }}"
             class="accordion-collapse collapse"
             data-bs-parent="#shortcode-help-{{ $locale }}">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:40%">Shortcode</th>
                                <th>Descripción</th>
                                <th class="text-center pe-3" style="width:80px">Insertar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $shortcodes = [
                                    [
                                        'tag'   => '[form id="1"]',
                                        'desc'  => 'Embeber formulario por ID',
                                        'text'  => '[form id="1"]',
                                        'show'  => class_exists(\Modules\Forms\Models\Form::class),
                                    ],
                                    [
                                        'tag'   => '[form slug="contacto"]',
                                        'desc'  => 'Embeber formulario por slug',
                                        'text'  => '[form slug="contacto"]',
                                        'show'  => class_exists(\Modules\Forms\Models\Form::class),
                                    ],
                                    [
                                        'tag'   => '[alert type="info" title="Nota"]Texto[/alert]',
                                        'desc'  => 'Caja de alerta Bootstrap (info, success, warning, danger)',
                                        'text'  => '[alert type="info" title="Nota"]Texto aquí[/alert]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[button url="/ruta" class="btn-primary"]Texto[/button]',
                                        'desc'  => 'Botón de enlace',
                                        'text'  => '[button url="/ruta" class="btn-primary"]Ver más[/button]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[columns count="2"][column]...[/column][/columns]',
                                        'desc'  => 'Layout en columnas Bootstrap',
                                        'text'  => '[columns count="2"][column]Columna 1[/column][column]Columna 2[/column][/columns]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[card title="Título"]Contenido[/card]',
                                        'desc'  => 'Tarjeta Bootstrap',
                                        'text'  => '[card title="Título"]Contenido de la tarjeta[/card]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[accordion id="acc1"][accordion-item title="Item" parent="acc1"]Texto[/accordion-item][/accordion]',
                                        'desc'  => 'Acordeón Bootstrap',
                                        'text'  => '[accordion id="acc1"][accordion-item title="Pregunta" parent="acc1"]Respuesta aquí[/accordion-item][/accordion]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[quote author="Autor"]Texto[/quote]',
                                        'desc'  => 'Cita con autor',
                                        'text'  => '[quote author="Autor" cite="Fuente"]Texto de la cita[/quote]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[badge type="primary"]Texto[/badge]',
                                        'desc'  => 'Etiqueta Bootstrap',
                                        'text'  => '[badge type="primary"]Nuevo[/badge]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[youtube id="dQw4w9WgXcQ" /]',
                                        'desc'  => 'Video de YouTube embebido',
                                        'text'  => '[youtube id="dQw4w9WgXcQ" /]',
                                        'show'  => true,
                                    ],
                                    [
                                        'tag'   => '[contact-form title="Contacto"]',
                                        'desc'  => 'Formulario de contacto básico',
                                        'text'  => '[contact-form title="Contáctenos"][/contact-form]',
                                        'show'  => true,
                                    ],
                                ];
                                $visibleShortcodes = array_filter($shortcodes, fn($s) => $s['show']);
                            @endphp

                            @foreach($visibleShortcodes as $sc)
                            <tr>
                                <td class="ps-3">
                                    <code class="text-nowrap" style="font-size:0.75rem">{{ $sc['tag'] }}</code>
                                </td>
                                <td class="text-muted">{{ $sc['desc'] }}</td>
                                <td class="text-center pe-3">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm py-0 px-2 shortcode-insert-btn"
                                            data-locale="{{ $locale }}"
                                            data-shortcode="{{ $sc['text'] }}"
                                            title="Insertar en el editor">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach

                            @if(class_exists(\Modules\Forms\Models\Form::class))
                            @php
                                $availableForms = \Modules\Forms\Models\Form::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->limit(20)
                                    ->get(['id', 'name', 'slug']);
                            @endphp
                            @if($availableForms->isNotEmpty())
                            <tr>
                                <td colspan="3" class="ps-3 pt-2 pb-1">
                                    <span class="text-muted fw-semibold">Formularios activos disponibles:</span>
                                </td>
                            </tr>
                            @foreach($availableForms as $form)
                            <tr class="table-light">
                                <td class="ps-3">
                                    <code class="text-nowrap" style="font-size:0.75rem">[form id="{{ $form->id }}"]</code>
                                </td>
                                <td class="text-muted">{{ $form->name }}</td>
                                <td class="text-center pe-3">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm py-0 px-2 shortcode-insert-btn"
                                            data-locale="{{ $locale }}"
                                            data-shortcode='[form id="{{ $form->id }}"]'
                                            title="Insertar formulario: {{ $form->name }}">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                            @endif
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
