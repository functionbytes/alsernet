@extends('layouts.theme')

@section('title', 'Respuestas: ' . $survey->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Respuestas de encuesta'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $survey->name }}</h5>
                        <p class="small mb-0 text-muted">
                            Disparo: {{ \Modules\Helpdesk\Models\Survey::TRIGGER_TYPES[$survey->trigger_type] ?? $survey->trigger_type }}
                        </p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.surveys.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($responses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Respondida</th>
                                    <th scope="col">Respuestas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($responses as $response)
                                    <tr>
                                        <td>
                                            @if($response->customer)
                                                <span class="fw-semibold">{{ $response->customer->name ?? '—' }}</span>
                                                @if($response->customer->email)
                                                    <br><small class="text-muted">{{ $response->customer->email }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Anonimo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $response->answered_at ? $response->answered_at->format('d/m/Y H:i') : '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if(is_array($response->answers))
                                                <small class="text-muted">{{ count($response->answers) }} respuestas</small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">Sin respuestas aun</h5>
                        <p class="text-muted mb-0">Esta encuesta no ha recibido respuestas todavia</p>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($responses->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $responses->firstItem() }} - {{ $responses->lastItem() }} de {{ $responses->total() }}
                        </div>
                        <div>
                            {{ $responses->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

@endsection
