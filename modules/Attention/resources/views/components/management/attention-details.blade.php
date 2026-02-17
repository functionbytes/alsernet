{{-- Component: Attention Details Card --}}
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Detalle de la solicitud</h5>
        <p class="small mb-0 text-muted">Información del radicado y fechas</p>
    </div>
    <div class="card-body">
        {{ csrf_field() }}
        <input type="hidden" id="uid" name="uid" value="{{ $attention->uid }}">

        <div class="row g-3">
            @if($attention->radicado)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Radicado</label>
                    <input type="text" class="form-control" value="{{ $attention->radicado }}" disabled>
                </div>
            @endif

            @if($attention->type)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Tipo</label>
                    <input type="text" class="form-control" value="{{ $attention->type->name }}" disabled>
                </div>
            @endif

            @if($attention->category)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Categoría</label>
                    <input type="text" class="form-control" disabled value="{{ $attention->category->name }}">
                </div>
            @endif

            @if($attention->sede)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Sede</label>
                    <input type="text" class="form-control" disabled value="{{ $attention->sede->name }}">
                </div>
            @endif

            @if($attention->status)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Estado</label>
                    <input type="text" class="form-control" disabled value="{{ $attention->status_label }}">
                </div>
            @endif

            @if($attention->department)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Departamento asignado</label>
                    <input type="text" class="form-control" disabled value="{{ $attention->department->name }}">
                </div>
            @endif

            @if($attention->assignedUser)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Usuario asignado</label>
                    <input type="text" class="form-control" disabled value="{{ $attention->assignedUser->name }}">
                </div>
            @endif

            @if($attention->response_type)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Tipo de respuesta</label>
                    <input type="text" class="form-control" disabled value="{{ $attention->response_type->label() }}">
                </div>
            @endif

            @if($attention->created_at)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Fecha de radicación</label>
                    <input type="text" class="form-control" value="{{ $attention->created_at->format('d/m/Y H:i') }}" disabled>
                </div>
            @endif

            @if($attention->resolved_at)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Fecha de resolución</label>
                    <input type="text" class="form-control" value="{{ $attention->resolved_at->format('d/m/Y H:i') }}" disabled>
                </div>
            @endif

            @if($attention->closed_at)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Fecha de cierre</label>
                    <input type="text" class="form-control" value="{{ $attention->closed_at->format('d/m/Y H:i') }}" disabled>
                </div>
            @endif

            <div class="col-12">
                <label class="form-label fw-semibold">Asunto</label>
                <textarea class="form-control" rows="2" disabled>{{ $attention->subject }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Descripción</label>
                <textarea class="form-control" rows="4" disabled>{{ $attention->description }}</textarea>
            </div>

            @if($attention->resolution)
                <div class="col-12">
                    <label class="form-label fw-semibold">Resolución</label>
                    <textarea class="form-control" rows="4" disabled>{{ $attention->resolution }}</textarea>
                </div>
            @endif
        </div>
    </div>
</div>
