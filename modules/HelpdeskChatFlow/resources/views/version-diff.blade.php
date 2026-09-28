@extends('layouts.theme')

@section('title', 'Comparar versión #' . $base->id . ' — ' . $chatFlow->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Comparar versión'])
@endsection

@section('content')

    @include('core::components.alerts')

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('chatflow.index') }}">Chat flows</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chatflow.edit', $chatFlow) }}">{{ $chatFlow->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chatflow.versions', $chatFlow) }}">Versiones</a></li>
            <li class="breadcrumb-item active" aria-current="page">Comparar</li>
        </ol>
    </nav>

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h6 class="fw-bold mb-1">Versión #{{ $base->id }} — {{ $base->name }}</h6>
                <p class="small text-muted mb-0">
                    Comparando contra
                    @if($against)
                        la versión #{{ $against->id }} ({{ $against->name }})
                    @else
                        el borrador actual del flow
                    @endif
                </p>
            </div>
            <form action="{{ route('chatflow.versions.diff', [$chatFlow, $base->id]) }}" method="GET" class="d-flex align-items-center gap-2">
                <label for="against" class="form-label small mb-0 text-muted">Comparar contra</label>
                <select name="against" id="against" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" {{ $against ? '' : 'selected' }}>Borrador actual</option>
                    @foreach($otherVersions as $option)
                        <option value="{{ $option->id }}" {{ $against && $against->id === $option->id ? 'selected' : '' }}>
                            #{{ $option->id }} — {{ $option->name }} ({{ $option->created_at->diffForHumans() }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Nodos añadidos <span class="badge bg-success-subtle text-success">{{ count($diff['added']) }}</span></h6>
                </div>
                <div class="card-body">
                    @forelse($diff['added'] as $node)
                        <div class="mb-2 pb-2 border-bottom">
                            <span class="fw-semibold">{{ $node['label'] ?? $node['id'] ?? '—' }}</span>
                            <span class="text-muted small ms-1">({{ $node['type'] ?? 'sin tipo' }} · id: {{ $node['id'] ?? '—' }})</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Sin nodos añadidos.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Nodos eliminados <span class="badge bg-brand-subtle text-brand">{{ count($diff['removed']) }}</span></h6>
                </div>
                <div class="card-body">
                    @forelse($diff['removed'] as $node)
                        <div class="mb-2 pb-2 border-bottom">
                            <span class="fw-semibold">{{ $node['label'] ?? $node['id'] ?? '—' }}</span>
                            <span class="text-muted small ms-1">({{ $node['type'] ?? 'sin tipo' }} · id: {{ $node['id'] ?? '—' }})</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Sin nodos eliminados.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Nodos modificados <span class="badge bg-light text-muted border">{{ count($diff['changed']) }}</span></h6>
                </div>
                <div class="card-body">
                    @forelse($diff['changed'] as $node)
                        <div class="mb-3 pb-3 border-bottom">
                            <p class="fw-semibold mb-2">{{ $node['label'] }} <span class="text-muted small">(id: {{ $node['id'] }})</span></p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Campo</th>
                                            <th>Antes</th>
                                            <th>Después</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($node['fields'] as $field => $values)
                                            <tr>
                                                <td class="text-muted">{{ $field }}</td>
                                                <td><code>{{ $values['old'] }}</code></td>
                                                <td><code>{{ $values['new'] }}</code></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Sin nodos modificados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

@endsection
