@extends('layouts.theme')
@section('title', 'Consultas')
@section('page_header')
    @include('core::components.card', ['title' => 'Consultas'])
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<form method="POST" action="{{ route('questions.bulk') }}" id="bulkForm">
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
        <h5 class="mb-1 fw-bold">Consultas de producto</h5>
        <p class="small mb-0 text-muted">
            Llegan del módulo <code>alsernetquestions</code> de la tienda.
            <strong>Una consulta no se publica sin respuesta</strong>: la pregunta sola no informa a nadie.
            Lo que se decide aquí se escribe en PrestaShop.
        </p>
    </div>

    <div class="card-body border-bottom">
        <ul class="nav nav-pills user-profile-tab gap-2">
            @foreach ([
                'unanswered' => 'Sin responder',
                'answered_pending' => 'Respondidas sin publicar',
                'approved' => 'Publicadas',
                'rejected' => 'Retiradas',
                'conflict' => 'Discrepancias',
                'all' => 'Todas',
            ] as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                       href="{{ route('questions.index', array_filter(['status' => $key, 'q' => $search])) }}">
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
            <div class="col-12 col-md-6">
                <label class="form-label" for="q">Buscar</label>
                <input type="search" class="form-control" id="q" name="q" value="{{ $search }}"
                       placeholder="Pregunta, respuesta, producto o cliente">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="lang">Idioma</label>
                <select class="form-select" id="lang" name="lang">
                    <option value="">Todos</option>
                    @foreach ($languages as $iso)
                        <option value="{{ $iso }}" @selected(request('lang') === $iso)>{{ strtoupper($iso) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('questions.index') }}" class="btn btn-light">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4"><input type="checkbox" class="form-check-input" id="bulkAll"></th>
                    <th>Consulta</th>
                    <th>Producto</th>
                    <th class="text-center">Idioma</th>
                    <th>Fecha</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($questions as $question)
                <tr>
                    <td class="ps-4">
                        <input type="checkbox" class="form-check-input bulk-check" form="bulkForm"
                               name="ids[]" value="{{ $question->id }}">
                    </td>
                    <td>
                        <a class="fw-semibold" href="{{ route('questions.show', $question) }}">
                            {{ Str::limit(strip_tags($question->question), 90) }}
                        </a>
                        <div class="small text-muted">{{ $question->client_name ?: 'Anónimo' }}</div>
                        @if($question->isAnswered())
                            <div class="small text-muted"><strong>Respuesta:</strong> {{ Str::limit(strip_tags($question->answer), 80) }}</div>
                        @else
                            <div class="small"><span class="badge bg-warning text-dark">Sin responder</span></div>
                        @endif
                    </td>
                    <td>
                        <div>{{ Str::limit($question->product_name ?? '—', 34) }}</div>
                        @if($question->product_reference)
                            <div class="small text-muted">{{ $question->product_reference }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ strtoupper($question->lang_iso) }}</td>
                    <td class="small">{{ optional($question->ps_date)->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-center">
                        @if($question->hasConflict())
                            <span class="badge bg-warning text-dark">Discrepa</span>
                        @elseif($question->status === 'approved')
                            <span class="badge bg-success">Publicada</span>
                        @elseif($question->status === 'rejected')
                            <span class="badge bg-secondary">Retirada</span>
                        @else
                            <span class="badge bg-light text-dark">Pendiente</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <a href="{{ route('questions.show', $question) }}" class="btn btn-sm btn-light">
                            {{ $question->isAnswered() ? 'Abrir' : 'Responder' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">No hay consultas con estos filtros.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($questions->hasPages())
        <div class="card-body border-top">{{ $questions->links() }}</div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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
            document.getElementById('bulkForm').submit();
        });
    });
});
</script>
@endpush

@endsection
