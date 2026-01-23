@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Select Facebook Page</h2>
            <a href="{{ route('admin.channels.facebook-pages.index') }}" class="btn btn-secondary">
                <i class="fa fa-times-circle"></i> Cancel
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Your Facebook Pages</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Select the Facebook Page you want to connect to this inbox.
                </p>

                <form action="{{ route('admin.channels.facebook-pages.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="inbox_name" class="form-label">Inbox Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('inbox_name') is-invalid @enderror"
                               id="inbox_name" name="inbox_name" value="{{ old('inbox_name') }}"
                               placeholder="e.g., Customer Support" required>
                        <div class="form-text">Choose a name to identify this inbox in your dashboard.</div>
                        @error('inbox_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Select Facebook Page <span class="text-danger">*</span></label>

                        @foreach($pages as $page)
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="page_id"
                                               id="page_{{ $page['id'] }}" value="{{ $page['id'] }}" required>
                                        <label class="form-check-label w-100" for="page_{{ $page['id'] }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $page['name'] }}</strong><br>
                                                    <small class="text-muted">ID: {{ $page['id'] }}</small>
                                                </div>
                                                <div>
                                                    <span class="badge bg-primary">{{ $page['category'] ?? 'Page' }}</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.channels.facebook-pages.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check-circle"></i> Connect Page
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle"></i> Next Steps</h5>
                <ol class="small mb-0">
                    <li>Choose an inbox name</li>
                    <li>Select the Facebook Page to connect</li>
                    <li>Click "Connect Page"</li>
                    <li>Configure webhook in Facebook Developer Console</li>
                    <li>Start receiving messages!</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
