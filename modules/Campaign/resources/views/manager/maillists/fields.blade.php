@extends('theme::layouts.manager')

@section('title', 'Campos de '.$list->name)

@section('content')
<div class="container py-4" style="max-width:980px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Mapping de campos</h2>
            <p class="text-muted mb-0">Lista: <strong>{{ $list->name }}</strong> · Cada campo define una variable usable en el email builder.</p>
        </div>
        <a href="{{ route('manager.campaigns.maillists.show', $list->uid) }}" class="btn btn-outline-secondary">← Volver a lista</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="alert alert-info">
        <strong>💡 Cómo funciona:</strong> Cada campo aquí genera una variable
        <code>{{ '{{TAG}}' }}</code> que el email builder ofrecerá insertar. Al enviar,
        cada destinatario verá su valor real.
        Ejemplo: con un campo <code>BIRTHDAY</code>, escribir
        <code>{{ '"Te deseamos un feliz {{BIRTHDAY}}"' }}</code> en una plantilla
        muestra a cada suscriptor su fecha de cumpleaños.
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Añadir campo nuevo</strong></div>
        <div class="card-body">
            <form method="post" action="{{ route('manager.campaigns.maillists.fields.store', $list->uid) }}" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small text-muted">Tag (UPPER_SNAKE)</label>
                    <input type="text" name="tag" class="form-control font-monospace" placeholder="BIRTHDAY" required style="text-transform:uppercase">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Etiqueta visible</label>
                    <input type="text" name="label" class="form-control" placeholder="Cumpleaños" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="text">Texto</option>
                        <option value="email">Email</option>
                        <option value="number">Número</option>
                        <option value="date">Fecha</option>
                        <option value="phone">Teléfono</option>
                        <option value="url">URL</option>
                        <option value="select">Select</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Valor por defecto</label>
                    <input type="text" name="default_value" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">+ Añadir</button>
                </div>
                <div class="col-md-12 d-flex gap-3 mt-2">
                    <div class="form-check"><input type="checkbox" name="required" value="1" id="req" class="form-check-input"><label for="req" class="form-check-label small">Obligatorio en formularios</label></div>
                    <div class="form-check"><input type="checkbox" name="visible" value="1" id="vis" class="form-check-input" checked><label for="vis" class="form-check-label small">Visible en formulario público</label></div>
                </div>
            </form>
        </div>
    </div>

    <h5>Campos existentes ({{ $fields->count() }})</h5>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="20%">Variable</th>
                    <th>Etiqueta</th>
                    <th>Tipo</th>
                    <th>Default</th>
                    <th>Obligatorio</th>
                    <th>Visible</th>
                    <th>Orden</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fields as $f)
                    @php $isSystem = in_array($f->tag, ['EMAIL', 'FIRST_NAME', 'LAST_NAME'], true); @endphp
                    <tr>
                        <td><code class="bg-light px-2 py-1 rounded">{{ '{{'.$f->tag.'}}' }}</code></td>
                        <td>{{ $f->label }}</td>
                        <td><small class="text-muted">{{ $f->type }}</small></td>
                        <td><small>{{ $f->default_value ?: '—' }}</small></td>
                        <td>{{ $f->required ? '✓' : '' }}</td>
                        <td>{{ $f->visible ? '✓' : '' }}</td>
                        <td>{{ $f->order }}</td>
                        <td>
                            @unless ($isSystem)
                                <form method="post" action="{{ route('manager.campaigns.maillists.fields.destroy', [$list->uid, $f->id]) }}" class="d-inline" onsubmit="return confirm('¿Eliminar campo {{ $f->tag }}?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-link btn-sm text-danger p-0">Eliminar</button>
                                </form>
                            @else
                                <small class="text-muted">sistema</small>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay campos. Añade el primero arriba.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 small text-muted">
        <strong>Variables del sistema</strong> (siempre disponibles, sin gestión aquí):
        <code>{{ '{{EMAIL}}' }}</code>,
        <code>{{ '{{NAME}}' }}</code>,
        <code>{{ '{{UNSUBSCRIBE_URL}}' }}</code>,
        <code>{{ '{{MANAGE_URL}}' }}</code>,
        <code>{{ '{{WEB_VIEW_URL}}' }}</code>,
        <code>{{ '{{COMPANY_ADDRESS}}' }}</code>.
    </div>
</div>
@endsection
