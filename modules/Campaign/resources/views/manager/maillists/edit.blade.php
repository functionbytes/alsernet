@extends('theme::layouts.manager')

@section('title', 'Editar lista')

@section('content')
<div class="container py-4" style="max-width:680px">
    <h2>Editar lista: {{ $list->name }}</h2>

    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="post" action="{{ route('manager.campaigns.maillists.update', $list->uid) }}">
        @csrf @method('PUT')

        <div class="mb-3"><label class="form-label">Nombre *</label>
            <input type="text" name="name" value="{{ old('name', $list->name) }}" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Descripción</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $list->description) }}</textarea></div>

        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">From email</label>
                <input type="email" name="from_email" value="{{ old('from_email', $list->from_email) }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">From name</label>
                <input type="text" name="from_name" value="{{ old('from_name', $list->from_name) }}" class="form-control"></div>
        </div>

        <div class="mb-3"><label class="form-label">Asunto por defecto</label>
            <input type="text" name="default_subject" value="{{ old('default_subject', $list->default_subject) }}" class="form-control"></div>

        <h5 class="mt-4">Datos de contacto (footer legal)</h5>
        <div class="mb-3"><label class="form-label">Empresa</label>
            <input type="text" name="contact_company" value="{{ old('contact_company', $list->contact_company) }}" class="form-control"></div>
        <div class="row mb-3">
            <div class="col-md-8"><label class="form-label">Dirección</label>
                <input type="text" name="contact_address_1" value="{{ old('contact_address_1', $list->contact_address_1) }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Ciudad</label>
                <input type="text" name="contact_city" value="{{ old('contact_city', $list->contact_city) }}" class="form-control"></div>
        </div>

        <h5 class="mt-4">Política</h5>
        <div class="form-check mb-2"><input type="checkbox" name="subscribe_confirmation" value="1" id="dco" class="form-check-input" @checked($list->subscribe_confirmation)><label for="dco" class="form-check-label">Doble opt-in</label></div>
        <div class="form-check mb-2"><input type="checkbox" name="send_welcome_email" value="1" id="we" class="form-check-input" @checked($list->send_welcome_email)><label for="we" class="form-check-label">Email de bienvenida</label></div>
        <div class="form-check mb-3"><input type="checkbox" name="unsubscribe_notification" value="1" id="un" class="form-check-input" @checked($list->unsubscribe_notification)><label for="un" class="form-check-label">Notificar al admin desuscripciones</label></div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('manager.campaigns.maillists.show', $list->uid) }}" class="btn btn-link">Cancelar</a>
    </form>
</div>
@endsection
