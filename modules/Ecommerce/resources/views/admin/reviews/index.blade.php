@extends('layouts.theme')

@section('title', 'Resenas')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Resenas'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Resenas</h5>
                        <p class="small mb-0 text-muted">Moderacion de resenas de productos</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($reviews->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cliente</th>
                                    <th>Estrellas</th>
                                    <th>Comentario</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reviews as $review)
                                    <tr>
                                        <td><a href="{{ route('ecommerce.products.edit', $review->product) }}" class="fw-semibold text-decoration-none">{{ $review->product->name }}</a></td>
                                        <td><small class="text-muted">{{ $review->customer->name ?? $review->customer_name }}</small></td>
                                        <td>
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $review->star ? 'text-warning' : 'text-muted' }}"></i>
                                            @endfor
                                        </td>
                                        <td><small class="text-muted">{{ Str::limit($review->comment, 60) }}</small></td>
                                        <td><span class="badge bg-{{ $review->status === 'approved' ? 'success' : 'warning' }}">{{ $review->status }}</span></td>
                                        <td class="text-center">
                                            @if($review->status !== 'approved')
                                                <form action="{{ route('ecommerce.reviews.approve', $review) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success" title="Aprobar"><i class="fas fa-check"></i></button>
                                                </form>
                                            @endif
                                            <form action="{{ route('ecommerce.reviews.destroy', $review) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar esta resena?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-comment fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay resenas</h5>
                    </div>
                @endif
            </div>

            @if($reviews->hasPages())
                <div class="card-footer">{{ $reviews->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
