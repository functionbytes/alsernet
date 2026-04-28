@extends('theme::layouts.manager')

@section('title', 'Nueva campaña')

@section('content')
    <div class="container py-4" style="max-width:880px">
        <h2>Nueva campaña</h2>

        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ route('manager.campaigns.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Nombre interno *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Asunto del email *</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="form-control" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">From email *</label>
                    <input type="email" name="from_email" value="{{ old('from_email') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">From name *</label>
                    <input type="text" name="from_name" value="{{ old('from_name') }}" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Reply-To</label>
                <input type="email" name="reply_to" value="{{ old('reply_to') }}" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Lista por defecto</label>
                <select name="default_maillist_id" class="form-select">
                    <option value="">— sin asignar —</option>
                    @foreach ($mailLists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Plantilla</label>
                <select name="template_id" class="form-select">
                    <option value="">— sin plantilla —</option>
                    @foreach ($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Tracking domain</label>
                <select name="tracking_domain_id" class="form-select">
                    <option value="">— usar dominio de la app —</option>
                    @foreach ($trackingDomains as $td)<option value="{{ $td->id }}">{{ $td->name }}</option>@endforeach
                </select>
            </div>

            <div class="form-check mb-2"><input type="checkbox" name="track_open" value="1" id="to" class="form-check-input" checked><label for="to" class="form-check-label">Tracking de aperturas</label></div>
            <div class="form-check mb-2"><input type="checkbox" name="track_click" value="1" id="tc" class="form-check-input" checked><label for="tc" class="form-check-label">Tracking de clicks</label></div>
            <div class="form-check mb-3"><input type="checkbox" name="sign_dkim" value="1" id="dkim" class="form-check-input"><label for="dkim" class="form-check-label">Firmar con DKIM (requiere SendingDomain verificado)</label></div>

            <button type="submit" class="btn btn-primary">Crear</button>
            <a href="{{ route('manager.campaigns.index') }}" class="btn btn-link">Cancelar</a>
        </form>
    </div>
@endsection
