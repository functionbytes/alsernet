@extends('layouts.theme')
@section('title', 'Opiniones')
@section('page_header')
    @include('core::components.card', ['title' => 'Opiniones'])
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

{{-- Form vacío: los checkboxes de cada fila se asocian con form="bulkForm"
     para no anidar <form> dentro de <form>, que es HTML inválido. --}}
<form method="POST" action="{{ route('reviews.bulk') }}" id="bulkForm">
    @csrf
    <input type="hidden" name="action" id="bulkActionInput" value="">
</form>

<div id="bulkBar" class="alert alert-primary d-none align-items-center justify-content-between mb-3">
    <span><span id="bulkCount">0</span> seleccionada(s)</span>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-light" data-bulk-action="approve">Publicar</button>
        <button type="button" class="btn btn-sm btn-light" data-bulk-action="reject">Retirar</button>
    </div>
</div>

<div class="card mb-4">

    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h5 class="mb-1 fw-bold">Bandeja de moderación</h5>
                <p class="small mb-0 text-muted">
                    Opiniones de producto y de la tienda, más las reseñas que se leen a diario de las fichas de Google.
                    Lo que se decide aquí se escribe en PrestaShop; si alguien modera desde su back-office, aparece en <strong>Discrepancias</strong>.
                </p>
            </div>
        </div>
    </div>

    {{-- Procedencia. Mezclar en una sola lista las opiniones de producto, las
         de la tienda y las reseñas de Google haría imposible moderar: no se
         juzgan igual. --}}
    <div class="card-body border-bottom">
        <div class="d-flex gap-2 flex-wrap">
            @php
                $procedencias = [
                    ['label' => 'Todas', 'params' => [], 'n' => array_sum($byEntity), 'on' => ! $entity && ! $origin],
                    ['label' => 'De producto', 'params' => ['entity' => 'product'], 'n' => $byEntity['product'], 'on' => $entity === 'product' && ! $origin],
                    ['label' => 'De la tienda', 'params' => ['entity' => 'store', 'origin' => 'customer'], 'n' => $byEntity['store_customer'], 'on' => $entity === 'store' && $origin === 'customer'],
                    ['label' => 'De Google', 'params' => ['origin' => 'google'], 'n' => $byEntity['store_google'], 'on' => $origin === 'google'],
                ];
            @endphp
            @foreach($procedencias as $p)
                <a class="btn btn-sm {{ $p['on'] ? 'btn-primary' : 'btn-light' }}"
                   href="{{ route('reviews.index', array_merge($p['params'], array_filter(['status' => $status, 'q' => $search, 'source' => request('source')]))) }}">
                    {{ $p['label'] }} <span class="ms-1">{{ number_format($p['n']) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Estados. nav-pills.user-profile-tab, el estilo de pestaña secundaria
         del panel, en lugar de nav-tabs. --}}
    <div class="card-body border-bottom">
        <ul class="nav nav-pills user-profile-tab gap-2">
            @foreach ([
                'pending' => 'Pendientes',
                'approved' => 'Publicadas',
                'rejected' => 'Retiradas',
                'conflict' => 'Discrepancias',
                'all' => 'Todas',
            ] as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                       href="{{ route('reviews.index', array_filter(['status' => $key, 'q' => $search, 'entity' => $entity, 'origin' => $origin, 'source' => request('source')])) }}">
                        {{ $label }}
                        @if(isset($counts[$key]) && $counts[$key])
                            <span class="badge bg-light text-dark ms-1">{{ number_format($counts[$key]) }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card-body border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="entity" value="{{ $entity }}">
            <input type="hidden" name="origin" value="{{ $origin }}">
            <div class="col-12 col-md-5">
                <label class="form-label" for="q">Buscar</label>
                <input type="search" class="form-control" id="q" name="q" value="{{ $search }}"
                       placeholder="Producto, cliente, texto o referencia de pedido">
            </div>
            @if($sources->isNotEmpty())
                <div class="col-6 col-md-3">
                    <label class="form-label" for="source">Tienda</label>
                    <select class="form-select" id="source" name="source">
                        <option value="">Todas</option>
                        @foreach($sources as $s)
                            <option value="{{ $s->id }}" @selected((int) request('source') === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-2">
                <label class="form-label" for="lang">Idioma</label>
                <select class="form-select" id="lang" name="lang">
                    <option value="">Todos</option>
                    @foreach ($languages as $iso)
                        <option value="{{ $iso }}" @selected(request('lang') === $iso)>{{ strtoupper($iso) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="stars">Valoración</label>
                <select class="form-select" id="stars" name="stars">
                    <option value="">Todas</option>
                    @foreach ([10 => '5', 8 => '4', 6 => '3', 4 => '2', 2 => '1', 0 => '0'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('stars') !== null && request('stars') !== '' && (int) request('stars') === $value)>
                            {{ $label }} estrellas
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('reviews.index') }}" class="btn btn-light">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4"><input type="checkbox" class="form-check-input" id="bulkAll"></th>
                    <th>Opinión</th>
                    <th>Producto</th>
                    <th class="text-center">Valoración</th>
                    <th class="text-center">Idioma</th>
                    <th>Fecha</th>
                    <th class="text-center">En la tienda</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($reviews as $review)
                <tr>
                    <td class="ps-4">
                        <input type="checkbox" class="form-check-input bulk-check" form="bulkForm"
                               name="ids[]" value="{{ $review->id }}">
                    </td>
                    <td>
                        <a class="fw-semibold" href="{{ route('reviews.show', $review) }}">{{ $review->title ?: 'Ver la opinión' }}</a>
                        <div class="small text-muted">{{ Str::limit(strip_tags($review->comment ?? ''), 110) }}</div>
                        <div class="small text-muted">
                            {{ $review->author ?: 'Anónimo' }}
                            @if($review->order_reference)
                                · pedido {{ $review->order_reference }}
                            @endif
                            @if($review->isFromGoogle())
                                · <span class="badge bg-light text-dark">Google</span>
                                {{ optional($review->source)->name }}
                            @elseif($review->entity === 'store')
                                · <span class="badge bg-light text-dark">Sobre la tienda</span>
                            @endif
                        </div>
                        @if($review->answer)
                            <div class="small mt-1"><strong>Respuesta:</strong> {{ Str::limit(strip_tags($review->answer), 80) }}</div>
                        @endif
                        @if($review->screening && $review->screening !== 'clean')
                            <div class="small mt-1">
                                <span class="badge bg-warning text-dark">{{ \Modules\Reviews\Services\ReviewScreener::LABELS[$review->screening] ?? $review->screening }}</span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div>{{ Str::limit($review->product_name ?? '—', 40) }}</div>
                        @if($review->product_reference)
                            <div class="small text-muted">{{ $review->product_reference }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $review->rating }} / 5</td>
                    <td class="text-center">{{ strtoupper($review->lang_iso) }}</td>
                    <td>{{ optional($review->ps_date)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-center">
                        @if($review->hasConflict())
                            <span class="badge bg-warning text-dark">Discrepa</span>
                        @elseif($review->ps_active)
                            <span class="badge bg-success">Visible</span>
                        @else
                            <span class="badge bg-light text-dark">Oculta</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false" aria-label="Acciones">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('reviews.show', $review) }}">Abrir ficha</a></li>
                                @if($review->status !== 'approved')
                                    <li>
                                        <form method="POST" action="{{ route('reviews.approve', $review) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Publicar</button>
                                        </form>
                                    </li>
                                @endif
                                @if($review->status !== 'rejected')
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                data-bs-target="#rejectModal" data-review="{{ $review->id }}">
                                            Retirar
                                        </button>
                                    </li>
                                @endif
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                            data-bs-target="#answerModal" data-review="{{ $review->id }}"
                                            data-answer="{{ $review->answer }}">
                                        Responder
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        No hay opiniones que mostrar con estos filtros.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($reviews->hasPages())
        <div class="card-body border-top">{{ $reviews->links() }}</div>
    @endif
</div>

{{-- Retirar --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Retirar la opinión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Dejará de verse en la ficha. El motivo queda registrado en el historial.</p>
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

{{-- Responder --}}
<div class="modal fade" id="answerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="answerForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Responder públicamente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Se publica bajo la opinión, en la ficha del producto.</p>
                    <label class="form-label" for="answerText">Respuesta</label>
                    <textarea class="form-control" id="answerText" name="answer" rows="4" maxlength="2000" required></textarea>
                </div>
                <div class="modal-footer d-block">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Publicar respuesta</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var bulkForm = document.getElementById('bulkForm');
    var bulkBar = document.getElementById('bulkBar');
    var bulkCount = document.getElementById('bulkCount');
    var checks = document.querySelectorAll('.bulk-check');
    var all = document.getElementById('bulkAll');

    function refresh() {
        var n = document.querySelectorAll('.bulk-check:checked').length;
        bulkCount.textContent = n;
        bulkBar.classList.toggle('d-none', n === 0);
        bulkBar.classList.toggle('d-flex', n > 0);
    }

    checks.forEach(function (c) { c.addEventListener('change', refresh); });

    if (all) {
        all.addEventListener('change', function () {
            checks.forEach(function (c) { c.checked = all.checked; });
            refresh();
        });
    }

    document.querySelectorAll('[data-bulk-action]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('bulkActionInput').value = b.getAttribute('data-bulk-action');
            bulkForm.submit();
        });
    });

    var rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function (event) {
            var id = event.relatedTarget.getAttribute('data-review');
            document.getElementById('rejectForm').action = '{{ url('panel/reviews') }}/' + id + '/reject';
        });
    }

    var answerModal = document.getElementById('answerModal');
    if (answerModal) {
        answerModal.addEventListener('show.bs.modal', function (event) {
            var id = event.relatedTarget.getAttribute('data-review');
            document.getElementById('answerForm').action = '{{ url('panel/reviews') }}/' + id + '/answer';
            document.getElementById('answerText').value = event.relatedTarget.getAttribute('data-answer') || '';
        });
    }
});
</script>
@endpush

@endsection
