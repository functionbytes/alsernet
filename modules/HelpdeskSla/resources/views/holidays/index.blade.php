@extends('layouts.theme')
@section('title', 'Festivos · SLA')
@section('page_header')
    @include('core::components.card', ['title' => 'Festivos · SLA'])
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($coverageWarning)
    <div class="alert alert-warning d-flex align-items-start gap-2">
        <i class="fas fa-triangle-exclamation mt-1"></i>
        <div>{{ $coverageWarning }}</div>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="fas fa-rotate text-primary me-1"></i>Sincronizar calendario oficial</h6>
                <p class="text-muted small mb-3">
                    Carga los festivos verificados de A Coruña (Galicia) para un año concreto: nacionales,
                    autonómicos y los dos locales del Concello. Es seguro repetirlo — no duplica fechas
                    ya cargadas.
                </p>
                <form method="POST" action="{{ route('helpdesksla.holidays.import') }}" class="d-flex gap-2">
                    @csrf
                    <select name="year" class="form-select" required>
                        @foreach($importYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-outline-primary text-nowrap">Sincronizar</button>
                </form>
                @error('year')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>

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
