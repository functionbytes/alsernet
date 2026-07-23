@extends('layouts.theme')
@section('title', 'Festivos · SLA')
@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="far fa-calendar-check text-primary me-2"></i>Festivos del calendario de negocio
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Los festivos se tratan como días no laborables: los vencimientos SLA que caerían en un festivo se empujan al siguiente día hábil. Marca «recurrente» para festivos anuales fijos.
    </p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Añadir festivo</h6>
                <form method="POST" action="{{ route('helpdesksla.holidays.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small" for="h-date">Fecha</label>
                        <input type="date" name="date" id="h-date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date') }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="h-name">Nombre</label>
                        <input type="text" name="name" id="h-name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="255" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_recurring" id="h-recurring" class="form-check-input" value="1" {{ old('is_recurring') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="h-recurring">Recurrente cada año (se compara por día y mes)</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Añadir festivo</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Festivos configurados</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays as $holiday)
                                <tr>
                                    <td>{{ $holiday->is_recurring ? $holiday->date->format('d/m') : $holiday->date->format('d/m/Y') }}</td>
                                    <td>{{ $holiday->name }}</td>
                                    <td>
                                        @if($holiday->is_recurring)
                                            <span class="badge bg-info-subtle text-info">Anual</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Puntual</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <form method="POST" action="{{ route('helpdesksla.holidays.destroy', $holiday) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item">Eliminar</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No hay festivos configurados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
