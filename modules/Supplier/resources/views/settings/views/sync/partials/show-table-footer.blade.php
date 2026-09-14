{{--
    Footer compartido para las tablas del detalle de batch (logs / fallos).
    Espera: $paginator (LengthAwarePaginator), $batch, $tab ('logs'|'failures')
--}}
<div class="card-footer bg-white border-top py-2">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            @if($paginator->total() > 0)
                <span class="text-muted small">
                    Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}
                </span>
            @endif
            <form method="GET" action="{{ route('settings.suppliers.sync.show', $batch->id) }}" class="d-inline-flex align-items-center gap-1 mb-0">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @foreach(request()->except(['per_page', 'page', 'logs_page', 'failures_page', 'tab']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label class="text-muted small mb-0">Por página:</label>
                <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                    @foreach([10, 20, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ (int) request('per_page', 20) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                    <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                </select>
            </form>
        </div>
        @if($paginator->hasPages())
            <nav>{{ $paginator->appends(['tab' => $tab])->links('pagination::bootstrap-5') }}</nav>
        @endif
    </div>
</div>
