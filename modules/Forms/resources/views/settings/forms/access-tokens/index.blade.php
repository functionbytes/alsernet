@extends('layouts.theme')

@section('title', 'Tokens de acceso: ' . $form->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('settings.forms.settings.access', $form) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $form->name }}</h5>
                        <span class="text-muted">Tokens de acceso individual</span>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateTokenModal">
                    <i class="fas fa-key me-1"></i> Generar token
                </button>
            </div>

            <div class="alert alert-info small mb-3">
                <i class="fas fa-info-circle me-1"></i>
                Los tokens de acceso permiten compartir el formulario con personas específicas mediante una URL única.
                Ideal para formularios privados con acceso controlado.
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Token</th>
                                    <th>Email</th>
                                    <th class="text-center">Usos</th>
                                    <th>Expira</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end pe-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tokens as $token)
                                    @php
                                        $isExpired  = $token->expires_at && $token->expires_at->isPast();
                                        $isExhausted = $token->max_uses && $token->uses_count >= $token->max_uses;
                                        $isValid    = !$isExpired && !$isExhausted;
                                        $accessUrl  = route('forms.public.access', $token->token);
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <code class="small text-muted">{{ Str::mask($token->token, '*', 8) }}</code>
                                                <button type="button"
                                                        class="btn btn-sm btn-link p-0 text-secondary btn-copy-token"
                                                        data-token="{{ $token->token }}"
                                                        title="Copiar token">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="small">{{ $token->email ?: '—' }}</td>
                                        <td class="text-center small">
                                            {{ $token->uses_count }}
                                            @if ($token->max_uses)
                                                / {{ $token->max_uses }}
                                            @else
                                                / ∞
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if ($token->expires_at)
                                                <span class="{{ $isExpired ? 'text-danger' : 'text-muted' }}">
                                                    {{ $token->expires_at->format('d/m/Y H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">Sin expiración</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($isExpired)
                                                <span class="badge bg-danger">Expirado</span>
                                            @elseif ($isExhausted)
                                                <span class="badge bg-warning text-dark">Agotado</span>
                                            @else
                                                <span class="badge bg-success">Válido</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary btn-copy-url"
                                                        data-url="{{ $accessUrl }}"
                                                        title="Copiar URL de acceso">
                                                    <i class="fas fa-link"></i>
                                                </button>
                                                <form method="POST" action="{{ route('settings.forms.access-tokens.destroy', [$form, $token]) }}"
                                                      onsubmit="return confirm('¿Eliminar este token? Los usuarios con este enlace perderán el acceso.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-key fa-2x mb-2 d-block opacity-50"></i>
                                            No hay tokens generados
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Modal generar token --}}
<div class="modal fade" id="generateTokenModal" tabindex="-1" aria-labelledby="generateTokenModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateTokenModalLabel">Generar token de acceso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="tokenEmail" class="form-label">Email (opcional)</label>
                        <input type="email" id="tokenEmail" class="form-control" placeholder="usuario@ejemplo.com">
                        <div class="form-text">Permite identificar a quién se entregó el token.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="tokenMaxUses" class="form-label">Máximo de usos</label>
                        <input type="number" id="tokenMaxUses" class="form-control" min="1" placeholder="Sin límite">
                    </div>
                    <div class="col-md-6">
                        <label for="tokenExpiresAt" class="form-label">Fecha de expiración</label>
                        <input type="datetime-local" id="tokenExpiresAt" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="generateTokenBtn">
                    <i class="fas fa-key me-1"></i> Generar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const storeTokenUrl = '{{ route('settings.forms.access-tokens.store', $form) }}';
    const csrfToken     = $('meta[name="csrf-token"]').attr('content');

    // Generar token
    $('#generateTokenBtn').on('click', function () {
        $.ajax({
            url: storeTokenUrl,
            method: 'POST',
            data: {
                email:      $('#tokenEmail').val() || null,
                max_uses:   $('#tokenMaxUses').val() || null,
                expires_at: $('#tokenExpiresAt').val() || null,
            },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                toastr.success('Token generado correctamente');
                $('#generateTokenModal').modal('hide');
                location.reload();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    toastr.error(Object.values(xhr.responseJSON.errors).flat().join(', '));
                } else {
                    toastr.error('Error al generar el token');
                }
            }
        });
    });

    // Limpiar modal al cerrar
    $('#generateTokenModal').on('hidden.bs.modal', function () {
        $('#tokenEmail, #tokenMaxUses, #tokenExpiresAt').val('');
    });

    // Copiar token al portapapeles
    $(document).on('click', '.btn-copy-token', function () {
        navigator.clipboard.writeText($(this).data('token')).then(function () {
            toastr.info('Token copiado al portapapeles');
        });
    });

    // Copiar URL de acceso al portapapeles
    $(document).on('click', '.btn-copy-url', function () {
        navigator.clipboard.writeText($(this).data('url')).then(function () {
            toastr.success('URL de acceso copiada al portapapeles');
        });
    });
</script>
@endpush
