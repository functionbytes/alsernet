@extends('layouts.admin')

@section('title', 'Editar Campaña')

@section('content')
    <div class="widget-content">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.social.campaigns.index') }}">Campañas</a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ol>
        </nav>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-bullseye me-2"></i>Editar Campaña</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.social.campaigns.update', $campaign) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $campaign->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label fw-semibold">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $campaign->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label fw-semibold">Fecha de inicio</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}">
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label fw-semibold">Fecha de fin</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}">
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="color" class="form-label fw-semibold">Color <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" id="color" name="color" value="{{ old('color', $campaign->color) }}" style="width: 60px; height: 40px;" required>
                                    <span id="colorValue">{{ old('color', $campaign->color) }}</span>
                                </div>
                                @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" value="1" {{ old('status', $campaign->status) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="status">Campaña activa</label>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.social.campaigns.index') }}" class="btn btn-light"><i class="fas fa-times me-1"></i> Cancelar</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Actualizar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('colorValue').textContent = this.value.toUpperCase();
});
</script>
@endpush
