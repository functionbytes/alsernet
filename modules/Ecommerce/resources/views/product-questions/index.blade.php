@extends('layouts.theme')

@section('title', 'Preguntas y respuestas')

@section('content')
@include('core::components.card', ['title' => 'Ecommerce - Preguntas y Respuestas'])

<div class="widget-content">
    @include('core::components.alerts')

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" style="max-width:200px" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <option value="pending" @selected(request('status') === 'pending')>Sin responder</option>
                    <option value="answered" @selected(request('status') === 'answered')>Respondidas</option>
                </select>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($questions->count() > 0)
                @foreach($questions as $q)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>{{ $q->product?->name ?? 'Producto eliminado' }}</strong>
                            <small class="text-muted">{{ $q->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <p class="mb-2">
                            <i class="far fa-question-circle text-primary me-1"></i>
                            <strong>{{ $q->author_name }}:</strong> {{ $q->question }}
                        </p>
                        @if($q->answer)
                            <div class="alert alert-info mb-2">
                                <strong>{{ $q->answered_by }}:</strong> {{ $q->answer }}
                                <small class="d-block text-muted mt-1">{{ $q->answered_at?->format('d/m/Y H:i') }}</small>
                            </div>
                            @if($q->is_published)
                                <span class="badge bg-success">Publicada</span>
                            @else
                                <span class="badge bg-secondary">No publicada</span>
                            @endif
                        @else
                            <form method="POST" action="{{ route('ecommerce.product-questions.answer', $q) }}" class="mb-2">
                                @csrf
                                <textarea name="answer" class="form-control mb-2" rows="2" placeholder="Escribe tu respuesta..." required></textarea>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_published" value="1" checked id="pub-{{ $q->id }}" class="form-check-input">
                                        <label for="pub-{{ $q->id }}" class="form-check-label">Publicar</label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary">Responder</button>
                                </div>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('ecommerce.product-questions.destroy', $q) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta pregunta?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger">Eliminar</button>
                        </form>
                    </div>
                @endforeach
                <div class="mt-3">{{ $questions->links() }}</div>
            @else
                <div class="text-center text-muted py-5">
                    <i class="far fa-question-circle fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">No hay preguntas todavía.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
