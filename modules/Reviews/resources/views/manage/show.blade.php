@extends('layouts.theme')
@section('title', 'Opinión · ' . ($review->product_name ?? '#' . $review->id))
@section('page_header')
    @include('core::components.card', ['title' => 'Gestionar opinión'])
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/reviews/css/reviews.css') }}?v={{ @filemtime(public_path('modules/reviews/css/reviews.css')) }}">
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
        'pending' => ['Pendiente de revisión', 'bg-light text-dark'],
        'approved' => ['Publicada', 'bg-success'],
        'rejected' => ['Retirada', 'bg-secondary'],
    ];
    [$estadoTexto, $estadoClase] = $estados[$review->status] ?? ['—', 'bg-light text-dark'];
    $aprobadas = $review->translations->where('reviewed', true)->count();
@endphp

<div class="row g-4">

    {{-- Columna de acciones --}}
    <div class="col-12 col-lg-4">

        <div class="card mb-4">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Moderación</h5>
                <p class="small mb-0 text-muted">
                    @if($review->isFromGoogle())
                        Decide si la mostramos en nuestra web. En Google seguirá publicada
                    @else
                        Lo que se decida aquí se escribe en la tienda
                    @endif
                </p>
            </div>
            <div class="card-body p-4 d-flex flex-column gap-3">

                <div>
                    <span class="badge {{ $estadoClase }}">{{ $estadoTexto }}</span>
                    @if($review->isFromGoogle())
                        <span class="badge bg-light text-muted border">Google · {{ optional($review->source)->name }}</span>
                    @elseif($review->entity === 'store')
                        <span class="badge bg-light text-muted border">Sobre la tienda</span>
                    @endif
                    @if(! $review->isFromGoogle() && $review->hasConflict())
                        <span class="badge bg-warning text-dark">Discrepa con la tienda</span>
                    @endif
                </div>

                @if($review->moderated_at)
                    <p class="small text-muted mb-0">
                        {{ $review->status === 'approved' ? 'Publicada' : 'Retirada' }} por
                        <strong>{{ $review->moderator_name ?? 'un compañero' }}</strong>
                        el {{ $review->moderated_at->format('d/m/Y \a \l\a\s H:i') }}.
                        @if($review->rejection_reason)
                            <br>Motivo: {{ $review->rejection_reason }}
                        @endif
                    </p>
                @endif

                @if($review->screening)
                    @php
                        $limpia = $review->screening === 'clean';
                    @endphp
                    <div class="alert {{ $limpia ? 'alert-success' : 'alert-warning' }} py-2 px-3 mb-0">
                        <strong>{{ $screeningLabels[$review->screening] ?? $review->screening }}</strong>
                        @if($review->screening_confidence)
                            <span class="small">({{ $review->screening_confidence }}% de confianza)</span>
                        @endif
                        @if($review->screening_reason)
                            <div class="small">{{ $review->screening_reason }}</div>
                        @endif
                        <div class="small text-muted">Revisión asistida · la decisión sigue siendo tuya</div>
                    </div>
                @elseif($screeningEnabled)
                    @can('reviews.moderate')
                        <form method="POST" action="{{ route('reviews.screen', $review) }}">
                            @csrf
                            <button type="submit" class="btn btn-light w-100">Revisar con IA</button>
                        </form>
                    @endcan
                @endif

                @can('reviews.moderate')
                    @if($review->status !== 'approved')
                        <form method="POST" action="{{ route('reviews.approve', $review) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                {{ $review->isFromGoogle() ? 'Mostrar en nuestra web' : 'Publicar en la tienda' }}
                            </button>
                        </form>
                    @endif

                    @if($review->status !== 'rejected')
                        <button type="button" class="btn btn-outline-primary w-100"
                                data-bs-toggle="modal" data-bs-target="#rejectModal">
                            {{ $review->isFromGoogle() ? 'Ocultar en nuestra web' : 'Retirar de la tienda' }}
                        </button>
                    @endif

                    <button type="button" class="btn btn-outline-primary w-100"
                            data-bs-toggle="modal" data-bs-target="#answerModal">
                        @if($review->isFromGoogle())
                            {{ $review->answer ? 'Editar respuesta en Google' : 'Responder en Google' }}
                        @else
                            {{ $review->answer ? 'Editar respuesta' : 'Responder públicamente' }}
                        @endif
                    </button>
                @endcan
            </div>
        </div>

        @if($review->isEditable())
        <div class="card mb-4">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Traducción</h5>
                <p class="small mb-0 text-muted">Siempre desde el {{ strtoupper($review->lang_iso) }}, nunca de otra traducción</p>
            </div>
            <div class="card-body p-4 d-flex flex-column gap-3">

                @if(! $translatorAvailable)
                    <p class="small text-muted mb-0">El servicio de traducción no está disponible en este entorno.</p>
                @else
                    @php
                        $heredadas = $review->translations->where('inherited', true)->count();
                        $faltan = count($rows) - $review->translations->count();
                    @endphp
                    <p class="small text-muted mb-0">
                        {{ $review->translations->count() }} de {{ count($rows) }} idiomas traducidos.
                        @if($faltan) <br><strong>Faltan {{ $faltan }}.</strong> @endif
                        @if($heredadas)
                            <br>{{ $heredadas }} vienen de la tienda y nadie las ha revisado.
                        @endif
                        @if($aprobadas)
                            <br>{{ $aprobadas }} aprobadas para publicar.
                        @endif
                        @if($review->translated_at)
                            <br>Traducidas aquí: {{ $review->translated_at->format('d/m/Y H:i') }}.
                        @endif
                    </p>

                    @can('reviews.moderate')
                        <form method="POST" action="{{ route('reviews.translate', $review) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                {{ $review->translations->count() ? 'Volver a traducir lo que falte' : 'Traducir a los demás idiomas' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('reviews.translations.pull', $review) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">
                                Volver a consultar la tienda
                            </button>
                        </form>

                        <form method="POST" action="{{ route('reviews.translations.publish', $review) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100" @disabled($aprobadas === 0)>
                                Publicar las {{ $aprobadas }} aprobadas
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        @endif

        <div class="card">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Historial</h5>
                <p class="small mb-0 text-muted">Qué se hizo, cuándo y desde dónde</p>
            </div>
            @if($review->events->isEmpty())
                <div class="card-body p-4 text-center">
                    <p class="text-muted small mb-0"><strong>Sin movimientos</strong><br>Todavía no se ha hecho nada con esta opinión.</p>
                </div>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($review->events as $event)
                        <li class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold">{{ $event->label }}</span>
                                <span class="small text-muted">{{ optional($event->created_at)->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="small text-muted">
                                {{ $event->source_label }}
                                @if($event->actor_name)
                                    · {{ $event->actor_name }}
                                @endif
                                @if($event->detail)
                                    <br>{{ $event->detail }}
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Columna de información --}}
    <div class="col-12 col-lg-8">

        <div class="card mb-4">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">La opinión</h5>
                <p class="small mb-0 text-muted">Tal y como la escribió el cliente</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Valoración</label>
                        <div class="form-control bg-light">{{ $review->rating }} / 5 <span class="text-muted small">({{ $review->stars }}/10 en la tienda)</span></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Idioma original</label>
                        <div class="form-control bg-light">{{ strtoupper($review->lang_iso) }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Título</label>
                        <div class="form-control bg-light">{{ $review->title ?: '—' }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Texto</label>
                        <div class="form-control bg-light reviews-longtext">{!! nl2br(e(strip_tags($review->comment ?? ''))) !!}</div>
                    </div>
                    @if($review->answer)
                        <div class="col-12">
                            <label class="form-label">Respuesta de la tienda</label>
                            <div class="form-control bg-light reviews-longtext">{!! nl2br(e(strip_tags($review->answer))) !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">De dónde viene</h5>
                <p class="small mb-0 text-muted">
                    {{ $review->isFromGoogle() ? 'Tienda y autor de la reseña' : 'Producto, cliente y pedido' }}
                </p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @if($review->isFromGoogle())
                        <div class="col-12">
                            <label class="form-label">Tienda</label>
                            <div class="form-control bg-light">{{ optional($review->source)->name ?: $review->product_name ?: '—' }}</div>
                        </div>
                    @else
                        <div class="col-12 col-md-8">
                            <label class="form-label">{{ $review->entity === 'store' ? 'Sobre' : 'Producto' }}</label>
                            <div class="form-control bg-light">{{ $review->product_name ?: ($review->entity === 'store' ? 'La tienda' : '—') }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Referencia</label>
                            <div class="form-control bg-light">{{ $review->product_reference ?: '—' }}</div>
                        </div>
                    @endif
                    <div class="col-12 col-md-6">
                        <label class="form-label">Cliente</label>
                        <div class="form-control bg-light">{{ $review->author ?: 'Anónimo' }}</div>
                    </div>
                    @unless($review->isFromGoogle())
                        <div class="col-12 col-md-6">
                            <label class="form-label">Correo</label>
                            <div class="form-control bg-light">{{ $review->customer_email ?: '—' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Pedido</label>
                            <div class="form-control bg-light">{{ $review->order_reference ?: '—' }}</div>
                        </div>
                    @endunless
                    <div class="col-12 col-md-6">
                        <label class="form-label">Fecha</label>
                        <div class="form-control bg-light">{{ optional($review->ps_date)->format('d/m/Y H:i') ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($review->isEditable())
        <div class="card">
            <div class="card-header p-3 bg-white border-bottom">
                <h5 class="mb-1 fw-bold">Traducciones</h5>
                <p class="small mb-0 text-muted">
                    Una por idioma. Se publican solo las aprobadas.
                    Las marcadas <strong>Ya en la tienda</strong> las dejó el proceso anterior, que traducía unas traducciones de otras: se ven en la web, pero conviene revisarlas o rehacerlas.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Idioma</th>
                            <th>Texto</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php $t = $row['translation']; @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">{{ strtoupper($row['iso']) }}</td>
                                <td>
                                    @if($t)
                                        <div class="small">{{ Str::limit(strip_tags($t->comment ?? ''), 140) }}</div>
                                        <div class="small text-muted">
                                            @if($t->inherited)
                                                Ya estaba en la tienda · sin revisar
                                            @else
                                                {{ $t->provider ? 'Traducida con '.$t->provider : 'Traducida aquí' }}
                                            @endif
                                            @if($t->title) · Título: {{ Str::limit($t->title, 40) }} @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Sin traducir</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(! $t)
                                        <span class="badge bg-light text-dark">Sin traducir</span>
                                    @else
                                        @switch($t->state)
                                            @case('published')
                                                <span class="badge bg-success">Publicada</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-primary">Aprobada</span>
                                                @break
                                            @case('inherited')
                                                {{-- Estado heredado, no una alerta: no debe pesar más que
                                                     "Sin revisar", que es lo que sí pide acción. --}}
                                                <span class="badge bg-light text-muted border">Ya en la tienda</span>
                                                @break
                                            @default
                                                <span class="badge bg-warning text-dark">Sin revisar</span>
                                        @endswitch
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($t)
                                        @can('reviews.moderate')
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"
                                                        aria-expanded="false" aria-label="Acciones">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <form method="POST" action="{{ route('reviews.translations.approve', [$review, $t]) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                {{ $t->reviewed ? 'Quitar aprobación' : 'Aprobar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                                data-bs-target="#editTranslationModal"
                                                                data-action="{{ route('reviews.translations.update', [$review, $t]) }}"
                                                                data-lang="{{ strtoupper($t->lang_iso) }}"
                                                                data-title="{{ $t->title }}"
                                                                data-comment="{{ $t->comment }}"
                                                                data-answer="{{ $t->answer }}">
                                                                Corregir
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

@include('reviews::manage._modals')

@endsection
