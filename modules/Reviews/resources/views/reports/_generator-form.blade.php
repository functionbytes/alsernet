<form action="{{ route('reviews.reports.generate') }}" method="POST">
    @csrf

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="report_type" class="form-label">Tipo de reporte</label>
            <select class="select2" name="report_type" id="report_type" class="form-select @error('report_type') is-invalid @enderror" required>
                <option value="">Seleccionar tipo...</option>
                <option value="location_summary">Resumen por ubicación</option>
                <option value="period_analysis">Análisis de periodo</option>
                <option value="comparison">Comparación de periodos</option>
                <option value="response_performance">Rendimiento de respuestas</option>
                <option value="detailed_export">Exportación detallada</option>
            </select>
            @error('report_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="format" class="form-label">Formato</label>
            <select class="select2" name="format" id="format" class="form-select @error('format') is-invalid @enderror" required>
                <option value="excel">Excel (.xlsx)</option>
                <option value="csv">CSV (.csv)</option>
                <option value="pdf">PDF (.pdf)</option>
            </select>
            @error('format')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Standard date fields (for most report types) -->
    <div class="row date-fields">
        <div class="col-md-6 mb-3">
            <label for="date_from" class="form-label">Fecha inicio</label>
            <input type="date" name="date_from" id="date_from" class="form-control @error('date_from') is-invalid @enderror" value="{{ now()->subMonth()->toDateString() }}">
            @error('date_from')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="date_to" class="form-label">Fecha fin</label>
            <input type="date" name="date_to" id="date_to" class="form-control @error('date_to') is-invalid @enderror" value="{{ now()->toDateString() }}">
            @error('date_to')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Comparison specific fields -->
    <div class="row comparison-fields" style="display: none;">
        <div class="col-12 mb-2">
            <h6>Periodo 1</h6>
        </div>
        <div class="col-md-6 mb-3">
            <label for="period1_from" class="form-label">Fecha inicio</label>
            <input type="date" name="period1_from" id="period1_from" class="form-control @error('period1_from') is-invalid @enderror" value="{{ now()->subMonths(2)->toDateString() }}">
            @error('period1_from')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="period1_to" class="form-label">Fecha fin</label>
            <input type="date" name="period1_to" id="period1_to" class="form-control @error('period1_to') is-invalid @enderror" value="{{ now()->subMonth()->toDateString() }}">
            @error('period1_to')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 mb-2">
            <h6>Periodo 2</h6>
        </div>
        <div class="col-md-6 mb-3">
            <label for="period2_from" class="form-label">Fecha inicio</label>
            <input type="date" name="period2_from" id="period2_from" class="form-control @error('period2_from') is-invalid @enderror" value="{{ now()->subMonth()->toDateString() }}">
            @error('period2_from')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="period2_to" class="form-label">Fecha fin</label>
            <input type="date" name="period2_to" id="period2_to" class="form-control @error('period2_to') is-invalid @enderror" value="{{ now()->toDateString() }}">
            @error('period2_to')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Period analysis specific fields -->
    <div class="row period-analysis-fields" style="display: none;">
        <div class="col-md-6 mb-3">
            <label for="group_by" class="form-label">Agrupar por</label>
            <select class="select2" name="group_by" id="group_by" class="form-select">
                <option value="day">Día</option>
                <option value="week">Semana</option>
                <option value="month" selected>Mes</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="location_ids" class="form-label">Ubicaciones (opcional)</label>
            <select class="select2" name="location_ids[]" id="location_ids" class="form-select" multiple size="5">
                <option value="">Todas las ubicaciones</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Mantener presionado Ctrl/Cmd para seleccionar múltiples ubicaciones. Dejar vacío para incluir todas.</small>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-play"></i> Generar reporte
            </button>
            <button type="reset" class="btn btn-light">
                <i class="fas fa-undo"></i> Limpiar
            </button>
        </div>
    </div>
</form>
