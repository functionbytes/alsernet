@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Connect Instagram Account</h2>
            <a href="{{ route('admin.channels.instagrams.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-instagram fs-1 text-danger d-block mb-4"></i>
                <h4 class="mb-3">Connect Your Instagram Account</h4>
                <p class="text-muted mb-4">
                    Click the button below to authorize this application to access your Instagram Business account.
                    You'll be redirected to Instagram to authenticate.
                </p>

                <a href="{{ $oauthUrl }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-instagram"></i> Continue with Instagram
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle"></i> Requirements</h5>
                <ul class="small mb-0">
                    <li>You must have an Instagram Business Account</li>
                    <li>The account must be connected to a Facebook Page (Meta requirement)</li>
                    <li>You'll need to grant messaging permissions</li>
                    <li>The connection is secure via OAuth 2.0</li>
                </ul>
            </div>
        </div>

        <div class="card bg-light mt-3">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-shield-check"></i> Privacy</h5>
                <p class="small mb-0">
                    We only request the minimum permissions needed to receive and send messages.
                    Your personal information is never stored or shared.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
