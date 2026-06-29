{{-- Component: Order Details Card --}}
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Detalle de la orden</h5>
        <p class="small mb-0 text-muted">Información de la orden y fechas</p>
    </div>
    <div class="card-body">
            {{ csrf_field() }}
            <input type="hidden" id="uid" name="uid" value="{{ $document->uid }}">

            <div class="row g-3">
                @if($document->order_id)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Orden</label>
                        <input type="text" class="form-control" value="{{$document->order_id}}" disabled>
                    </div>
                @endif

                @if($document->order_reference)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Referencia</label>
                        <input type="text" class="form-control" value="{{$document->order_reference}}" disabled>
                    </div>
                @endif

                @if($document->documentType?->label)
                        <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Origen</label>
                        <input type="text" class="form-control" disabled value="{{ $document->sync->label }}">
                    </div>
                @endif

                @if($document->documentType?->label)
                        <div class="col-sm-12 col-md-6">
                            <label class="form-label fw-semibold">Tipo de documento</label>
                            <input type="text" class="form-control" disabled value="{{ $document->documentType->label }}">
                        </div>
                @endif

                @if($document->order_date)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Fecha de orden</label>
                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($document->order_date)->format('d/m/Y H:i') }}" disabled>
                    </div>
                @endif

                @if($document->confirmed_at)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Fecha de confirmación</label>
                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($document->confirmed_at)->format('d/m/Y H:i') }}" disabled>
                    </div>
                @endif

                @if($document->created_at)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Fecha de creación</label>
                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i') }}" disabled>
                    </div>
                @endif
            </div>
    </div>
</div>
