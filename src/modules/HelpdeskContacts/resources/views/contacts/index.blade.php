@extends('layouts.theme')

@section('title', 'Contactos')

@push('css')
    <style>
        .contacts-avatar { width: 36px; height: 36px; }
        #filters-panel { visibility: visible; }
    </style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Contactos'])
@endsection

@section('content')

@php
    function sortUrl(string $col, string $currentSort, string $currentDir): string {
        $newDir = ($currentSort === $col && $currentDir === 'desc') ? 'asc' : 'desc';
        return request()->fullUrlWithQuery(['sort' => $col, 'direction' => $newDir, 'page' => null]);
    }
    function sortIcon(string $col, string $currentSort, string $currentDir): string {
        if ($currentSort !== $col) { return '<i class="fas fa-sort text-muted"></i>'; }
        return $currentDir === 'asc'
            ? '<i class="fas fa-sort-up"></i>'
            : '<i class="fas fa-sort-down"></i>';
    }
@endphp

    @include('core::components.alerts')

    <div class="card">

        <div class="card-header p-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Contactos</h5>
                    <p class="small mb-0 text-muted">Vista 360 de los clientes del helpdesk</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-muted">{{ number_format($customers->total()) }} total</span>
                    <a href="{{ route('contacts.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv"></i> Exportar CSV
                    </a>
                    @can('contacts.create')
                        <a href="{{ route('contacts.import') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-file-import"></i> Importar
                        </a>
                    @endcan
                    <a href="{{ route('contacts.reports') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                </div>
            </div>
        </div>

        {{-- Advanced filters --}}
        @php
            $activeFilters = collect(['channel', 'last_seen', 'verified', 'banned'])
                ->filter(fn ($k) => filled(request($k)))
                ->count();
        @endphp
        <div class="card-body border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <button class="btn btn-sm btn-outline-secondary" type="button"
                        data-bs-toggle="collapse" data-bs-target="#filters-panel"
                        aria-expanded="{{ $activeFilters > 0 ? 'true' : 'false' }}">
                    <i class="fas fa-sliders me-1"></i> Filtros
                    @if($activeFilters > 0)
                        <span class="badge bg-primary ms-1">{{ $activeFilters }}</span>
                    @endif
                </button>
                @if($activeFilters > 0)
                    <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-link text-muted p-0">
                        <i class="fas fa-xmark me-1"></i> Limpiar
                    </a>
                @endif
            </div>
            <div class="collapse {{ $activeFilters > 0 ? 'show' : '' }}" id="filters-panel">
                <form method="GET" action="{{ route('contacts.index') }}" id="contactsFilterForm">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <div class="row g-2">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <select name="channel" class="form-select form-select-sm">
                                <option value="">Todos los canales</option>
                                <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
                                <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="facebook" {{ request('channel') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                                <option value="instagram" {{ request('channel') === 'instagram' ? 'selected' : '' }}>Instagram</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <select name="last_seen" class="form-select form-select-sm">
                                <option value="">Cualquier fecha</option>
                                <option value="today" {{ request('last_seen') === 'today' ? 'selected' : '' }}>Hoy</option>
                                <option value="week" {{ request('last_seen') === 'week' ? 'selected' : '' }}>Esta semana</option>
                                <option value="month" {{ request('last_seen') === 'month' ? 'selected' : '' }}>Este mes</option>
                                <option value="inactive" {{ request('last_seen') === 'inactive' ? 'selected' : '' }}>Sin actividad (+30 días)</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <select name="verified" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="yes" {{ request('verified') === 'yes' ? 'selected' : '' }}>Solo verificados</option>
                                <option value="no" {{ request('verified') === 'no' ? 'selected' : '' }}>No verificados</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <select name="banned" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="yes" {{ request('banned') === 'yes' ? 'selected' : '' }}>Suspendidos</option>
                                <option value="no" {{ request('banned') === 'no' ? 'selected' : '' }}>No suspendidos</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Aplicar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Search bar --}}
        <div class="card-body border-bottom py-3">
            <form method="GET" action="{{ route('contacts.index') }}" id="contactsSearchForm">
                @foreach(request()->only(['channel', 'last_seen', 'verified', 'banned']) as $key => $value)
                    @if(filled($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="d-flex gap-2">
                    <input type="text" name="q" class="form-control"
                           placeholder="Buscar por nombre, email o teléfono..."
                           value="{{ request('q') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-magnifying-glass"></i>
                    </button>
                    @if(filled(request('q')))
                        <a href="{{ route('contacts.index', request()->except('q')) }}" class="btn btn-light">
                            <i class="fas fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="card-body border-bottom py-2 px-3">
            <div id="bulk-bar" class="d-none d-flex align-items-center gap-2">
                <span id="bulk-count" class="small fw-semibold text-muted"></span>
                <button class="btn btn-sm btn-outline-secondary" data-bulk-action="unban">Reactivar</button>
                <button class="btn btn-sm btn-outline-warning" data-bulk-action="ban">Suspender</button>
                <button class="btn btn-sm btn-outline-danger" data-bulk-action="delete">Eliminar</button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3"><input type="checkbox" id="check-all" class="form-check-input"></th>
                            <th>
                                <a href="{{ sortUrl('name', $sort, $direction) }}" class="text-dark text-decoration-none">
                                    Contacto {!! sortIcon('name', $sort, $direction) !!}
                                </a>
                            </th>
                            <th>Teléfono</th>
                            <th>Canales</th>
                            <th>
                                <a href="{{ sortUrl('last_seen_at', $sort, $direction) }}" class="text-dark text-decoration-none">
                                    Última visita {!! sortIcon('last_seen_at', $sort, $direction) !!}
                                </a>
                            </th>
                            <th class="text-center">
                                <a href="{{ sortUrl('total_conversations', $sort, $direction) }}" class="text-dark text-decoration-none">
                                    Conv. {!! sortIcon('total_conversations', $sort, $direction) !!}
                                </a>
                            </th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            @php
                                $avatarUrl = method_exists($customer, 'getAvatarUrl') ? $customer->getAvatarUrl() : ($customer->avatar_url ?? null);
                                $initials = strtoupper(mb_substr($customer->name ?? '?', 0, 1));
                                $convCount = $customer->total_conversations ?? 0;
                            @endphp
                            <tr>
                                <td class="ps-3"><input type="checkbox" class="form-check-input contact-check" value="{{ $customer->id }}"></td>
                                <td>
                                    <a href="{{ route('contacts.show', $customer) }}"
                                       class="d-flex align-items-center gap-3 text-decoration-none text-body">
                                        @if($avatarUrl && ! str_contains($avatarUrl, 'ui-avatars.com'))
                                            <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}"
                                                 width="36" height="36" loading="lazy"
                                                 class="contacts-avatar rounded-circle object-fit-cover">
                                        @else
                                            <span class="contacts-avatar rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center fw-semibold">{{ $initials }}</span>
                                        @endif
                                        <span>
                                            <span class="d-block fw-semibold">
                                                {{ $customer->name ?: 'Sin nombre' }}
                                                @if($customer->email_verified_at ?? false)
                                                    <i class="fas fa-circle-check text-success ms-1"></i>
                                                @endif
                                                @if($customer->banned_at ?? false)
                                                    <span class="badge bg-danger-subtle text-danger ms-1">Suspendido</span>
                                                @endif
                                            </span>
                                            <span class="d-block small text-muted">{{ $customer->email ?: '—' }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="small">{{ $customer->phone ?: ($customer->whatsapp_phone ?? '—') }}</td>
                                <td class="small">
                                    <div class="d-flex gap-1">
                                        @if($customer->email)
                                            <span class="text-muted" title="Email"><i class="fas fa-envelope"></i></span>
                                        @endif
                                        @if($customer->whatsapp_phone)
                                            <span class="text-success" title="WhatsApp"><i class="fab fa-whatsapp"></i></span>
                                        @endif
                                        @if($customer->facebook_psid)
                                            <span class="text-primary" title="Facebook Messenger"><i class="fab fa-facebook-messenger"></i></span>
                                        @endif
                                        @if($customer->instagram_id)
                                            <span style="color:#c13584" title="Instagram"><i class="fab fa-instagram"></i></span>
                                        @endif
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    @if($customer->last_seen_at ?? false)
                                        {{ $customer->last_seen_at->locale('es')->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($convCount > 0)
                                        <span class="badge bg-primary-subtle text-primary">{{ $convCount }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('contacts.show', $customer) }}">Ver ficha 360</a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-users fa-2x text-muted mb-3 d-block"></i>
                                    <p class="fw-semibold mb-1">Sin contactos</p>
                                    <p class="small text-muted mb-0">
                                        @if(filled(request('q')))
                                            No se encontraron resultados para "{{ request('q') }}"
                                        @else
                                            Aún no hay contactos registrados
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($customers->hasPages() || $customers->total() > 15)
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0">Mostrar:</label>
                    <select class="form-select form-select-sm w-auto" id="per-page-select">
                        @foreach([15, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="small text-muted">de {{ number_format($customers->total()) }}</span>
                </div>
                <div>{{ $customers->appends(request()->input())->links() }}</div>
            </div>
        @endif

    </div>

@endsection

@push('scripts')
<script>
$(function () {
    var bulkUrl = '{{ route("contacts.bulk-action") }}';

    $('#check-all').on('change', function () {
        $('.contact-check').prop('checked', this.checked);
        updateBulkBar();
    });

    $(document).on('change', '.contact-check', updateBulkBar);

    function updateBulkBar() {
        var count = $('.contact-check:checked').length;
        if (count > 0) {
            $('#bulk-bar').removeClass('d-none');
            $('#bulk-count').text(count + ' seleccionados');
        } else {
            $('#bulk-bar').addClass('d-none');
            $('#check-all').prop('checked', false);
        }
    }

    $('#per-page-select').on('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });

    $(document).on('click', '[data-bulk-action]', function () {
        var action = $(this).data('bulk-action');
        var ids = $('.contact-check:checked').map(function () { return parseInt(this.value); }).get();
        if (!ids.length) { return; }
        if (action === 'delete' && !confirm('¿Eliminar ' + ids.length + ' contactos? Esta acción no se puede deshacer.')) { return; }
        $.ajax({
            url: bulkUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            contentType: 'application/json',
            data: JSON.stringify({ action: action, ids: ids }),
        }).done(function (resp) {
            toastr.success(resp.message || 'Acción aplicada');
            setTimeout(function () { location.reload(); }, 800);
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Error al ejecutar la acción');
        });
    });
});
</script>
@endpush
