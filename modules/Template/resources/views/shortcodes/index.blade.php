@extends('layouts.theme')

@section('page_title', 'Shortcodes')

@section('content')

    @include('core::components.card', ['title' => 'Shortcodes'])

    @include('core::components.alerts')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">
            Gestiona los shortcodes disponibles en el editor de páginas. Arrastra para reordenar.
        </p>
        <a href="{{ route('settings.shortcodes.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Nuevo shortcode
        </a>
    </div>

    @if($shortcodes->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-code fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay shortcodes definidos</h5>
                <a href="{{ route('settings.shortcodes.create') }}" class="btn btn-primary mt-2">
                    Crear primer shortcode
                </a>
            </div>
        </div>
    @else
        <div class="row g-3" id="shortcodesGrid">
            @foreach($shortcodes as $shortcode)
                <div class="col-xl-3 col-lg-4 col-sm-6" data-id="{{ $shortcode->id }}">
                    <div class="card h-100 {{ $shortcode->is_active ? '' : 'opacity-50' }}">
                        <div class="card-body text-center py-3">
                            <div class="drag-handle text-muted mb-2" style="cursor:grab; font-size:.8rem;">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="mb-2" style="font-size:2rem; color:#adb5bd;">
                                <i class="{{ $shortcode->icon ?: 'fas fa-code' }}"></i>
                            </div>
                            <h6 class="card-title mb-1 fw-semibold">{{ $shortcode->name }}</h6>
                            <p class="card-text small text-muted mb-1">{{ $shortcode->description }}</p>
                            <code class="small text-secondary">{{ $shortcode->key }}</code>
                            @if($shortcode->render_template)
                                <div class="mt-1">
                                    <span class="badge bg-success-subtle text-success" title="Tiene plantilla de renderizado">
                                        <i class="fas fa-check-circle me-1"></i>Render
                                    </span>
                                </div>
                            @endif
                            @if(!$shortcode->is_active)
                                <div class="mt-1">
                                    <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white border-top-0 pt-0 d-flex gap-1 justify-content-center">
                            <a href="{{ route('settings.shortcodes.edit', $shortcode->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('settings.shortcodes.destroy', $shortcode->id) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar el shortcode «{{ $shortcode->name }}»?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        $(function () {
            if (typeof Sortable !== 'undefined') {
                Sortable.create(document.getElementById('shortcodesGrid'), {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function () {
                        var order = [];
                        $('#shortcodesGrid [data-id]').each(function () {
                            order.push($(this).data('id'));
                        });
                        $.post('{{ route('settings.shortcodes.order') }}', {
                            _token: '{{ csrf_token() }}',
                            order: order
                        });
                    }
                });
            }

            @if(session('success'))
            toastr.success(@json(session('success')), 'Éxito');
            @endif
            @if(session('error'))
            toastr.error(@json(session('error')), 'Error');
            @endif
        });
    </script>
@endpush
