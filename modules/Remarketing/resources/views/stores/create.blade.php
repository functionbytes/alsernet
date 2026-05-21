@extends('layouts.theme')

@section('title', 'Conectar tienda')

@section('page_header')
    @include('core::components.card', ['title' => 'Conectar tienda'])
@endsection

@section('content')

    @include('core::components.alerts')

    {{-- Wizard steps indicator --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 wizard-step active" data-step="1">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white bg-primary" style="width:32px;height:32px">1</div>
                    <span class="fw-semibold d-none d-sm-inline">Plataforma</span>
                </div>
                <div class="flex-grow-1 border-top border-2 mx-2"></div>
                <div class="d-flex align-items-center gap-2 wizard-step" data-step="2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-muted bg-light" style="width:32px;height:32px" id="step2-circle">2</div>
                    <span class="text-muted d-none d-sm-inline" id="step2-label">Credenciales</span>
                </div>
                <div class="flex-grow-1 border-top border-2 mx-2"></div>
                <div class="d-flex align-items-center gap-2 wizard-step" data-step="3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-muted bg-light" style="width:32px;height:32px" id="step3-circle">3</div>
                    <span class="text-muted d-none d-sm-inline" id="step3-label">Verificación</span>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('remarketing.stores.store') }}" method="POST" id="storeForm">
        @csrf
        <input type="hidden" name="platform" id="selectedPlatform">

        {{-- Step 1: Platform selection --}}
        <div id="step-1">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Selecciona la plataforma</h5>
                    <small class="text-muted">Elige la plataforma de tu tienda para continuar</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="platform-card card border-2 text-center p-3 cursor-pointer h-100" data-platform="shopify">
                                <i class="fab fa-shopify fa-3x mb-2 text-success"></i>
                                <div class="fw-semibold">Shopify</div>
                                <small class="text-muted">OAuth + API key</small>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="platform-card card border-2 text-center p-3 cursor-pointer h-100" data-platform="woocommerce">
                                <i class="fab fa-wordpress fa-3x mb-2 text-primary"></i>
                                <div class="fw-semibold">WooCommerce</div>
                                <small class="text-muted">Consumer key/secret</small>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="platform-card card border-2 text-center p-3 cursor-pointer h-100" data-platform="prestashop">
                                <i class="fas fa-shopping-bag fa-3x mb-2 text-warning"></i>
                                <div class="fw-semibold">PrestaShop</div>
                                <small class="text-muted">API Key</small>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="platform-card card border-2 text-center p-3 cursor-pointer h-100" data-platform="magento">
                                <i class="fas fa-store fa-3x mb-2 text-danger"></i>
                                <div class="fw-semibold">Magento</div>
                                <small class="text-muted">Access token</small>
                            </div>
                        </div>

                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="platform-card card border-2 text-center p-3 cursor-pointer h-100" data-platform="bigcommerce">
                                <i class="fas fa-cart-plus fa-3x mb-2 text-info"></i>
                                <div class="fw-semibold">BigCommerce</div>
                                <small class="text-muted">API credentials</small>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" id="btn-next-step1" class="btn btn-primary w-100 mb-2" disabled>
                        Continuar <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                    <a href="{{ route('remarketing.stores.index') }}" class="btn btn-light w-100">Cancelar</a>
                </div>
            </div>
        </div>

        {{-- Step 2: Credentials (shown/hidden per platform) --}}
        <div id="step-2" class="d-none">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Credenciales de conexión</h5>
                    <small class="text-muted">Introduce las credenciales de acceso a tu tienda</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label">Nombre de la tienda <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Mi tienda online" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Dominio / URL de la tienda <span class="text-danger">*</span></label>
                            <input type="url" name="domain" class="form-control @error('domain') is-invalid @enderror"
                                   placeholder="https://mitienda.com" value="{{ old('domain') }}" required>
                            @error('domain')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Shopify credentials --}}
                        <div class="platform-fields col-12 d-none" data-platform="shopify">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">API Key <span class="text-danger">*</span></label>
                                    <input type="text" name="api_key" class="form-control" placeholder="shpat_...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">API Secret</label>
                                    <input type="password" name="api_secret" class="form-control" placeholder="shpss_...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Webhook Secret</label>
                                    <input type="text" name="settings[webhook_secret]" class="form-control">
                                </div>
                            </div>
                        </div>

                        {{-- WooCommerce credentials --}}
                        <div class="platform-fields col-12 d-none" data-platform="woocommerce">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Consumer Key <span class="text-danger">*</span></label>
                                    <input type="text" name="api_key" class="form-control" placeholder="ck_...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Consumer Secret <span class="text-danger">*</span></label>
                                    <input type="password" name="api_secret" class="form-control" placeholder="cs_...">
                                </div>
                            </div>
                        </div>

                        {{-- PrestaShop credentials --}}
                        <div class="platform-fields col-12 d-none" data-platform="prestashop">
                            <label class="form-label">API Key <span class="text-danger">*</span></label>
                            <input type="text" name="api_key" class="form-control">
                        </div>

                        {{-- Magento credentials --}}
                        <div class="platform-fields col-12 d-none" data-platform="magento">
                            <label class="form-label">Access Token <span class="text-danger">*</span></label>
                            <input type="text" name="access_token" class="form-control">
                        </div>

                        {{-- BigCommerce credentials --}}
                        <div class="platform-fields col-12 d-none" data-platform="bigcommerce">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Store Hash <span class="text-danger">*</span></label>
                                    <input type="text" name="api_key" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Access Token <span class="text-danger">*</span></label>
                                    <input type="password" name="access_token" class="form-control">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" id="btn-next-step2" class="btn btn-primary w-100 mb-2">
                        Verificar conexión <i class="fas fa-plug ms-1"></i>
                    </button>
                    <button type="button" id="btn-back-step2" class="btn btn-light w-100">Volver</button>
                </div>
            </div>
        </div>

        {{-- Step 3: Verification --}}
        <div id="step-3" class="d-none">
            <div class="card">
                <div class="card-header border-bottom p-3">
                    <h5 class="mb-0 fw-bold">Verificación de conexión</h5>
                    <small class="text-muted">Comprobando las credenciales con la plataforma</small>
                </div>
                <div class="card-body text-center py-5" id="verify-container">
                    <div id="verify-loading">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted">Verificando credenciales...</p>
                    </div>
                    <div id="verify-success" class="d-none">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h5 class="text-success">¡Conexión verificada!</h5>
                        <p class="text-muted mb-0">Las credenciales son correctas. Puedes guardar la tienda.</p>
                    </div>
                    <div id="verify-error" class="d-none">
                        <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                        <h5 class="text-danger">Error de conexión</h5>
                        <p class="text-muted" id="verify-error-msg">No se pudo verificar las credenciales.</p>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" id="btn-save" class="btn btn-primary w-100 mb-2 d-none">
                        <i class="fas fa-save me-1"></i> Guardar tienda
                    </button>
                    <button type="button" id="btn-back-step3" class="btn btn-light w-100">Volver</button>
                </div>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var selectedPlatform = null;

    // Step 1: platform selection
    $(document).on('click', '.platform-card', function () {
        $('.platform-card').removeClass('border-primary');
        $(this).addClass('border-primary');
        selectedPlatform = $(this).data('platform');
        $('#selectedPlatform').val(selectedPlatform);
        $('#btn-next-step1').prop('disabled', false);
    });

    $('#btn-next-step1').on('click', function () {
        goToStep(2);
        // Show correct credential fields
        $('.platform-fields').addClass('d-none');
        $('.platform-fields[data-platform="' + selectedPlatform + '"]').removeClass('d-none');
    });

    $('#btn-back-step2').on('click', function () { goToStep(1); });

    // Step 2 → verify credentials
    $('#btn-next-step2').on('click', function () {
        var name = $('[name="name"]').val();
        var domain = $('[name="domain"]').val();
        if (!name || !domain) {
            toastr.warning('Completa el nombre y dominio antes de continuar');
            return;
        }
        goToStep(3);
        verifyCredentials();
    });

    $('#btn-back-step3').on('click', function () { goToStep(2); });

    function goToStep(n) {
        for (var i = 1; i <= 3; i++) {
            $('#step-' + i).addClass('d-none');
        }
        $('#step-' + n).removeClass('d-none');

        // Update circles
        for (var j = 2; j <= 3; j++) {
            if (j <= n) {
                $('#step' + j + '-circle').removeClass('bg-light text-muted').addClass('bg-primary text-white');
                $('#step' + j + '-label').removeClass('text-muted').addClass('fw-semibold');
            } else {
                $('#step' + j + '-circle').addClass('bg-light text-muted').removeClass('bg-primary text-white');
                $('#step' + j + '-label').addClass('text-muted').removeClass('fw-semibold');
            }
        }
    }

    function verifyCredentials() {
        $('#verify-loading').removeClass('d-none');
        $('#verify-success, #verify-error').addClass('d-none');
        $('#btn-save').addClass('d-none');

        var formData = $('#storeForm').serialize();

        $.ajax({
            url: '{{ route('remarketing.stores.verify') }}',
            method: 'POST',
            data: formData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#verify-loading').addClass('d-none');
                $('#verify-success').removeClass('d-none');
                $('#btn-save').removeClass('d-none');
            },
            error: function (xhr) {
                $('#verify-loading').addClass('d-none');
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'No se pudo verificar las credenciales.';
                $('#verify-error-msg').text(msg);
                $('#verify-error').removeClass('d-none');
            }
        });
    }
});
</script>
@endpush
