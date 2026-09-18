{{-- Contenido de la pestaña "Actividad" del panel derecho — cargado bajo
     demanda por RightPanelTabController@activity. Recibe $rpEvents. --}}
@php
    $rpEventIcons = [
        'status_change'   => 'fas fa-circle-dot',
        'assigned'        => 'fas fa-user-check',
        'unassigned'      => 'fas fa-user-minus',
        'closed'          => 'fas fa-circle-xmark',
        'reopened'        => 'fas fa-rotate-left',
        'archived'        => 'fas fa-box-archive',
        'unarchived'      => 'fas fa-box-open',
        'priority_changed'=> 'fas fa-flag',
        'internal_note'   => 'fas fa-note-sticky',
        'attachment_added'=> 'fas fa-paperclip',
        'customer_replied'=> 'fas fa-reply',
    ];
@endphp
@if($rpEvents->isEmpty())
    <div class="bv-tab-empty">
        <i class="fas fa-clock-rotate-left"></i>
        <div class="bv-tab-empty-title">{{ __('helpdesk::helpdesk.inbox.right.no_activity_title') }}</div>
        <div class="bv-tab-empty-sub">{{ __('helpdesk::helpdesk.inbox.right.no_activity_sub') }}</div>
    </div>
@else
    <div class="rsp-section bv-x49">
        <div class="lbl"><i class="fas fa-bolt-lightning"></i> {{ __('helpdesk::helpdesk.inbox.right.activity_timeline') }}</div>
        <div class="rsp-timeline">
            @foreach($rpEvents as $event)
            <div class="rsp-tl-item">
                <div class="ic">
                    <i class="{{ $rpEventIcons[$event->type] ?? 'fas fa-circle-info' }}"></i>
                </div>
                <div class="body">
                    <div class="t">{{ $event->event_label }}</div>
                    <div class="s">
                        {{ $event->created_at?->diffForHumans() }}
                        @if($event->sender_name !== 'Sistema') · {{ $event->sender_name }} @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
@endif
