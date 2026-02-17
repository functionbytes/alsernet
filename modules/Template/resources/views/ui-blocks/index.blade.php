@extends('layouts.theme')

@section('page_title', 'Bloques UI')

@section('content')

    @include('core::components.card', ['title' => 'Bloques de interfaz de usuario'])

    @include('core::components.alerts')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">
            Gestiona los bloques disponibles en el editor de páginas. Arrastra para reordenar.
        </p>
        <a href="{{ route('settings.ui-blocks.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Nuevo bloque
        </a>
    </div>

    @if($blocks->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-puzzle-piece fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay bloques definidos</h5>
                <a href="{{ route('settings.ui-blocks.create') }}" class="btn btn-primary mt-2">
                    Crear primer bloque
                </a>
            </div>
        </div>
    @else
        <div class="row g-3" id="blocksGrid">
            @foreach($blocks as $block)
                <div class="col-xl-3 col-lg-4 col-sm-6" data-id="{{ $block->id }}">
                    <div class="card h-100 {{ $block->is_active ? '' : 'opacity-50' }}">
                        <div class="card-body text-center py-3">
                            <div class="drag-handle text-muted mb-2" style="cursor:grab; font-size:.8rem;">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="mb-2" style="font-size:2rem; color:#adb5bd;">
                                <i class="{{ $block->icon ?: 'fas fa-puzzle-piece' }}"></i>
                            </div>
                            <h6 class="card-title mb-1 fw-semibold">{{ $block->name }}</h6>
                            <p class="card-text small text-muted mb-1">{{ $block->description }}</p>
                            <code class="small text-secondary">{{ $block->key }}</code>
                            @if(!$block->is_active)
                                <div class="mt-1">
                                    <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white border-top-0 pt-0 d-flex gap-1 justify-content-center">
                            <a href="{{ route('settings.ui-blocks.edit', $block->id) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('settings.ui-blocks.destroy', $block->id) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar el bloque «{{ $block->name }}»?')">
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
                Sortable.create(document.getElementById('blocksGrid'), {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function () {
                        var order = [];
                        $('#blocksGrid [data-id]').each(function () {
                            order.push($(this).data('id'));
                        });
                        $.post('{{ route('settings.ui-blocks.order') }}', {
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
