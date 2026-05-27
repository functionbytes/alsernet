{{-- Modal: Atajos de teclado --}}
<div class="bv-modal" data-bv-modal-name="shortcuts">
    <div class="bv-modal-dialog bv-modal-dialog--wide">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fad fa-keyboard bv-modal-title-icon"></i> Atajos de teclado</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-shortcuts-wrap">
                <div>
                    <div class="bv-right-section-title bv-rst-mb8">Navegacion</div>
                    <div class="bv-sc-group">
                        @foreach([
                            ['J / ↓', 'Siguiente conversacion'],
                            ['K / ↑', 'Conversacion anterior'],
                            ['/', 'Buscar conversaciones'],
                            ['⌘K', 'Buscar cliente'],
                            ['?', 'Mostrar atajos'],
                            ['Tab', 'Saltar al hilo (desde lista)'],
                            ['Shift+Tab', 'Saltar al panel derecho'],
                        ] as $sc)
                            <div class="bv-sc-row">
                                <span>{{ $sc[1] }}</span>
                                <kbd class="bv-sc-kbd">{{ $sc[0] }}</kbd>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="bv-right-section-title bv-rst-mb8">Acciones sobre conversacion</div>
                    <div class="bv-sc-group">
                        @foreach([
                            ['R', 'Responder (enfocar compositor)'],
                            ['N', 'Nota interna'],
                            ['A', 'Asignar'],
                            ['T', 'Etiquetar'],
                            ['S', 'Cambiar estado'],
                            ['P', 'Cambiar prioridad'],
                            ['F', 'Filtrar'],
                            ['#', 'Cerrar conversacion (modal)'],
                            ['⌘E', 'Archivar conversacion'],
                            ['⌘⇧D', 'Cerrar conversacion (directo)'],
                            ['⌘/', 'Abrir/cerrar atajos'],
                            ['Esc', 'Cerrar modal / salir de modo concentracion'],
                        ] as $sc)
                            <div class="bv-sc-row">
                                <span>{{ $sc[1] }}</span>
                                <kbd class="bv-sc-kbd">{{ $sc[0] }}</kbd>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="bv-right-section-title bv-rst-mb8">Ir a (G + tecla)</div>
                    <div class="bv-sc-group">
                        @foreach([
                            ['G → U', 'No leidas'],
                            ['G → M', 'Mis conversaciones'],
                            ['G → A', 'Todas'],
                            ['G → R', 'Urgentes'],
                        ] as $sc)
                            <div class="bv-sc-row">
                                <span>{{ $sc[1] }}</span>
                                <kbd class="bv-sc-kbd">{{ $sc[0] }}</kbd>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="bv-right-section-title bv-rst-mb8">Productividad</div>
                    <div class="bv-sc-group">
                        @foreach([
                            ['Icono expandir', 'Modo concentracion (oculta paneles laterales)'],
                            ['Icono volumen', 'Activar/silenciar sonido de notificacion'],
                        ] as $sc)
                            <div class="bv-sc-row">
                                <span>{{ $sc[1] }}</span>
                                <kbd class="bv-sc-kbd">{{ $sc[0] }}</kbd>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="bv-right-section-title bv-rst-mb8">Composer</div>
                    <div class="bv-sc-group">
                        @foreach([
                            ['⌘↵', 'Enviar mensaje'],
                            ['/', 'Insertar respuesta guardada'],
                        ] as $sc)
                            <div class="bv-sc-row">
                                <span>{{ $sc[1] }}</span>
                                <kbd class="bv-sc-kbd">{{ $sc[0] }}</kbd>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
