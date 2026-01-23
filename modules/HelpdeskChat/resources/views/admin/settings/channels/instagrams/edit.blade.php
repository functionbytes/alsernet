@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Edit Instagram Account</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.channels.instagrams.update', $instagram) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Inbox Name</label>
                        <input type="text" name="inbox_name" class="form-control @error('inbox_name') is-invalid @enderror"
                               value="{{ old('inbox_name', $instagram->inbox->name) }}" required>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Instagram ID</label>
                        <input type="text" class="form-control" value="{{ $instagram->instagram_id }}" disabled>
                        <small class="text-muted">Instagram ID cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                               value="{{ old('username', $instagram->username) }}">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-circle"></i> Update Instagram Account
                        </button>
                        <a href="{{ route('admin.channels.instagrams.show', $instagram) }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-info mb-3">
            <div class="card-header bg-info">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Token Status</h5>
            </div>
            <div class="card-body">
                @if($instagram->isTokenExpired())
                    <span class="badge bg-danger d-block mb-2">Expired</span>
                    <p class="small text-muted">Token has expired. Please reauthorize your Instagram account.</p>
                @elseif($instagram->needsTokenRefresh())
                    <span class="badge bg-warning d-block mb-2">Expiring Soon</span>
                    <p class="small text-muted">Expires {{ $instagram->token_expires_at->diffForHumans() }}</p>
                @else
                    <span class="badge bg-success d-block mb-2">Active</span>
                    <p class="small text-muted">Expires {{ $instagram->token_expires_at?->diffForHumans() ?? 'Unknown' }}</p>
                @endif
            </div>
        </div>

        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Credentials</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">If your Instagram credentials are invalid or expired, you can reauthorize your account.</p>

                <form action="{{ route('admin.channels.instagrams.reauthorize', $instagram) }}" method="POST" class="d-grid">
                    @csrf
                    @method('POST')
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-shield-lock"></i> Reauthorize Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
