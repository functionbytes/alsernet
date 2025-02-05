@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Campana tipo '. $campaign->type])

    <div class="card">
        <ul class="nav nav-pills user-profile-tab" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"  href="{{route('manager.campaigns.recipients', $campaign->uid )}}" >
                    <i class="ti ti-user-circle me-2 fs-6"></i>
                    <span class="d-none d-md-block">Destinatario</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.setup', $campaign->uid )}}" >
                    <i class="ti ti-bell me-2 fs-6"></i>
                    <span class="d-none d-md-block">Configuracion</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.template', $campaign->uid )}}"  >
                    <i class="ti ti-article me-2 fs-6"></i>
                    <span class="d-none d-md-block">Plantilla</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.schedule', $campaign->uid )}}" >
                    <i class="ti ti-lock me-2 fs-6"></i>
                    <span class="d-none d-md-block">Cronograma</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3" href="{{route('manager.campaigns.confirm', $campaign->uid )}}" >
                    <i class="ti ti-lock me-2 fs-6"></i>
                    <span class="d-none d-md-block">Confirmacion</span>
                </a>
            </li>
        </ul>
        <div class="card-body">
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade active show" role="tabpanel" tabindex="0">
                    <form id="formRecipients" enctype="multipart/form-data" role="form" onSubmit="return false">
                        {{ csrf_field() }}

                        <input type="hidden" id="uid" name="uid" value="{{$campaign->uid}}">
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection





