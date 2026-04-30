{{-- Modal: Cambiar estado de la conversación --}}
<div class="bv-modal" data-bv-modal-name="status">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-circle-dot bv-modal-title-icon"></i> Cambiar estado</div>
            <button class="bv-modal-close" data-bv-close>
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-opt-list" data-bv-opt-group="status">
                @foreach($statuses as $s)
                    <button class="bv-opt" data-bv-value="{{ $s->id }}" data-bv-label="{{ $s->name }}" data-bv-color="{{ $s->color }}">
                        <span class="dot" style="background:var(--bv-{{ $s->color ?? 'success' }})"></span>
                        <div class="body">
                            <div class="name">{{ $s->name }}</div>
                            @if($s->description)
                                <div class="sub">{{ $s->description }}</div>
                            @endif
                        </div>
                        <i class="fas fa-check check"></i>
                    </button>
                @endforeach
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" data-bv-apply="status"><i class="fas fa-check"></i> Guardar cambios</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>
