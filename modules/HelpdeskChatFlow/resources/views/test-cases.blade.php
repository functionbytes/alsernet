@extends('layouts.theme')

@section('title', 'Escenarios de prueba — ' . $chatFlow->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Escenarios de prueba'])
@endsection

@section('content')

    @include('core::components.alerts')

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('chatflow.index') }}">Chat flows</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chatflow.edit', $chatFlow) }}">{{ $chatFlow->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Escenarios de prueba</li>
        </ol>
    </nav>

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold mb-1">Escenarios de regresión</h6>
                <p class="small text-muted mb-0">Define conversaciones de prueba y re-ejecútalas cuando cambies el flow para detectar roturas.</p>
            </div>
            <div class="d-flex gap-2">
                <span id="run-summary" class="align-self-center small text-muted"></span>
                <button type="button" id="run-all-btn" class="btn btn-primary" {{ $testCases->isEmpty() ? 'disabled' : '' }}>
                    <i class="fas fa-play me-1"></i> Ejecutar todos
                </button>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#new-case-modal">
                    <i class="fas fa-plus me-1"></i> Nuevo escenario
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($testCases->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Escenario</th>
                                <th>Pasos</th>
                                <th>Resultado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($testCases as $case)
                                <tr data-case-id="{{ $case->id }}">
                                    <td class="fw-semibold">{{ $case->name }}</td>
                                    <td><span class="text-muted small">{{ count($case->steps ?? []) }} pasos</span></td>
                                    <td class="result-cell">
                                        @if($case->last_result === 'passed')
                                            <span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>Pasó</span>
                                        @elseif($case->last_result === 'failed')
                                            <span class="badge bg-danger-subtle text-danger"><i class="fas fa-xmark me-1"></i>Falló</span>
                                        @else
                                            <span class="badge bg-light text-muted border">Sin ejecutar</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-secondary run-one-btn" data-url="{{ route('chatflow.test-cases.run', [$chatFlow, $case]) }}">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <form action="{{ route('chatflow.test-cases.destroy', [$chatFlow, $case]) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar este escenario?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="detail-row d-none" data-detail-for="{{ $case->id }}">
                                    <td colspan="4" class="bg-light"><div class="detail-content small"></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-vial fa-3x mb-3 text-muted opacity-50"></i>
                    <h6 class="fw-bold mb-2">Sin escenarios de prueba</h6>
                    <p class="text-muted mb-0">Crea un escenario con los mensajes del cliente y lo que esperas que responda el bot.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- New scenario modal --}}
    <div class="modal fade" id="new-case-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form action="{{ route('chatflow.test-cases.store', $chatFlow) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-vial me-2 text-primary"></i>Nuevo escenario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre del escenario</label>
                            <input type="text" name="name" class="form-control" placeholder="ej: Cliente consulta estado de pedido" required>
                        </div>
                        <label class="form-label">Pasos (mensaje del cliente → texto esperado en la respuesta)</label>
                        <div id="steps-container"></div>
                        <button type="button" id="add-step" class="btn btn-sm btn-outline-primary mt-1">
                            <i class="fas fa-plus me-1"></i> Agregar paso
                        </button>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="submit" class="btn btn-primary w-100 mb-2">Guardar escenario</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    @if(session('success')) toastr.success(@json(session('success'))); @endif
    @if(session('error')) toastr.error(@json(session('error'))); @endif

    // Dynamic steps in the new-scenario form
    let stepIndex = 0;
    function addStep() {
        const i = stepIndex++;
        $('#steps-container').append(`
            <div class="input-group input-group-sm mb-1 step-row">
                <span class="input-group-text">${i + 1}</span>
                <input type="text" name="steps[${i}][input]" class="form-control" placeholder="Mensaje del cliente (ej: 3)" required>
                <input type="text" name="steps[${i}][expect_contains]" class="form-control" placeholder="Texto esperado en la respuesta">
                <button type="button" class="btn btn-outline-secondary remove-step"><i class="fas fa-times"></i></button>
            </div>`);
    }
    addStep();
    $('#add-step').on('click', addStep);
    $('#steps-container').on('click', '.remove-step', function () { $(this).closest('.step-row').remove(); });

    // Escape user/bot strings before interpolating into HTML (avoid self-XSS).
    function escHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function renderDetail(result) {
        if (result.error) return `<div class="text-danger">${escHtml(result.error)}</div>`;
        return result.steps.map((s, i) => `
            <div class="d-flex gap-2 py-1">
                <i class="fas fa-${s.matched ? 'check text-success' : 'xmark text-danger'} mt-1"></i>
                <div>
                    <div><strong>${i + 1}.</strong> Cliente: «${escHtml(s.input)}»${s.expect ? ` · espera: «${escHtml(s.expect)}»` : ''}</div>
                    <div class="text-muted">Bot: ${s.got ? escHtml(s.got) : '(sin respuesta)'}</div>
                </div>
            </div>`).join('');
    }

    function setBadge($row, passed) {
        const badge = passed
            ? '<span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>Pasó</span>'
            : '<span class="badge bg-danger-subtle text-danger"><i class="fas fa-xmark me-1"></i>Falló</span>';
        $row.find('.result-cell').html(badge);
    }

    // Run one scenario
    $('.run-one-btn').on('click', function () {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const caseId = $row.data('case-id');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: $btn.data('url'), method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(function (result) {
            setBadge($row, result.passed);
            const $detail = $(`tr[data-detail-for="${caseId}"]`);
            $detail.removeClass('d-none').find('.detail-content').html(renderDetail(result));
        }).fail(function () {
            toastr.error('Error al ejecutar el escenario');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-play"></i>');
        });
    });

    // Run all
    $('#run-all-btn').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Ejecutando…');
        $.ajax({
            url: @json(route('chatflow.test-cases.run-all', $chatFlow)), method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(function (data) {
            $.each(data.results, function (caseId, passed) {
                setBadge($(`tr[data-case-id="${caseId}"]`), passed);
            });
            $('#run-summary').html(`<strong class="${data.failed ? 'text-danger' : 'text-success'}">${data.passed}/${data.total} escenarios pasaron</strong>`);
            data.failed ? toastr.warning(`${data.failed} escenario(s) fallaron`) : toastr.success('Todos los escenarios pasaron');
        }).fail(function () {
            toastr.error('Error al ejecutar los escenarios');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i> Ejecutar todos');
        });
    });
});
</script>
@endpush
