@extends('layouts.theme')

@section('title', 'Plantillas de email')

@section('page_header')
    @include('core::components.card', ['title' => 'Plantillas de email'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0 small">Diseños de email reutilizables para campañas y automatizaciones</p>
        <a href="{{ route('remarketing.templates.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva plantilla
        </a>
    </div>

    @if($templates->isEmpty())
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-file-code fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-3">No hay plantillas creadas todavía</p>
                <a href="{{ route('remarketing.templates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Crear primera plantilla
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($templates as $template)
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="card h-100">

                        {{-- Thumbnail --}}
                        <div class="bg-light d-flex align-items-center justify-content-center border-bottom"
                             style="height:160px;overflow:hidden">
                            @if($template->thumbnail_url)
                                <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}"
                                     width="100%" loading="lazy"
                                     style="object-fit:cover;height:160px">
                            @else
                                <i class="fas fa-image fa-3x text-muted"></i>
                            @endif
                        </div>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="fw-bold mb-1 me-2">{{ $template->name }}</h6>
                                @php
                                    $typeColors = ['campaign'=>'primary','automation'=>'success','transactional'=>'info'];
                                    $tColor = $typeColors[$template->type] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $tColor }}-subtle text-{{ $tColor }} flex-shrink-0">{{ ucfirst($template->type) }}</span>
                            </div>
                            @if($template->subject)
                                <p class="small text-muted mb-0 mt-1">{{ Str::limit($template->subject, 60) }}</p>
                            @endif
                            <p class="small text-muted mb-0 mt-1">{{ $template->created_at->format('d/m/Y') }}</p>
                        </div>

                        <div class="card-footer bg-transparent border-top d-flex gap-2">
                            <a href="{{ route('remarketing.templates.edit', $template) }}"
                               class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="{{ route('remarketing.templates.preview', $template) }}"
                               target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item btn-duplicate" data-id="{{ $template->id }}">Duplicar</button>
                                    </li>
                                    @if(!$template->is_global)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item btn-delete"
                                                    data-id="{{ $template->id }}"
                                                    data-name="{{ $template->name }}"
                                                    data-url="{{ route('remarketing.templates.destroy', $template) }}">
                                                Eliminar
                                            </button>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

        @if($templates->hasPages())
            <div class="mt-3">
                {{ $templates->links() }}
            </div>
        @endif
    @endif

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Eliminar plantilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Eliminar la plantilla <strong id="delete-name"></strong>? Las campañas que la usen perderán su plantilla.</p>
                </div>
                <div class="modal-footer flex-column">
                    <form id="deleteForm" method="POST" class="w-100">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100 mb-2">Eliminar plantilla</button>
                    </form>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $(document).on('click', '.btn-delete', function () {
        $('#delete-name').text($(this).data('name'));
        $('#deleteForm').attr('action', $(this).data('url'));
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-duplicate', function () {
        var id = $(this).data('id');
        $.ajax({
            url: '/api/remarketing/templates/' + id + '/duplicate',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                toastr.success(res.message || 'Plantilla duplicada');
                location.reload();
            },
            error: function () { toastr.error('Error al duplicar la plantilla'); }
        });
    });

});
</script>
@endpush
