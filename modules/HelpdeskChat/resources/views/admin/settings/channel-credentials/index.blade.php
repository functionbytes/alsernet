@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Channel Credentials Setup</h2>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border">
            <div class="card-header">
                <h5 class="mb-0">Channel Configuration Status</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Below you can see the configuration status for each messaging channel.
                    Make sure to configure at least one channel to start receiving messages.
                </p>

                <div class="row">
                    @forelse($channels as $channelKey => $channel)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card @if($channel['configured']) border-success @else border-danger @endif h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi {{ $channel['icon'] }} fs-3 @if($channel['configured']) text-success @else text-danger @endif me-2"></i>
                                        <h6 class="card-title mb-0">{{ $channel['name'] }}</h6>
                                    </div>

                                    <div class="mb-3">
                                        @if($channel['configured'])
                                            <span class="badge bg-success">
                                                <i class="fa fa-check-circle"></i> Configured
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="bi bi-exclamation-circle"></i> Not Configured
                                            </span>
                                            @if(!($channel['optional'] ?? false))
                                                <span class="badge bg-warning ms-1">Required</span>
                                            @endif
                                        @endif
                                    </div>

                                    @if(!$channel['configured'])
                                        <div class="alert alert-sm alert-warning mb-3">
                                            <small>
                                                <strong>Missing:</strong>
                                                {{ implode(', ', $channel['required'] ?? []) }}
                                            </small>
                                        </div>
                                    @endif

                                    <a href="{{ route('admin.settings.channel-credentials.show', $channelKey) }}"
                                       class="btn btn-sm @if($channel['configured']) btn-outline-success @else btn-outline-danger @endif w-100">
                                        <i class="bi bi-gear"></i>
                                        @if($channel['configured'])
                                            View Configuration
                                        @else
                                            Setup Now
                                        @endif
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-5">
                                <p class="text-muted">No channels available</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-lightbulb"></i> Pro Tips</h5>
                <ul class="small mb-0">
                    <li>Store all credentials in your <code>.env</code> file</li>
                    <li>Never commit sensitive credentials to version control</li>
                    <li>Use environment-specific configuration files</li>
                    <li>Rotate API keys and secrets regularly</li>
                    <li>Keep your integrations up to date</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
