@extends('layouts.theme')

@section('title', 'Editar campaña — ' . $campaign->name)

@section('page_header')
    @include('core::components.card', ['title' => 'Editar campaña'])
@endsection

@section('content')

    @php $currentTab = request()->get('tab', 'general'); @endphp

    <div class="row g-3">

        {{-- Main form column --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                {{-- Tab navigation --}}
                <div class="card-header border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'general' ? 'active' : '' }}"
                               href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'general']) }}">
                                General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'content' ? 'active' : '' }}"
                               href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'content']) }}">
                                Contenido
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'appearance' ? 'active' : '' }}"
                               href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'appearance']) }}">
                                Apariencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $currentTab === 'conditions' ? 'active' : '' }}"
                               href="{{ route('helpdesk.campaigns.edit', ['campaign' => $campaign, 'tab' => 'conditions']) }}">
                                Condiciones
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    @include('core::components.alerts')

                    @if($currentTab === 'general')
                        @include('helpdeskcampaigns::managers.campaigns.tabs.general', ['campaign' => $campaign])
                    @elseif($currentTab === 'content')
                        @include('helpdeskcampaigns::managers.campaigns.tabs.content', ['campaign' => $campaign])
                    @elseif($currentTab === 'appearance')
                        @include('helpdeskcampaigns::managers.campaigns.tabs.appearance', ['campaign' => $campaign])
                    @elseif($currentTab === 'conditions')
                        @include('helpdeskcampaigns::managers.campaigns.tabs.conditions', ['campaign' => $campaign])
                    @endif
                </div>
            </div>
        </div>

        {{-- Info sidebar --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informacion de la campana</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">Estado</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $campaign->status_color }}-subtle text-{{ $campaign->status_color }}">
                                {{ $campaign->status_label }}
                            </span>
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Tipo</dt>
                        <dd class="col-7">{{ $campaign->type_label }}</dd>
                        <dt class="col-5 text-muted fw-normal">Creada</dt>
                        <dd class="col-7">{{ $campaign->created_at->format('d/m/Y H:i') }}</dd>
                        <dt class="col-5 text-muted fw-normal">Actualizada</dt>
                        <dd class="col-7">{{ $campaign->updated_at->format('d/m/Y H:i') }}</dd>
                        @if($campaign->published_at)
                            <dt class="col-5 text-muted fw-normal">Publicada</dt>
                            <dd class="col-7">{{ $campaign->published_at->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Acciones rapidas</h6>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('helpdesk.campaigns.show', $campaign) }}" class="btn btn-light btn-sm">
                            Ver estadisticas
                        </a>
                        @if($campaign->status === 'draft')
                            <form method="POST" action="{{ route('helpdesk.campaigns.publish', $campaign) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm w-100">Publicar campana</button>
                            </form>
                        @elseif($campaign->status === 'active')
                            <form method="POST" action="{{ route('helpdesk.campaigns.pause', $campaign) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm w-100">Pausar campana</button>
                            </form>
                        @elseif($campaign->status === 'paused')
                            <form method="POST" action="{{ route('helpdesk.campaigns.resume', $campaign) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm w-100">Reanudar campana</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
