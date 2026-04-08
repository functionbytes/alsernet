@extends('layouts.helpdesk')

@section('title', 'Editar ticket ' . $ticket->ticket_number)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('agent.helpdesk.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">Editar — {{ $ticket->ticket_number }}</h5>
    </div>

    <div class="card shadow-sm" style="max-width: 700px;">
        <div class="card-body">
            <form action="{{ route('agent.helpdesk.tickets.update', $ticket) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Asunto</label>
                    <input type="text" name="subject" class="form-control"
                        value="{{ old('subject', $ticket->subject) }}" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status_id" class="form-select">
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}" @selected($ticket->status_id == $status->id)>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select name="category_id" class="form-select">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected($ticket->category_id == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Prioridad</label>
                        <select name="priority" class="form-select">
                            @foreach(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'] as $val => $label)
                                <option value="{{ $val }}" @selected($ticket->priority === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar cambios
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
