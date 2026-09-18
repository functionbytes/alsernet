@extends('layouts.theme')
@section('title', 'Consulta · ' . ($question->product_name ?? '#' . $question->id))
@section('page_header')
    @include('core::components.card', ['title' => 'Gestionar consulta'])
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/questions/css/questions.css') }}?v={{ @filemtime(public_path('modules/questions/css/questions.css')) }}">
@endpush

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

@php
    $estados = [
        'pending' => ['Pendiente', 'bg-light text-dark'],
        'approved' => ['Publicada', 'bg-success'],
        'rejected' => ['Retirada', 'bg-secondary'],
    ];
    [$estadoTexto, $estadoClase] = $estados[$question->status] ?? ['—', 'bg-light text-dark'];
@endphp

<div class="row g-4">

    <div class="col-12 col-lg-4">

        <div class="card mb-4">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Estado</h5>
                <p class="small mb-0 text-muted">Sin respuesta no se puede publicar</p>
            </div>
            <div class="card-body p-4 d-flex flex-column gap-3">
                <div>
                    <span class="badge {{ $estadoClase }}">{{ $estadoTexto }}</span>
                    @if(! $question->isAnswered())
                        <span class="badge bg-warning text-dark">Sin responder</span>
                    @endif
                    @if($question->hasConflict())
                        <span class="badge bg-warning text-dark">Discrepa con la tienda</span>
                    @endif
                </div>

                @if($question->moderated_at)
                    <p class="small text-muted mb-0">
                        {{ $question->status === 'approved' ? 'Publicada' : 'Retirada' }} por
                        <strong>{{ $question->moderator_name ?? 'un compañero' }}</strong>
                        el {{ $question->moderated_at->format('d/m/Y \a \l\a\s H:i') }}.
                        @if($question->rejection_reason)<br>Motivo: {{ $question->rejection_reason }}@endif
                    </p>
                @endif

                @can('questions.moderate')
                    @if($question->status !== 'approved')
                        <form method="POST" action="{{ route('questions.approve', $question) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $question->isAnswered())>
                                Publicar en la ficha
                            </button>
                        </form>
                        @unless($question->isAnswered())
                            <p class="small text-muted mb-0">Respóndela antes de publicarla.</p>
                        @endunless
                    @endif

                    @if($question->status !== 'rejected')
                        <button type="button" class="btn btn-outline-primary w-100"
                                data-bs-toggle="modal" data-bs-target="#rejectModal">
                            Retirar
                        </button>
                    @endif
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Historial</h5>
                <p class="small mb-0 text-muted">Qué se hizo, cuándo y desde dónde</p>
            </div>
            @if($question->events->isEmpty())
                <div class="card-body p-4 text-center">
                    <p class="text-muted small mb-0"><strong>Sin movimientos</strong></p>
                </div>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($question->events as $event)
                        <li class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold">{{ $event->label }}</span>
                                <span class="small text-muted">{{ optional($event->created_at)->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="small text-muted">
                                {{ $event->source_label }}
                                @if($event->actor_name) · {{ $event->actor_name }} @endif
                                @if($event->detail)<br>{{ $event->detail }}@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="col-12 col-lg-8">

        <div class="card mb-4">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">La consulta</h5>
                <p class="small mb-0 text-muted">Lo que preguntó el cliente</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Pregunta</label>
                        <div class="form-control bg-light questions-longtext">{!! nl2br(e(strip_tags($question->question))) !!}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Cliente</label>
                        <div class="form-control bg-light">{{ $question->client_name ?: 'Anónimo' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Correo</label>
                        <div class="form-control bg-light">{{ $question->client_email ?: '—' }}</div>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Producto</label>
                        <div class="form-control bg-light">{{ $question->product_name ?: '—' }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Fecha</label>
                        <div class="form-control bg-light">{{ optional($question->ps_date)->format('d/m/Y H:i') ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Responder es la acción principal: sin esto la consulta no sirve --}}
        <div class="card mb-4">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Respuesta</h5>
                <p class="small mb-0 text-muted">Se publica bajo la pregunta, en la ficha del producto</p>
            </div>
            <div class="card-body p-4">
                @can('questions.moderate')
                    <form method="POST" action="{{ route('questions.answer', $question) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="answer">Tu respuesta</label>
                            <textarea class="form-control" id="answer" name="answer" rows="5" maxlength="4000" required>{{ $question->answer }}</textarea>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-light">Guardar</button>
                            <button type="submit" name="publish" value="1" class="btn btn-primary">Guardar y publicar</button>
                        </div>
                    </form>
                @else
                    <div class="form-control bg-light questions-longtext">{!! nl2br(e(strip_tags($question->answer ?? '—'))) !!}</div>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Traducciones</h5>
                <p class="small mb-0 text-muted">
                    Una por idioma. Las de la tienda ya venían así, en su propia tabla.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Idioma</th>
                            <th>Pregunta traducida</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php $t = $row['translation']; @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">{{ strtoupper($row['iso']) }}</td>
                                <td>
                                    @if($t)
                                        <div class="small">{{ Str::limit(strip_tags($t->question ?? ''), 120) }}</div>
                                        @if($t->answer)
                                            <div class="small text-muted">Respuesta: {{ Str::limit(strip_tags($t->answer), 90) }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted small">Sin traducir</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(! $t)
                                        <span class="badge bg-light text-dark">Sin traducir</span>
                                    @elseif($t->state === 'inherited')
                                        <span class="badge bg-light text-muted border">Ya en la tienda</span>
                                    @elseif($t->state === 'published')
                                        <span class="badge bg-success">Publicada</span>
                                    @elseif($t->state === 'approved')
                                        <span class="badge bg-primary">Aprobada</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Sin revisar</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('questions.reject', $question) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Retirar la consulta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Dejará de verse en la ficha del producto.</p>
                    <label class="form-label" for="rejectReason">Motivo</label>
                    <input type="text" class="form-control" id="rejectReason" name="reason" maxlength="255"
                           placeholder="Opcional: por qué se retira">
                </div>
                <div class="modal-footer d-block">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Retirar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
