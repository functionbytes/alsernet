@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Campanas'])

    <div class="widget-content searchable-container list">

        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ Request::fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2 ">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i data-feather="search"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search" name="search" placeholder="Buscar" @isset($searchKey) value="{{ $searchKey }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <select class="form-select select2" name="role" data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="admin" @isset($role) @if ($role=='manager') selected @endif @endisset>  Administrador</option>
                                        <option value="customer" @isset($role) @if ($role=='inventarie') selected @endif @endisset>  Inventario</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa-duotone fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href=" {{ route('manager.campaigns.selecttype') }}" class="btn btn-primary">
                                    <i class="fa-duotone fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                    <tr>
                        <th>Titulo</th>
                        <th>Perfil</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach ($campaigns as $key => $campaign)
                        <tr class="search-items">
                            <td>
                                <span class="usr-email-addr" >{{ Str::words( Str::lower($campaign->title ), 12, '...')  }}</span>
                            </td>
                            <td>
                                <span class="usr-email-addr" >
                                @if ($campaign->readCache('SubscriberCount'))
                                        <div>
                                                            <span class="text-semibold" data-popup="tooltip" title="{{ $campaign->displayRecipients() }}">
                                                                {{ number_with_delimiter($campaign->readCache('SubscriberCount')) }} {{ trans('messages.recipients') }}
                                                            </span>
                                                        </div>
                                    @endif
                                </span>
                            </td>
                            <td>
                               <span class="badge bg-light-secondary rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                                     @if ($campaign->status != 'new' && isset($campaign->run_at))
                                       <span class="text-muted2 d-block xtooltip" title="{{ $campaign->scheduleDiffForHumans() }}">{{ trans('messages.run_at') }}: <span class="material-symbols-rounded">alarm</span>
							                        {{ isset($campaign->run_at) ? Auth::user()->customer->formatDateTime($campaign->run_at, 'datetime_full') : "" }}</span>
                                   @else
                                       <span class="text-muted2 d-block">{{ trans('messages.updated_at') }}: {{ $campaign->created_at }}</span>
                                   @endif
                               </span>
                            </td>

                            <td>
                                <span class="usr-ph-no" data-phone="{{ date('Y-m-d', strtotime($campaign->updated_at)) }}">{{ date('Y-m-d', strtotime($campaign->updated_at)) }}</span>
                            </td>
                            <td class="text-left">
                                <div class="dropdown dropstart">
                                    <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots fs-5"></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">


                                            <li>
                                                <a class="dropdown-item" href="{{ route('manager.campaigns.edit', $campaign->uid) }}" >Editar</a>
                                            </li>

                                            <li>
                                                <a class="dropdown-item" href="{{ route('manager.campaigns.overview', $campaign->uid) }}" >Estadistica</a>
                                            </li>

                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.campaigns.resend', $campaign->uid) }}" >Reenviar</a>
                                                </li>

                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.campaigns.send.test', $campaign->uid) }}" >Enviar test</a>
                                                </li>

                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.campaigns.pause', $campaign->uid) }}" >Pausar</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" link-method="POST" link-confirm="{{ trans('messages.pause_campaigns_confirm', ['number' => '1']) }}" href="{{ route('manager.campaigns.pause', $campaign->uid) }}" >Pausar</a>
                                                </li>

                                                <li>
                                                    <a class="dropdown-item"  link-method="POST" link-confirm="{{ trans('messages.restart_campaigns_confirm', ['number' => '1']) }}" href="{{ route('manager.campaigns.restart', $campaign->uid) }}" >Pausar</a>
                                                </li>

                                               <li>
                                                    <a class="dropdown-item"  href="{{ route('manager.campaigns.copy', $campaign->uid) }}" >Copiar</a>
                                               </li>

                                                    <li>
                                                        <a class="dropdown-item"  href="{{ route('manager.campaigns.delete', $campaign->uid) }}"  link-method="POST"
                                                           link-confirm="{{ trans('messages.delete_campaign_confirm', ['name' => $campaign->name]) }}" >Eliminar</a>
                                                    </li>


                                    </ul>
                                </div>

                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
            <div class="result-body ">
                <span>Mostrar {{ $campaigns->firstItem() }}-{{ $campaigns->lastItem() }} de {{ $campaigns->total() }} resultados</span>
                <nav>
                    {{ $campaigns->appends(request()->input())->links() }}
                </nav>
            </div>
        </div>
    </div>
@endsection





