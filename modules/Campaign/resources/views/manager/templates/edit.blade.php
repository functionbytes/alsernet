@extends('layouts.theme')

@section('title', 'Editar plantilla')

@section('content')
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>{{ $template->name }}</h2>
            <a href="{{ route('manager.templates.copy', $template->uid) }}" class="btn btn-outline-secondary btn-sm">Duplicar</a>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="post" action="{{ route('manager.templates.update', $template->uid) }}">
            @csrf @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="name" value="{{ old('name', $template->name) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Asunto</label>
                    <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Layout</label>
                    <select name="layout_id" class="form-select">
                        <option value="">— sin layout —</option>
                        @foreach ($layouts as $l)<option value="{{ $l->id }}" @selected($template->layout_id == $l->id)>{{ $l->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6 form-check pt-4">
                    <input type="checkbox" name="shared" value="1" id="shared" class="form-check-input" @checked($template->shared)>
                    <label for="shared" class="form-check-label">Compartida (visible para otros usuarios)</label>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <label class="form-label d-flex justify-content-between">
                        <span>HTML</span>
                        <button type="button" class="btn btn-sm btn-link" onclick="updatePreview()">Refrescar preview →</button>
                    </label>
                    <textarea name="html" id="html-editor" rows="30" class="form-control font-monospace" style="font-size:.85rem">{{ old('html', $template->html ?? $template->content) }}</textarea>
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Preview</label>
                    <iframe id="preview" srcdoc="{{ $template->html ?? $template->content }}" style="width:100%;height:600px;border:1px solid #ddd;border-radius:.25rem"></iframe>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Plain text (alternativo, autogenera del HTML al guardar si está vacío)</label>
                <textarea name="plain" rows="6" class="form-control font-monospace" style="font-size:.85rem">{{ old('plain', $template->plain) }}</textarea>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Guardar plantilla</button>
                <a href="{{ route('manager.templates.index') }}" class="btn btn-link">Cancelar</a>
            </div>
        </form>

    <script>
        function updatePreview() {
            const html = document.getElementById('html-editor').value;
            const iframe = document.getElementById('preview');
            iframe.srcdoc = html;
        }
        // Preview live con debounce
        let timeout;
        document.getElementById('html-editor').addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(updatePreview, 800);
        });
    </script>
@endsection
