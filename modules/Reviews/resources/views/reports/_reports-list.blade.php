<div class="table-responsive">
    <table class="table table-hover" id="reports-table">
        <thead>
            <tr>
                <th>Nombre archivo</th>
                <th>Tipo</th>
                <th>Formato</th>
                <th>Estado</th>
                <th>Tamaño</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentReports as $report)
                <tr>
                    <td>{{ $report->filename }}</td>
                    <td>
                        @switch($report->report_type)
                            @case('location_summary')
                                Resumen por ubicación
                                @break
                            @case('period_analysis')
                                Análisis de periodo
                                @break
                            @case('comparison')
                                Comparación
                                @break
                            @case('response_performance')
                                Rendimiento de respuestas
                                @break
                            @case('detailed_export')
                                Exportación detallada
                                @break
                            @default
                                {{ $report->report_type }}
                        @endswitch
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ strtoupper($report->format) }}</span>
                    </td>
                    <td>
                        @if($report->isExpired())
                            <span class="badge bg-warning">Expirado</span>
                        @elseif($report->isCompleted())
                            <span class="badge bg-success">Completado</span>
                        @elseif($report->isFailed())
                            <span class="badge bg-danger">Fallido</span>
                        @elseif($report->isProcessing())
                            <span class="badge bg-info">Procesando</span>
                        @else
                            <span class="badge bg-secondary">{{ $report->status }}</span>
                        @endif
                    </td>
                    <td>{{ $report->getFileSizeFormatted() }}</td>
                    <td>{{ $report->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        @if($report->isCompleted() && !$report->isExpired())
                            <a href="{{ $report->getDownloadUrl() }}" class="btn btn-sm btn-primary" title="Descargar">
                                <i class="fas fa-download"></i>
                            </a>
                        @else
                            <button class="btn btn-sm btn-secondary" disabled title="No disponible">
                                <i class="fas fa-download"></i>
                            </button>
                        @endif

                        <form action="{{ route('reviews.reports.destroy', $report) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        No hay reportes generados en los últimos 30 días.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
