@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Crear regla social</h5>
        </div>
        <div class="card-body">
            <form action="/api/helpdesk/social/rules" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="platform" class="form-label">Plataforma</label>
                    <select name="platform" id="platform" class="form-select">
                        <option value="">Todas las plataformas</option>
                        <option value="facebook" @selected(old('platform') == 'facebook')>Facebook</option>
                        <option value="instagram" @selected(old('platform') == 'instagram')>Instagram</option>
                        <option value="whatsapp" @selected(old('platform') == 'whatsapp')>WhatsApp</option>
                        <option value="tiktok" @selected(old('platform') == 'tiktok')>TikTok</option>
                        <option value="x" @selected(old('platform') == 'x')>X</option>
                        <option value="linkedin" @selected(old('platform') == 'linkedin')>LinkedIn</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="conditions" class="form-label">Condiciones</label>
                    <textarea name="conditions" id="conditions" class="form-control" rows="5">{{ old('conditions') }}</textarea>
                    <div class="form-text">Las condiciones deben ser un array JSON con objetos que tengan field, operator y value.</div>
                </div>

                <div class="mb-3">
                    <label for="actions" class="form-label">Acciones</label>
                    <textarea name="actions" id="actions" class="form-control" rows="5">{{ old('actions') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="priority" class="form-label">Prioridad</label>
                    <input type="number" name="priority" id="priority" class="form-control" value="{{ old('priority', 0) }}">
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="stop_processing" id="stop_processing" value="1" class="form-check-input" @checked(old('stop_processing', false))>
                        <label for="stop_processing" class="form-check-label">Detener procesamiento</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="valid_from" class="form-label">Válida desde</label>
                    <input type="date" name="valid_from" id="valid_from" class="form-control" value="{{ old('valid_from') }}">
                </div>

                <div class="mb-3">
                    <label for="valid_until" class="form-label">Válida hasta</label>
                    <input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until') }}">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                    <a href="{{ route('helpdesksocial.rules.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
