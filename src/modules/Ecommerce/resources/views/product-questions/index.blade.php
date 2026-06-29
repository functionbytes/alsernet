@extends('layouts.theme')

@section('title', 'Preguntas y respuestas')

@section('content')

    <div class="card">

        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Preguntas y respuestas</h5>
                    <p class="small mb-0 text-muted">Modera las preguntas que los clientes hacen sobre tus productos</p>
                </div>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="GET" class="d-flex gap-2 align-items-center">
                <label class="form-label mb-0 me-2 fw-semibold">Filtrar:</label>
                <select name="status" class="form-select" style="max-width:240px" onchange="this.form.submit()">
                    <option value="">Todas las preguntas</option>
                    <option value="pending" @selected(request('status') === 'pending')>Sin responder</option>
                    <option value="answered" @selected(request('status') === 'answered')>Respondidas</option>
                </select>
            </form>
        </div>

        <div class="card-body">
            @include('core::components.alerts')

            @if($questions->count() > 0)
                @foreach($questions as $q)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>{{ $q->product?->name ?? 'Producto eliminado' }}</strong>
                                @if(!$q->answer)
                                    <span class="badge bg-warning-subtle text-warning ms-2">Sin responder</span>
                                @elseif($q->is_published)
                                    <span class="badge bg-success-subtle text-success ms-2">Publicada</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary ms-2">No publicada</span>
                                @endif
                            </div>
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
                        @else
                            <form method="POST" action="{{ route('ecommerce.product-questions.answer', $q) }}" class="mb-2">
                                @csrf
                                <textarea name="answer" class="form-control mb-2" rows="2" placeholder="Escribe tu respuesta..." required></textarea>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_published" value="1" checked id="pub-{{ $q->id }}" class="form-check-input">
                                        <label for="pub-{{ $q->id }}" class="form-check-label">Publicar</label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary">Responder</button>
                                </div>
                            </form>
                        @endif
                        <div class="dropdown">
                            <a href="javascript:void(0)" class="text-muted small" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-vertical me-1"></i> Acciones
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <button class="dropdown-item"
                                            onclick="if(confirm('¿Eliminar esta pregunta?')) { document.getElementById('del-q-{{ $q->id }}').submit(); }">
                                        Eliminar pregunta
                                    </button>
                                    <form id="del-q-{{ $q->id }}" method="POST" action="{{ route('ecommerce.product-questions.destroy', $q) }}" class="d-none">
                                        @csrf @method('DELETE')
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                @endforeach

                @if($questions->hasPages())
                    <div class="mt-3">{{ $questions->links() }}</div>
                @endif
            @else
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="mb-3 text-muted">
                            <i class="far fa-question-circle fs-2"></i>
                        </div>
                        <h6 class="mb-1">No hay preguntas todavía</h6>
                        <p class="text-muted mb-0">Cuando los clientes pregunten sobre tus productos aparecerán aquí</p>
                    </div>
                </div>
            @endif
        </div>

    </div>

@endsection
