@extends('layouts.theme')

@section('title', 'Nueva Integración Webhook')

@section('content')

    @include('managers.includes.card', ['title' => 'Nueva Integración Webhook'])

    <div class="card">
        <div class="card-body">
            <form id="integration-form" method="POST" action="{{ route('webhooks.backups.integrations.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="Ej: Integración PrestaShop">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Plan</label>
                        <select name="plan" class="form-select">
                            <option value="free">Free</option>
                            <option value="pro">Pro</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Límite diario de eventos</label>
                        <input type="number" name="daily_limit" class="form-control" value="1000" min="1">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">IPs permitidas (opcional)</label>
                        <input type="text" name="allowed_ips" class="form-control"
                               placeholder="192.168.1.1, 10.0.0.5">
                        <small class="text-muted">Separar con comas. Dejar vacío para permitir todas.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dominios permitidos (opcional)</label>
                        <input type="text" name="allowed_domains" class="form-control"
                               placeholder="example.com, api.example.com">
                        <small class="text-muted">Separar con comas.</small>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Información adicional sobre esta integración..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('webhooks.backups.integrations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Crear integración
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.getElementById('integration-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('¡Creado!', data.message, 'success')
                .then(() => window.location.href = data.redirect);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error al crear la integración', 'error');
        console.error(err);
    });
});
</script>
@endpush
