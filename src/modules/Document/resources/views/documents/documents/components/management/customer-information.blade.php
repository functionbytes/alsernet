{{-- Component: Customer Information Card --}}
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Información del cliente</h5>
        <p class="small mb-0 text-muted">Datos de contacto del cliente</p>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @if($document->customer_firstname)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Nombres</label>
                    <input type="text" class="form-control" value="{{$document->customer_firstname}}" disabled>
                </div>
            @endif

            @if($document->customer_lastname)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Apellidos</label>
                    <input type="text" class="form-control" value="{{$document->customer_lastname}}" disabled>
                </div>
            @endif

            @if($document->customer_dni)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">DNI/NIE/CIF</label>
                    <input type="text" class="form-control" value="{{$document->customer_dni}}" disabled>
                </div>
            @endif

            @if($document->customer_email)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Correo electrónico</label>
                    <input type="text" class="form-control" value="{{$document->customer_email}}" disabled>
                </div>
            @endif

            @if($document->customer_cellphone)
                <div class="col-sm-12 col-md-6">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <input type="text" class="form-control" value="{{$document->customer_cellphone}}" disabled>
                </div>
            @endif

            @if($document->lang)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Idioma</label>
                        <input type="text" class="form-control" value="{{$document->lang->title}}" disabled>
                    </div>
            @endif

        </div>
    </div>
</div>
