@extends('theme::layouts.manager')

@section('title', 'Destinatarios · '.$campaign->name)

@section('content')
<div class="container py-4" style="max-width:880px">
    <h2>Destinatarios</h2>
    <p class="text-muted">Selecciona las listas (y opcionalmente segmentos) a los que enviar.</p>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="post" action="{{ route('manager.campaigns.recipients', $campaign->uid) }}">
        @csrf
        <div id="lists-container">
            @php
                $rows = $selected->isEmpty() ? collect([(object) ['mail_list_id' => '', 'segment_id' => null]]) : $selected;
            @endphp
            @foreach ($rows as $i => $row)
                <div class="row g-2 mb-2 align-items-end list-row">
                    <div class="col-md-6">
                        <label class="form-label small">Lista</label>
                        <select name="lists[{{ $i }}][mail_list_id]" class="form-select" required>
                            <option value="">— seleccionar —</option>
                            @foreach ($mailLists as $l)
                                <option value="{{ $l->id }}" @selected($row->mail_list_id == $l->id)>{{ $l->name }} ({{ $l->subscribeCount() }} suscritos)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Segmento (opcional)</label>
                        <select name="lists[{{ $i }}][segment_id]" class="form-select">
                            <option value="">— toda la lista —</option>
                            @foreach ($mailLists as $l)
                                @foreach ($l->segments as $seg)
                                    <option value="{{ $seg->id }}" @selected($row->segment_id == $seg->id)>{{ $l->name }} → {{ $seg->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-link text-danger remove-row">×</button>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button" id="add-row" class="btn btn-outline-secondary btn-sm mb-3">+ Añadir lista</button>

        <hr>
        <button type="submit" class="btn btn-primary">Guardar destinatarios</button>
        <a href="{{ route('manager.campaigns.show', $campaign->uid) }}" class="btn btn-link">Cancelar</a>
    </form>
</div>

<script>
    document.getElementById('add-row').addEventListener('click', () => {
        const c = document.getElementById('lists-container');
        const i = c.children.length;
        const tpl = c.firstElementChild.cloneNode(true);
        tpl.querySelectorAll('select').forEach((s, idx) => {
            s.name = idx === 0 ? `lists[${i}][mail_list_id]` : `lists[${i}][segment_id]`;
            s.value = '';
        });
        c.appendChild(tpl);
    });
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-row')) {
            const rows = document.querySelectorAll('.list-row');
            if (rows.length > 1) e.target.closest('.list-row').remove();
        }
    });
</script>
@endsection
