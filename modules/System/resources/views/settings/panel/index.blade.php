@extends('theme::layouts.theme')

@section('title', 'Configuración del sistema')

@section('content')
<div class="px-3">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:48px;height:48px;background:#13C67222">
            <i class="fas fa-cogs" style="color:#13C672;font-size:1.3rem"></i>
        </div>
        <div>
            <h4 class="mb-0 fw-semibold">Configuración del sistema</h4>
            <p class="text-muted mb-0 small">Administra todas las configuraciones desde un solo lugar</p>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <div class="input-group" style="max-width:420px">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text"
                   class="form-control border-start-0"
                   id="settings-search"
                   placeholder="Buscar configuración..."
                   autocomplete="off">
        </div>
    </div>

    {{-- Groups --}}
    @foreach($groups as $groupKey => $group)
        @php
            $visibleItems = array_filter(
                $group['items'],
                fn($item) => \Illuminate\Support\Facades\Route::has($item['route'])
            );
        @endphp
        @if(!empty($visibleItems))
        <div class="mb-5 settings-group" data-group="{{ $groupKey }}">
            <h6 class="text-uppercase fw-semibold small mb-3 d-flex align-items-center gap-2"
                style="color:#6c757d;letter-spacing:.06em">
                <i class="{{ $group['icon'] }} text-{{ $group['color'] }}"></i>
                {{ $group['label'] }}
            </h6>
            <div class="row g-3">
                @foreach($visibleItems as $item)
                <div class="col-xl-3 col-lg-4 col-md-6 settings-item"
                     data-search="{{ strtolower($item['label'] . ' ' . ($item['description'] ?? '')) }}">
                    <div class="card h-100 settings-card">
                        <div class="card-body">
                            <span class="bg-{{ $group['color'] }} rounded-3 p-3 text-white d-inline-block">
                                <i class="{{ $item['icon'] ?? $group['icon'] }} fs-4"></i>
                            </span>
                            <h6 class="card-title mt-3 mb-1 fw-semibold d-flex align-items-center gap-2">
                                {{ $item['label'] }}
                                @if($item['badge'] ?? null)
                                    <span class="badge bg-success" style="font-size:.6rem;padding:.2em .45em">{{ $item['badge'] }}</span>
                                @endif
                            </h6>
                            @if($item['description'] ?? null)
                            <p class="text-muted lh-base mb-3" style="font-size:.8rem">{{ $item['description'] }}</p>
                            @else
                            <p class="mb-3"></p>
                            @endif
                            <a href="{{ route($item['route']) }}"
                               class="stretched-link fw-medium text-{{ $group['color'] }} d-flex align-items-center gap-1"
                               style="font-size:.85rem;text-decoration:none">
                                Configurar
                                <i class="fas fa-chevron-right" style="font-size:.7rem"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach

    {{-- Empty state --}}
    <div id="settings-empty" class="d-none text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted mb-0">
            No se encontraron configuraciones para
            "<strong id="search-term"></strong>"
        </p>
    </div>

</div>
@endsection

@push('css')
<style>
.settings-card {
    transition: transform .15s ease, box-shadow .15s ease;
    cursor: pointer;
}
.settings-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    var searchInput = document.getElementById('settings-search');
    var emptyState  = document.getElementById('settings-empty');
    var searchTerm  = document.getElementById('search-term');

    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var visibleCount = 0;

        document.querySelectorAll('.settings-item').forEach(function (item) {
            var text    = item.dataset.search || '';
            var visible = !q || text.includes(q);
            item.style.display = visible ? '' : 'none';
            if (visible) { visibleCount++; }
        });

        document.querySelectorAll('.settings-group').forEach(function (group) {
            var hasVisible = Array.from(group.querySelectorAll('.settings-item'))
                .some(function (item) { return item.style.display !== 'none'; });
            group.style.display = hasVisible ? '' : 'none';
        });

        emptyState.classList.toggle('d-none', visibleCount > 0);
        if (!visibleCount) { searchTerm.textContent = this.value; }
    });
}());
</script>
@endpush
