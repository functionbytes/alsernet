@extends('layouts.theme')

@section('title', 'Correos programados')

@section('page_header')
    @include('core::components.card', ['title' => 'Correos programados'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header p-4 border-bottom d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-1 fw-bold">Correos programados</h5>
                <p class="small mb-0 text-muted">Correos de tickets aún no enviados, con fecha de envío futura — de todos los tickets a la vez.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                {{-- Selector de modo de vista (Lista/Compacta/Kanban) — mismo
                     concepto que el de HelpdeskEmailActivity (evx-mode-switch),
                     con prefijo de clase propio (sched-) y paleta de
                     HelpdeskTickets. Opera SOLO sobre las filas ya cargadas
                     en `rows` (ver @push('scripts')), sin volver a pedir
                     nada al servidor al cambiar de modo. --}}
                <div class="sched-mode-switch" id="sched-mode-switch" role="group" aria-label="Modo de vista">
                    <button type="button" class="sched-mode-btn on" data-sched-mode="list">
                        <i class="fa-solid fa-list" aria-hidden="true"></i> Lista
                    </button>
                    <button type="button" class="sched-mode-btn" data-sched-mode="compact">
                        <i class="fa-solid fa-bars" aria-hidden="true"></i> Compacta
                    </button>
                    <button type="button" class="sched-mode-btn" data-sched-mode="kanban">
                        <i class="fa-solid fa-table-columns" aria-hidden="true"></i> Kanban
                    </button>
                </div>
                <div id="sched-bulk-toolbar" class="d-none">
                    <span class="small text-muted me-2"><span id="sched-bulk-count">0</span> seleccionados</span>
                    <button type="button" class="btn btn-primary btn-sm" id="sched-bulk-resend">Enviar ahora</button>
                    <button type="button" class="btn btn-light btn-sm" id="sched-bulk-cancel">Cancelar</button>
                </div>
            </div>
        </div>

        <div id="sched-list-view" class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="sched-col-shrink"><input type="checkbox" id="sched-select-all"></th>
                        <th>Ticket</th>
                        <th>Asunto</th>
                        <th>Para</th>
                        <th>Programado para</th>
                        <th class="sched-col-shrink"></th>
                    </tr>
                </thead>
                <tbody id="sched-tbody">
                    <tr id="sched-loading"><td colspan="6" class="text-center text-muted py-4">Cargando…</td></tr>
                    <tr id="sched-empty" class="d-none"><td colspan="6" class="text-center text-muted py-4">No hay correos programados.</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Vista Kanban — la rellena el JS a partir de las mismas filas ya
             cargadas en la tabla de arriba (ver @push('scripts')). Agrupa por
             franja horaria de scheduled_at (Atrasado/Hoy/Esta semana/Más
             adelante): agrupar por estado no aporta nada en esta pantalla
             (todo está en estado 'scheduled' por definición), la pregunta
             real de un agente aquí es "qué sale y cuándo". --}}
        <div id="sched-kanban-view" class="sched-mode-hidden">
            <div class="sched-kanban-empty text-center text-muted py-4">Cargando…</div>
        </div>
    </div>
@endsection

{{-- El CSS/JS del selector de modos vive en ficheros propios del módulo
     (resources/css|js/scheduled-emails.*), no en tickets-app.css: su
     selector raíz .tkt trae `body:has(.tkt) .mc-content { margin:0!important;
     height:calc(100vh - 80px)!important; ... }`, pensado para el shell de
     app a pantalla completa de esa otra pantalla — cargarlo en esta rompería
     el layout normal de tarjeta que usa scheduled.blade.php. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/scheduled-emails.css') }}">

@push('scripts')
{{-- Solo datos: las URLs de servidor que el JS necesita. La lógica entera
     vive en scheduled-emails.js. --}}
<script>
window.bvSchedConfig = {
    dataUrl: @json($dataUrl),
    bulkUrl: @json($bulkUrl),
    ticketsUrl: @json(url('panel/helpdesk/tickets')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/scheduled-emails.js') }}"></script>
@endpush
