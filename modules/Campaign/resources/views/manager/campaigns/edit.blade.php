@extends('theme::layouts.manager')

@section('title', 'Editar campaña')

@section('content')
<div class="container py-4" style="max-width:880px">
    <h2>Editar: {{ $campaign->name }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="post" action="{{ route('manager.campaigns.update', $campaign->uid) }}">
        @csrf @method('PUT')

        <div class="mb-3"><label class="form-label">Nombre interno *</label>
            <input type="text" name="name" value="{{ old('name', $campaign->name) }}" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Asunto *</label>
            <input type="text" name="subject" value="{{ old('subject', $campaign->subject) }}" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Preheader</label>
            <input type="text" name="preheader" value="{{ old('preheader', $campaign->preheader) }}" class="form-control" maxlength="120" placeholder="Texto que se ve en el preview del cliente de email"></div>

        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">From email *</label>
                <input type="email" name="from_email" value="{{ old('from_email', $campaign->from_email) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">From name *</label>
                <input type="text" name="from_name" value="{{ old('from_name', $campaign->from_name) }}" class="form-control" required></div>
        </div>

        <div class="mb-3"><label class="form-label">Reply-To</label>
            <input type="email" name="reply_to" value="{{ old('reply_to', $campaign->reply_to) }}" class="form-control"></div>

        <div class="mb-3"><label class="form-label">Lista por defecto</label>
            <select name="default_maillist_id" class="form-select">
                <option value="">— sin asignar —</option>
                @foreach ($mailLists as $l)<option value="{{ $l->id }}" @selected($campaign->default_maillist_id == $l->id)>{{ $l->name }}</option>@endforeach
            </select></div>

        <div class="mb-3"><label class="form-label">Plantilla</label>
            <select name="template_id" class="form-select">
                <option value="">— sin plantilla —</option>
                @foreach ($templates as $t)<option value="{{ $t->id }}" @selected($campaign->template_id == $t->id)>{{ $t->name }}</option>@endforeach
            </select></div>

        <div class="mb-3"><label class="form-label">Tracking domain</label>
            <select name="tracking_domain_id" class="form-select">
                <option value="">— dominio app —</option>
                @foreach ($trackingDomains as $td)<option value="{{ $td->id }}" @selected($campaign->tracking_domain_id == $td->id)>{{ $td->name }}</option>@endforeach
            </select></div>

        <div class="mb-3"><label class="form-label">Programar envío</label>
            <input type="datetime-local" name="run_at" value="{{ old('run_at', optional($campaign->run_at)->format('Y-m-d\TH:i')) }}" class="form-control"></div>

        <div class="mb-3"><label class="form-label">Plain text alternativo</label>
            <textarea name="plain" rows="6" class="form-control font-monospace">{{ old('plain', $campaign->plain) }}</textarea></div>

        <div class="form-check mb-2"><input type="checkbox" name="track_open" value="1" id="to" class="form-check-input" @checked($campaign->track_open)><label for="to" class="form-check-label">Tracking de aperturas</label></div>
        <div class="form-check mb-2"><input type="checkbox" name="track_click" value="1" id="tc" class="form-check-input" @checked($campaign->track_click)><label for="tc" class="form-check-label">Tracking de clicks</label></div>
        <div class="form-check mb-3"><input type="checkbox" name="sign_dkim" value="1" id="dkim" class="form-check-input" @checked($campaign->sign_dkim)><label for="dkim" class="form-check-label">Firmar con DKIM</label></div>

        <button type="submit" class="btn btn-primary">Guardar cambios</button>
        <a href="{{ route('manager.campaigns.show', $campaign->uid) }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
