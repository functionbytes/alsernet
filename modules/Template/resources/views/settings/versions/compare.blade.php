@extends('layouts.theme')

@section('page_title', "Comparar versiones")

@section('content')
    @include('core::components.card', ['title' => 'Comparar versiones'])

    <div class="page-wrapper">
        <div class="container-xl">
            @include('core::components.alerts')

            <div class="card">
                <div class="card-header border-bottom">
                    <h3 class="card-title">
                        <i class="fas fa-code-compare me-2"></i>
                        Comparación: v{{ $v1->version }} vs v{{ $v2->version }}
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        {{-- Versión 1 --}}
                        <div class="col-lg-6">
                            <div class="card border-info">
                                <div class="card-header bg-light-info">
                                    <h5 class="card-title">Versión {{ $v1->version }}</h5>
                                    <small class="text-muted">{{ $v1->created_at->format('d/m/Y H:i') }}</small>
                                </div>

                                <div class="card-body">
                                    @foreach($comparison['differences'] as $field => $diff)
                                        @if($diff['changed'])
                                            <div class="mb-3 pb-3 border-bottom">
                                                <strong>{{ $diff['label'] }}</strong>
                                                <div class="text-danger mt-2">
                                                    <code class="text-break">
                                                        {{ substr($diff['old_value'], 0, 200) }}{{ strlen($diff['old_value']) > 200 ? '...' : '' }}
                                                    </code>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Versión 2 --}}
                        <div class="col-lg-6">
                            <div class="card border-success">
                                <div class="card-header bg-light-success">
                                    <h5 class="card-title">Versión {{ $v2->version }}</h5>
                                    <small class="text-muted">{{ $v2->created_at->format('d/m/Y H:i') }}</small>
                                </div>

                                <div class="card-body">
                                    @foreach($comparison['differences'] as $field => $diff)
                                        @if($diff['changed'])
                                            <div class="mb-3 pb-3 border-bottom">
                                                <strong>{{ $diff['label'] }}</strong>
                                                <div class="text-success mt-2">
                                                    <code class="text-break">
                                                        {{ substr($diff['new_value'], 0, 200) }}{{ strlen($diff['new_value']) > 200 ? '...' : '' }}
                                                    </code>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Resumen de cambios --}}
                    @if($comparison['has_changes'])
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>{{ collect($comparison['differences'])->where('changed', true)->count() }} campo(s) modificado(s)</strong>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay diferencias entre estas versiones
                        </div>
                    @endif

                    {{-- Tabla de diferencias detallada --}}
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Campo</th>
                                <th>Versión {{ $v1->version }}</th>
                                <th>Versión {{ $v2->version }}</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comparison['differences'] as $field => $diff)
                                <tr>
                                    <td><strong>{{ $diff['label'] }}</strong></td>
                                    <td>
                                        <code class="text-break small">
                                            {{ substr($diff['old_value'], 0, 100) }}{{ strlen($diff['old_value']) > 100 ? '...' : '' }}
                                        </code>
                                    </td>
                                    <td>
                                        <code class="text-break small">
                                            {{ substr($diff['new_value'], 0, 100) }}{{ strlen($diff['new_value']) > 100 ? '...' : '' }}
                                        </code>
                                    </td>
                                    <td class="text-center">
                                        @if($diff['changed'])
                                            <span class="badge bg-warning">Cambiado</span>
                                        @else
                                            <span class="badge bg-secondary">Sin cambios</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer border-top">
                    <div class="btn-list">
                        <a href="{{ route('settings.templates.versions.index', $template) }}"
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al historial
                        </a>

                        <a href="{{ route('settings.templates.show', $template) }}"
                           class="btn btn-outline-secondary">
                            Ver template actual
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
