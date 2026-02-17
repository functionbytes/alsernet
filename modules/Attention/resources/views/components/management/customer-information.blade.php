{{-- Component: Customer Information Card --}}
<div class="card mb-3">
    <div class="card-header p-3 bg-white border-bottom">
        <h5 class="mb-1 fw-bold">Información del ciudadano</h5>
        <p class="small mb-0 text-muted">Datos de contacto del ciudadano</p>
    </div>
    <div class="card-body">
        @if($attention->is_anonymous)
            <div class="alert alert-info py-2 px-3 mb-0" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fas fa-user-secret me-2 mt-1"></i>
                    <div>
                        <strong>PQRSF anónimo</strong><br>
                        <small>El ciudadano decidió radicar de forma anónima</small>
                    </div>
                </div>
            </div>
        @else
            <div class="row g-3">
                @if($attention->customer_firstname)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Nombres</label>
                        <input type="text" class="form-control" value="{{ $attention->customer_firstname }}" disabled>
                    </div>
                @endif

                @if($attention->customer_lastname)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Apellidos</label>
                        <input type="text" class="form-control" value="{{ $attention->customer_lastname }}" disabled>
                    </div>
                @endif

                @if($attention->customer_dni)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">DNI/Cédula</label>
                        <input type="text" class="form-control" value="{{ $attention->customer_dni }}" disabled>
                    </div>
                @endif

                @if($attention->customer_email)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Correo electrónico</label>
                        <input type="text" class="form-control" value="{{ $attention->customer_email }}" disabled>
                    </div>
                @endif

                @if($attention->customer_cellphone)
                    <div class="col-sm-12 col-md-6">
                        <label class="form-label fw-semibold">Teléfono</label>
                        <input type="text" class="form-control" value="{{ $attention->customer_cellphone }}" disabled>
                    </div>
                @endif

                @if($attention->customer_address)
                    <div class="col-sm-12">
                        <label class="form-label fw-semibold">Dirección</label>
                        <input type="text" class="form-control" value="{{ $attention->customer_address }}" disabled>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
