@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-envelope"></i> Email Channels</h2>
        <a href="{{ route('admin.channels.emails.create') }}" class="btn btn-primary">
            <i class="fa fa-plus-circle"></i> Add Email Channel
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($emails->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-envelope display-1 text-muted"></i>
                <h4 class="mt-3">No Email Channels</h4>
                <p class="text-muted">Create your first email channel to start receiving and sending emails.</p>
                <a href="{{ route('admin.channels.emails.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus-circle"></i> Add Email Channel
                </a>
            </div>
        </div>
    @else
        <div class="row">
            @foreach($emails as $inbox)
                @php
                    $email = $inbox->channel;
                @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-envelope"></i> {{ $inbox->name }}
                            </h5>
                            <p class="card-text">
                                <strong>Email:</strong> {{ $email->email }}<br>
                                <strong>Provider:</strong> {{ ucfirst($email->provider ?? 'custom') }}<br>
                                <strong>IMAP:</strong>
                                @if($email->imap_enabled)
                                    <span class="badge bg-success">Enabled</span>
                                @else
                                    <span class="badge bg-secondary">Disabled</span>
                                @endif
                                <br>
                                <strong>SMTP:</strong>
                                @if($email->smtp_enabled)
                                    <span class="badge bg-success">Enabled</span>
                                @else
                                    <span class="badge bg-secondary">Disabled</span>
                                @endif
                            </p>
                            <p class="card-text">
                                <small class="text-muted">Forward to: {{ $email->forward_to_email }}</small>
                            </p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="btn-group w-100" role="group">
                                <a href="{{ route('admin.channels.emails.show', $email) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-eye"></i> View
                                </a>
                                <a href="{{ route('admin.channels.emails.edit', $email) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('admin.channels.emails.destroy', $email) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fa fa-trash"></i> Delete
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
@endsection
