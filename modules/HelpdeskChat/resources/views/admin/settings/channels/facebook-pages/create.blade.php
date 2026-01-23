@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Connect Facebook Page</h2>
            <a href="{{ route('admin.channels.facebook-pages.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-messenger fs-1 text-primary d-block mb-4"></i>
                <h4 class="mb-3">Connect Your Facebook Page</h4>
                <p class="text-muted mb-4">
                    Click the button below to authorize this application to access your Facebook Pages.
                    You'll be redirected to Facebook to select which page you want to connect.
                </p>

                <a href="{{ $oauthUrl }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-facebook"></i> Continue with Facebook
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle"></i> Requirements</h5>
                <ul class="small mb-0">
                    <li>You must be an admin of the Facebook Page</li>
                    <li>The page must be published (not in draft mode)</li>
                    <li>You'll need to grant "pages_messaging" permission</li>
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
