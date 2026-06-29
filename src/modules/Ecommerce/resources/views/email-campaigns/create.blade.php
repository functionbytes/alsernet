@extends('layouts.theme')

@section('title', 'Nueva campaña de email')

@section('content')

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="#" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva campaña de email</h5>
                        <small class="text-muted">Configura un envío masivo de email marketing</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la campaña <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Asunto del email <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Segmento de destinatarios</label>
                            <select name="segment" class="form-select">
                                <option value="all">Todos los clientes</option>
                                @foreach(($segmentCounts ?? []) as $key => $count)
                                    <option value="{{ $key }}">{{ $key }} ({{ $count }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Contenido</label>
                            <textarea name="content" class="form-control" rows="8"></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Crear campaña</button>
                        <a href="{{ route('ecommerce.email-campaigns.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Email marketing</h6>
                    <p class="card-text text-muted mb-0">
                        Envía campañas masivas a segmentos específicos de tus clientes.
                        Incluye tracking de aperturas y clicks.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
