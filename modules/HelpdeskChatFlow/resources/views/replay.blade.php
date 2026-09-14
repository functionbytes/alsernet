@extends('layouts.theme')

@section('title', 'Reproducción — ' . $chatFlow->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Reproducción de sesión'])
@endsection

@section('content')

    @include('core::components.alerts')

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('chatflow.index') }}">Chat flows</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chatflow.edit', $chatFlow) }}">{{ $chatFlow->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chatflow.sessions', $chatFlow) }}">Sesiones</a></li>
            <li class="breadcrumb-item active" aria-current="page">Reproducción #{{ $session->id }}</li>
        </ol>
    </nav>

    @php
        $statusChanged = $result['final_status'] !== $result['original_status'];
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="fw-bold mb-2">Reproducción contra el flow actual</h6>
            <p class="small text-muted mb-3">Se reenvían los {{ count($result['inputs']) }} mensajes del cliente de la sesión original al flow tal y como está ahora.</p>
            <div class="d-flex gap-3 flex-wrap">
                <div>
                    <span class="text-muted small d-block">Estado original</span>
                    <span class="badge bg-secondary-subtle text-secondary">{{ $result['original_status'] }}</span>
                </div>
                <div>
                    <span class="text-muted small d-block">Estado al reproducir</span>
                    <span class="badge bg-{{ $statusChanged ? 'warning' : 'success' }}-subtle text-{{ $statusChanged ? 'warning' : 'success' }}">{{ $result['final_status'] }}</span>
                </div>
            </div>
            @if($statusChanged)
                <div class="alert alert-warning mt-3 mb-0 py-2 small">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    El resultado cambió respecto a la sesión original. Revisa si una edición reciente alteró este recorrido.
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body chatflow-replay">
            @forelse($result['timeline'] as $entry)
                @if(($entry['role'] ?? '') === 'customer')
                    <div class="d-flex justify-content-end mb-2">
                        <div class="replay-bubble replay-customer">{{ $entry['text'] }}</div>
                    </div>
                @else
                    @foreach($entry['messages'] ?? [] as $msg)
                        @if(!empty($msg['text']))
                            <div class="d-flex justify-content-start mb-2">
                                <div class="replay-bubble replay-bot {{ ($msg['system'] ?? false) ? 'replay-system' : '' }}">{!! nl2br(e($msg['text'])) !!}</div>
                            </div>
                        @endif
                    @endforeach
                @endif
            @empty
                <p class="text-muted text-center py-4">Esta sesión no tiene mensajes del cliente para reproducir.</p>
            @endforelse
        </div>
    </div>

@endsection

@push('css')
<style>
.chatflow-replay { background: #f3f4f6; border-radius: 8px; padding: 16px; }
.replay-bubble { max-width: 75%; padding: 8px 12px; border-radius: 12px; font-size: 13.5px; white-space: pre-wrap; word-break: break-word; }
.replay-customer { background: #90bb13; color: #fff; border-bottom-right-radius: 3px; }
.replay-bot { background: #fff; color: #1e293b; border: 1px solid #e5e7eb; border-bottom-left-radius: 3px; }
.replay-system { background: #fef9c3; color: #854d0e; border-color: #fde68a; font-size: 12px; }
</style>
@endpush
