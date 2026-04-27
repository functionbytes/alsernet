@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Mis busquedas guardadas')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> <a href="{{ route('account.dashboard') }}">{{ __('Mi cuenta') }}</a>
            <span></span> {{ __('Busquedas guardadas') }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">

            {{-- Sidebar --}}
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i>{{ __('Dashboard') }}
                        </a>
                        <a href="{{ route('account.orders') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-box me-2"></i>{{ __('Mis ordenes') }}
                        </a>
                        <a href="{{ route('account.profile') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i>{{ __('Mi perfil') }}
                        </a>
                        <a href="{{ route('account.addresses') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt me-2"></i>{{ __('Mis direcciones') }}
                        </a>
                        <a href="{{ route('account.saved-searches.index') }}" class="list-group-item list-group-item-action active">
                            <i class="fas fa-bell me-2"></i>{{ __('Busquedas guardadas') }}
                        </a>
                        <a href="{{ route('ecommerce.logout') }}"
                           class="list-group-item list-group-item-action text-danger"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i>{{ __('Cerrar sesion') }}
                        </a>
                        <form id="logout-form" action="{{ route('ecommerce.logout') }}" method="POST" class="d-none">@csrf</form>
                    </div>
                </div>
            </div>

            {{-- Main content --}}
            <div class="col-lg-9">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">{{ __('Busquedas guardadas') }}</h6>
                        <small class="text-muted">{{ __('Te avisaremos por correo cuando aparezcan nuevos productos que coincidan.') }}</small>
                    </div>
                    <div class="card-body">
                        @if($searches->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3 d-block opacity-25"></i>
                                <p class="mb-1">{{ __('Aun no tienes busquedas guardadas.') }}</p>
                                <p class="small">{{ __('Cuando uses los filtros del catalogo, podras guardarlos para recibir notificaciones.') }}</p>
                                <a href="{{ route('shop.index') }}" class="btn btn-primary btn-sm mt-2">{{ __('Ir a la tienda') }}</a>
                            </div>
                        @else
                            <div class="row g-3">
                                @foreach($searches as $search)
                                    <div class="col-md-6">
                                        <div class="card h-100 border">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-0 fw-bold">{{ $search->name }}</h6>
                                                    @if($search->is_active)
                                                        <span class="badge bg-success">{{ __('Activa') }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ __('Pausada') }}</span>
                                                    @endif
                                                </div>

                                                @if($search->query)
                                                    <p class="mb-1 small">
                                                        <strong>{{ __('Busqueda') }}:</strong> "{{ $search->query }}"
                                                    </p>
                                                @endif

                                                @php $filters = $search->filters ?? []; @endphp
                                                @if(! empty($filters))
                                                    <ul class="small text-muted mb-2 ps-3">
                                                        @if(! empty($filters['min_price']))
                                                            <li>{{ __('Precio min') }}: ${{ $filters['min_price'] }}</li>
                                                        @endif
                                                        @if(! empty($filters['max_price']))
                                                            <li>{{ __('Precio max') }}: ${{ $filters['max_price'] }}</li>
                                                        @endif
                                                        @if(! empty($filters['on_sale']))
                                                            <li>{{ __('Solo ofertas') }}</li>
                                                        @endif
                                                    </ul>
                                                @endif

                                                <small class="text-muted d-block mb-3">
                                                    {{ __('Guardada el') }} {{ $search->created_at->format('d/m/Y') }}
                                                    @if($search->last_notified_at)
                                                        &middot; {{ __('Ultimo aviso') }}: {{ $search->last_notified_at->format('d/m/Y') }}
                                                    @endif
                                                </small>

                                                <div class="d-flex gap-2">
                                                    <form method="POST" action="{{ route('account.saved-searches.toggle', $search) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            {{ $search->is_active ? __('Pausar') : __('Activar') }}
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('account.saved-searches.destroy', $search) }}"
                                                          onsubmit="return confirm('{{ __('Eliminar esta busqueda?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            {{ __('Eliminar') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
