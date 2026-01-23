@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Select Instagram Account</h2>
            <a href="{{ route('admin.channels.instagrams.create') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Start Over
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

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h5>Your Instagram Accounts</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.channels.instagrams.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label>Select Account</label>
                        <div class="list-group">
                            @foreach($accounts as $account)
                                <label class="list-group-item list-group-item-action">
                                    <input class="form-check-input me-2" type="radio" name="instagram_id" value="{{ $account['id'] }}" required>
                                    <div>
                                        <strong>{{ $account['username'] ?? 'Unknown' }}</strong>
                                        @if(isset($account['followers_count']))
                                            <span class="badge bg-info">{{ number_format($account['followers_count']) }} followers</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Inbox Name</label>
                        <input type="text" name="inbox_name" class="form-control" placeholder="e.g., Instagram Support" required>
                        <div class="form-text">This name will be displayed in your inbox list</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check-circle"></i> Connect Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
