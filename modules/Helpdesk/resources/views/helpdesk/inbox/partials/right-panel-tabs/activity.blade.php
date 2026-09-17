{{-- Contenido de la pestaña "Actividad" del panel derecho — cargado bajo
     demanda por RightPanelTabController@activity. Recibe $rpEventGroups
     (colección de eventos ya formateados, agrupada por etiqueta de día) y
     $rpEventsCount. El ícono/tono/título/tarjeta de email de cada evento ya
     vienen resueltos desde el controlador (ver RightPanelTabController::
     formatActivityEvent()) — antes ese cálculo vivía aquí y usaba el campo
     equivocado ($event->type, que para estos eventos siempre vale el
     literal 'activity'), así que el ícono nunca cambiaba de uno genérico. --}}
@if($rpEventGroups->isEmpty())
    <div class="bv-tab-empty">
        <i class="fas fa-clock-rotate-left"></i>
        <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_activity_title') }}</div>
        <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_activity_sub') }}</div>
    </div>
@else
    <div class="rsp-section bv-x49">
        <div class="lbl">
            <i class="fas fa-bolt-lightning"></i> {{ __('helpdesk::helpdesk.inbox.right.activity_timeline') }}
        </div>
        @foreach($rpEventGroups as $dayLabel => $dayEvents)
            <div class="rsp-tl-day">{{ $dayLabel }}</div>
            <div class="rsp-timeline">
                @foreach($dayEvents as $item)
                <div class="rsp-tl-item">
                    <div class="ic {{ $item['tone'] !== 'neutral' ? $item['tone'] : '' }}">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </div>
                    <div class="body">
                        <div class="t">{{ $item['title'] }}</div>
                        @if($item['email'])
                            <div class="rsp-tl-em-card">
                                <div class="rsp-tl-em-subj">{{ $item['email']['subject'] }}</div>
                                <div class="rsp-tl-em-status {{ $item['email']['delivered'] ? '' : 'is-pending' }}">
                                    <i class="fas {{ $item['email']['delivered'] ? 'fa-check' : 'fa-clock' }}"></i>
                                    {{ $item['email']['status_label'] }}
                                </div>
                            </div>
                        @endif
                        <div class="s">{{ $item['subtitle'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
