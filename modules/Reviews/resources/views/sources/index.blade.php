@extends('layouts.theme')
@section('title', 'Fichas de opiniones')
@section('page_header')
    @include('core::components.card', ['title' => 'Fichas de opiniones'])
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="card mb-4">
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h5 class="mb-1 fw-bold">Fichas de las que leemos opiniones</h5>
                <p class="small mb-0 text-muted">
                    Cada tienda física en Google. El sistema las lee una vez al día y lo nuevo entra en la
                    bandeja de moderación; se publica en la web cuando alguien lo aprueba.
                    Las credenciales se guardan cifradas y no vuelven a mostrarse.
                </p>
            </div>
            @can('reviews.settings')
                <div class="ms-auto ps-3 flex-shrink-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sourceModal" data-mode="create">
                        Registrar ficha
                    </button>
                </div>
            @endcan
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Ficha</th>
                    <th>Identificador</th>
                    <th class="text-center">Credenciales</th>
                    <th class="text-center">Opiniones</th>
                    <th>Última lectura</th>
                    <th class="text-center">Activa</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($sources as $source)
                <tr>
                    <td class="ps-4">
                        <a class="fw-semibold" href="{{ route('reviews.sources.show', $source) }}">{{ $source->name }}</a>
                        <div class="small text-muted">{{ ucfirst($source->platform) }}</div>
                        @if($source->last_error)
                            <div class="small text-muted">Último error: {{ Str::limit($source->last_error, 70) }}</div>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $source->external_id ?: '—' }}</td>
                    <td class="text-center">
                        @if($source->isConfigured())
                            <span class="badge bg-success">Completas</span>
                        @else
                            <span class="badge bg-warning text-dark">Faltan datos</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('reviews.sources.show', $source) }}">{{ number_format($source->reviews_count) }}</a>
                    </td>
                    <td class="small">
                        {{ optional($source->last_fetch_at)->format('d/m/Y H:i') ?? 'Nunca' }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $source->active ? 'bg-success' : 'bg-light text-dark' }}">
                            {{ $source->active ? 'Sí' : 'No' }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        @can('reviews.settings')
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false" aria-label="Acciones">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('reviews.sources.show', $source) }}">Ver sus reseñas</a></li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#sourceModal"
                                                data-mode="edit"
                                                data-action="{{ route('reviews.sources.update', $source) }}"
                                                data-name="{{ $source->name }}"
                                                data-external="{{ $source->external_id }}"
                                                data-account="{{ $source->account_id }}"
                                                data-active="{{ $source->active ? 1 : 0 }}"
                                                data-auto="{{ $source->auto_approve ? 1 : 0 }}">
                                            Editar
                                        </button>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('reviews.sources.test', $source) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Probar conexión</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('reviews.sources.fetch', $source) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Leer ahora</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('reviews.sources.destroy', $source) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item">Eliminar</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        Todavía no hay ninguna ficha registrada.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Cómo obtener las credenciales</h5>
        <p class="small mb-0 text-muted">Las reseñas de un negocio solo puede leerlas su propietario</p>
    </div>
    <div class="card-body p-4">
        <ol class="small text-muted mb-0">
            <li>En Google Cloud, crear un proyecto y habilitar <strong>Google Business Profile API</strong>. El acceso hay que solicitarlo a Google y tarda unos días en concederse.</li>
            <li>Crear credenciales de <strong>ID de cliente de OAuth</strong> (tipo aplicación de escritorio o web).</li>
            <li>Autorizar una vez con la cuenta que administra las fichas, con el permiso <code>https://www.googleapis.com/auth/business.manage</code>, y guardar el <strong>token de actualización</strong> que devuelve.</li>
            <li>El identificador de la ficha es <code>locations/…</code> y el de la cuenta <code>accounts/…</code>, ambos visibles en la propia API.</li>
        </ol>
    </div>
</div>

<div class="modal fade" id="sourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" id="sourceForm" action="{{ route('reviews.sources.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sourceModalTitle">Registrar ficha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="sourceName">Nombre de la tienda</label>
                            <input type="text" class="form-control" id="sourceName" name="name" required
                                   placeholder="Álvarez Capitán Haya">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sourceExternal">Identificador de la ficha</label>
                            <input type="text" class="form-control" id="sourceExternal" name="external_id"
                                   placeholder="locations/1234567890">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sourceAccount">Identificador de la cuenta</label>
                            <input type="text" class="form-control" id="sourceAccount" name="account_id"
                                   placeholder="accounts/1234567890">
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold mb-1">Credenciales</h6>
                            <p class="small text-muted mb-0">
                                Se guardan cifradas y no vuelven a mostrarse. Al editar, deja en blanco lo que no quieras cambiar.
                            </p>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sourceClientId">ID de cliente</label>
                            <input type="text" class="form-control" id="sourceClientId" name="client_id" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sourceClientSecret">Secreto de cliente</label>
                            <input type="password" class="form-control" id="sourceClientSecret" name="client_secret" autocomplete="new-password">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="sourceRefresh">Token de actualización</label>
                            <input type="password" class="form-control" id="sourceRefresh" name="refresh_token" autocomplete="new-password">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sourceActive">Leer a diario</label>
                            <select class="form-select" id="sourceActive" name="active">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="sourceAuto">Publicar sin revisar</label>
                            <select class="form-select" id="sourceAuto" name="auto_approve">
                                <option value="0">No, pasan por la bandeja</option>
                                <option value="1">Sí, se publican al llegar</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-block">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Guardar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('sourceModal');

    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var t = event.relatedTarget;
        var crear = t.getAttribute('data-mode') !== 'edit';
        var form = document.getElementById('sourceForm');

        document.getElementById('sourceModalTitle').textContent = crear ? 'Registrar ficha' : 'Editar ficha';
        form.action = crear ? '{{ route('reviews.sources.store') }}' : t.getAttribute('data-action');

        document.getElementById('sourceName').value = crear ? '' : (t.getAttribute('data-name') || '');
        document.getElementById('sourceExternal').value = crear ? '' : (t.getAttribute('data-external') || '');
        document.getElementById('sourceAccount').value = crear ? '' : (t.getAttribute('data-account') || '');
        document.getElementById('sourceActive').value = crear ? '1' : (t.getAttribute('data-active') || '1');
        document.getElementById('sourceAuto').value = crear ? '0' : (t.getAttribute('data-auto') || '0');

        // Las credenciales nunca se rellenan: no salen del servidor.
        ['sourceClientId', 'sourceClientSecret', 'sourceRefresh'].forEach(function (id) {
            document.getElementById(id).value = '';
        });
    });
});
</script>
@endpush

@endsection
