@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Crear plantilla social</h5>
        </div>
        <div class="card-body">
            <form action="/api/helpdesk/social/templates" method="POST">
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
                    <label for="body" class="form-label">Cuerpo</label>
                    <textarea name="body" id="body" class="form-control" rows="5" required>{{ old('body') }}</textarea>
                    <div class="form-text">Usa {{variable}} para placeholders.</div>
                </div>

                <div class="mb-3">
                    <label for="variables" class="form-label">Variables</label>
                    <textarea name="variables" id="variables" class="form-control" rows="3">{{ old('variables') }}</textarea>
                    <div class="form-text">Array JSON con los nombres de las variables disponibles.</div>
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Categoría</label>
                    <select name="category" id="category" class="form-select" required>
                        <option value="" disabled selected>Seleccionar categoría</option>
                        <option value="greeting" @selected(old('category') == 'greeting')>Saludo</option>
                        <option value="support" @selected(old('category') == 'support')>Soporte</option>
                        <option value="sales" @selected(old('category') == 'sales')>Ventas</option>
                        <option value="feedback" @selected(old('category') == 'feedback')>Retroalimentación</option>
                    </select>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" @checked(old('is_active', true))>
                        <label for="is_active" class="form-check-label">Activa</label>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_default" id="is_default" value="1" class="form-check-input" @checked(old('is_default', false))>
                        <label for="is_default" class="form-check-label">Por defecto</label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                    <a href="{{ route('helpdesksocial.templates.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
