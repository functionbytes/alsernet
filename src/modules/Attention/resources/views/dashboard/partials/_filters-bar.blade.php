{{-- Filters Bar --}}
@php
    $currentFrom = $from->format('Y-m-d');
    $currentTo   = $to->format('Y-m-d');
    $today       = now()->format('Y-m-d');
    $activeRange = match(true) {
        $currentFrom === $today && $currentTo === $today
            => 'today',
        $currentFrom === now()->subDays(7)->format('Y-m-d') && $currentTo === $today
            => 'last_7_days',
        $currentFrom === now()->subDays(30)->format('Y-m-d') && $currentTo === $today
            => 'last_30_days',
        $currentFrom === now()->copy()->startOfMonth()->format('Y-m-d') && $currentTo === $today
            => 'this_month',
        $currentFrom === now()->copy()->subMonth()->startOfMonth()->format('Y-m-d')
            && $currentTo === now()->copy()->subMonth()->endOfMonth()->format('Y-m-d')
            => 'last_month',
        $currentFrom === now()->copy()->startOfYear()->format('Y-m-d') && $currentTo === $today
            => 'this_year',
        default => 'custom',
    };
@endphp

<form method="GET" action="{{ route('attention.dashboard') }}" id="dashboard-filter-form">
    <div class="card card-body mb-4 border-0 shadow-sm py-2">
        <div class="d-flex align-items-center flex-wrap gap-3">

            {{-- Period nav-pills --}}
            <ul class="nav nav-pills gap-1 flex-nowrap overflow-auto mb-0" id="rangePills">
                @php
                    $pills = [
                        'today'      => ['label' => 'Hoy',          'from' => $today,                                             'to' => $today],
                        'last_7_days'=> ['label' => '7 días',       'from' => now()->subDays(7)->format('Y-m-d'),                 'to' => $today],
                        'last_30_days'=>['label' => '30 días',      'from' => now()->subDays(30)->format('Y-m-d'),                'to' => $today],
                        'this_month' => ['label' => 'Este mes',     'from' => now()->copy()->startOfMonth()->format('Y-m-d'),    'to' => $today],
                        'last_month' => ['label' => 'Mes anterior', 'from' => now()->copy()->subMonth()->startOfMonth()->format('Y-m-d'), 'to' => now()->copy()->subMonth()->endOfMonth()->format('Y-m-d')],
                        'this_year'  => ['label' => 'Este año',     'from' => now()->copy()->startOfYear()->format('Y-m-d'),     'to' => $today],
                    ];
                @endphp
                @foreach($pills as $range => $pill)
                    <li class="nav-item flex-shrink-0">
                        <a class="nav-link py-1 px-3 small fw-semibold {{ $activeRange === $range ? 'active' : 'text-muted' }}"
                           href="#" data-range="{{ $range }}"
                           data-from="{{ $pill['from'] }}" data-to="{{ $pill['to'] }}">
                            {{ $pill['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Divider --}}
            <div class="d-none d-md-block flex-shrink-0" style="width:1px;height:28px;background:#e9ecef;"></div>

            {{-- Date range input-group --}}
            <div class="d-flex align-items-center flex-shrink-0">
                <div class="input-group input-group" style="width:240px;">
                    <input type="text" id="filter-daterange"
                           class="form-control form-control daterange-dashboard"
                           value="{{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }}"
                           readonly style="cursor:pointer;">
                    <span class="input-group-text bg-info" style="cursor:pointer;" id="filter-daterange-icon">
                        <i class="fas fa-calendar-alt text-white" style="font-size:0.75rem;"></i>
                    </span>
                </div>
                <input type="hidden" name="from" id="filter-from" value="{{ $currentFrom }}">
                <input type="hidden" name="to"   id="filter-to"   value="{{ $currentTo }}">
            </div>

            {{-- Actions dropdown --}}
            <div class="ms-auto flex-shrink-0">
                <div class="dropdown">
                    <a href="javascript:void(0)"
                       class="d-flex align-items-center justify-content-center rounded-circle text-muted"
                       style="width:30px;height:30px;background:#f5f6f8;"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" id="refreshBtn">Actualizar datos</a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="window.print()">Imprimir</a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</form>
