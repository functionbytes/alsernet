@extends('layouts.theme')
@section('title', 'Ajustes de opiniones')
@section('page_header')
    @include('core::components.card', ['title' => 'Ajustes de opiniones'])
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('reviews.settings.update') }}">
    @csrf

    <div class="card mb-4">
        <div class="card-header p-4 border-bottom border-light">
            <h5 class="mb-1 fw-bold">Revisión asistida</h5>
            <p class="small mb-0 text-muted">
                Marca lo que parece problemático para que quien modera empiece por ahí, en vez de por la primera de la lista.
                <strong>No aprueba ni rechaza nada</strong>: la decisión sigue siendo de una persona.
            </p>
        </div>

        <div class="card-body p-4">
            @unless($aiReady)
                <div class="alert alert-warning">
                    El servicio de IA no está configurado, así que la revisión no se ejecutará aunque se active aquí.
                </div>
            @endunless

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="enabled">Revisión asistida</label>
                    <select class="form-select" id="enabled" name="enabled">
                        <option value="1" @selected($enabled)>Activada</option>
                        <option value="0" @selected(! $enabled)>Desactivada</option>
                    </select>
                    <small class="text-muted">Desactivada, todo funciona igual: solo deja de marcarse nada.</small>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label" for="auto_reject">Al detectar spam u ofensas</label>
                    <select class="form-select" id="auto_reject" name="auto_reject">
                        <option value="0" @selected(! $autoReject)>Solo marcarlas, decide una persona</option>
                        <option value="1" @selected($autoReject)>Retirarlas directamente</option>
                    </select>
                    <small class="text-muted">Retirar en automático puede tumbar una opinión legítima; se puede deshacer.</small>
                </div>

                <div class="col-12">
                    <label class="form-label">Qué se marca</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($labels as $clave => $etiqueta)
                            @continue($clave === 'clean' || $clave === 'unclear')
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="flags[]"
                                       value="{{ $clave }}" id="flag_{{ $clave }}" @checked(in_array($clave, $flags, true))>
                                <label class="form-check-label" for="flag_{{ $clave }}">{{ $etiqueta }}</label>
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted">
                        Una opinión muy negativa pero legítima nunca se marca: criticar el producto no es un problema.
                    </small>
                </div>
            </div>
        </div>

        <div class="card-body border-top">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Cómo va</h5>
        <p class="small mb-0 text-muted">Estado de la cola de revisión</p>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            @foreach ([
                ['Pendientes de revisar', $stats['pendientes']],
                ['Ya cribadas', $stats['cribadas']],
                ['Marcadas con algún problema', $stats['marcadas']],
            ] as [$titulo, $valor])
                <div class="col-12 col-md-4">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">{{ $titulo }}</h6>
                            <h4 class="mb-0 fw-bold">{{ number_format($valor) }}</h4>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="small text-muted mt-3 mb-0">
            Para cribar la cola de una vez:
            <code>php artisan reviews:screen --limit=200</code>.
            Cada opinión se criba una sola vez; volver a lanzarlo solo mira las que faltan.
        </p>
    </div>
</div>

@endsection
