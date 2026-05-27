{{-- Modal: Cambiar estado de la conversación --}}
@php
    $statusIconMap = [
        'nuevo'     => 'fa-solid fa-circle',
        'activo'    => 'fa-solid fa-circle-play',
        'esperando' => 'fa-regular fa-clock',
        'resuelto'  => 'fa-solid fa-check',
        'cerrado'   => 'fa-solid fa-lock',
        'archivado' => 'fa-solid fa-box-archive',
    ];
@endphp
<div class="bv-modal" data-bv-modal-name="status">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-solid fa-circle-half-stroke"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">CHAT · BANDEJA</div>
                <div class="modal-title">Cambiar estado</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="reason-list">
                @foreach($statuses ?? [] as $s)
                    @php
                        $icon = $statusIconMap[strtolower($s->name)] ?? 'fa-solid fa-circle';
                    @endphp
                    <div class="reason {{ $selectedConversation?->status_id === $s->id ? 'on' : '' }}"
                         data-bv-value="{{ $s->id }}"
                         data-bv-label="{{ $s->name }}"
                         data-bv-color="{{ $s->color }}">
                        <div class="ic"><i class="{{ $icon }}"></i></div>
                        <div class="body">
                            <span class="t">{{ $s->name }}</span>
                            @if($s->description)
                                <span class="s">{{ $s->description }}</span>
                            @endif
                        </div>
                        <div class="radio"></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-primary" data-bv-apply="status">Guardar cambios</button>
            <button class="btn btn-outline" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>
