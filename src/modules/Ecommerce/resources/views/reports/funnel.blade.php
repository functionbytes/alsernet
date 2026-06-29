@extends('layouts.theme')

@section('title', 'Embudo de conversión')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Reportes'])
@endsection

@section('content')
    @include('core::components.alerts')

    {{-- Report navigation --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <ul class="nav nav-pills nav-fill">
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.index') }}">Resumen</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.comparison') }}">Comparativa</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.funnel') }}">Embudo</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.customer-ltv') }}">LTV clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.abandoned-carts') }}">Carritos abandonados</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
            </ul>
        </div>
    </div>

    {{-- Date filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small mb-1">Desde</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label small mb-1">Hasta</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
            </form>
        </div>
    </div>

    {{-- Funnel steps --}}
    <div class="card">
        <div class="card-header p-4 border-bottom">
            <h6 class="fw-bold mb-0">Embudo de conversion</h6>
        </div>
        <div class="card-body">
            @foreach ($funnel as $idx => $step)
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>{{ $step['label'] }}</strong>
                        <span>
                            <strong>{{ number_format($step['value']) }}</strong>
                            @if ($idx > 0)
                                <small class="text-muted ms-2">({{ $step['rate'] }}% del paso anterior)</small>
                            @endif
                        </span>
                    </div>
                    <div class="progress" style="height:28px">
                        <div
                            class="progress-bar bg-primary"
                            role="progressbar"
                            style="width:{{ $idx === 0 ? 100 : $step['rate'] }}%"
                        >
                            {{ $idx === 0 ? '100%' : $step['rate'].'%' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
