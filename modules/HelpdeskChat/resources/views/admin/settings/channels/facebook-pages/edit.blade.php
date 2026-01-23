@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Edit Facebook Page</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.channels.facebook-pages.update', $facebookPage) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="inbox_name" class="form-label">Inbox Name</label>
                        <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                               id="inbox_name" name="inbox_name" value="{{ old('inbox_name', $facebookPage->inbox->name) }}" required>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.channels.facebook-pages.show', $facebookPage) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-circle"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Credentials</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">If your Facebook credentials are invalid or expired, you can reauthorize your account.</p>

                <form action="{{ route('admin.channels.facebook-pages.reauthorize', $facebookPage) }}" method="POST" class="d-grid">
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
