{{-- Content tab --}}
<form method="POST" action="{{ route('helpdesk.campaigns.update', $campaign) }}" id="content-form">
    @csrf
    @method('PUT')

    <h6 class="fw-semibold mb-1">Bloques de contenido</h6>
    <p class="text-muted small mb-3">Componentes visuales que forman el cuerpo de la campaña</p>

    @php $content = old('content', $campaign->content ?? []); @endphp

    @if(empty($content))
        <div class="alert alert-info mb-3" id="empty-state">
            Sin contenido aún. Haz clic en "Agregar bloque" para comenzar a diseñar la campaña.
        </div>
    @endif

    <div id="content-blocks-container" class="mb-3">
        @foreach($content as $index => $block)
            @include('helpdeskcampaigns::managers.campaigns.partials.content-block', ['block' => $block, 'index' => $index])
        @endforeach
    </div>

    <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="addContentBlock()">
        <i class="fas fa-plus me-1"></i> Agregar bloque
    </button>

    <div class="d-flex justify-content-between border-top pt-3">
        <a href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'general']) }}" class="btn btn-light">
            Anterior: General
        </a>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar contenido</button>
            <a href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'appearance']) }}" class="btn btn-light">
                Siguiente: Apariencia
            </a>
        </div>
    </div>
</form>

{{-- Block template --}}
<template id="content-block-template">
    <div class="card mb-2 content-block" data-block-index="">
        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-grow-1">
                    <div class="mb-2">
                        <label class="form-label small mb-1">Tipo de bloque</label>
                        <select class="form-select form-select-sm block-type" name="content[][type]" onchange="updateBlockFields(this)">
                            <option value="text">Texto</option>
                            <option value="heading">Encabezado</option>
                            <option value="button">Botón</option>
                            <option value="image">Imagen</option>
                            <option value="html">HTML personalizado</option>
                        </select>
                    </div>
                    <div class="block-fields"></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removeBlock(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
    window.HcmContentTab = { blockCounter: {{ count($content) }} };
</script>
<script src="{{ asset('modules/helpdeskcampaigns/js/campaign-form-content.js') }}?v={{ @filemtime(public_path('modules/helpdeskcampaigns/js/campaign-form-content.js')) }}" defer></script>
@endpush
