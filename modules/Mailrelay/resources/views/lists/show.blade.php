@extends('layouts.theme')

@section('title', $list->name)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-list"></i> {{ $list->name }}
                    </h1>
                    <p class="text-muted mb-0">{{ $list->description ?? 'No description' }}</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('mailrelay.lists.edit', $list) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('mailrelay.lists.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <!-- List Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Subscribers</h6>
                            <h3 class="mb-0">{{ $list->subscribers_count ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Campaigns</h6>
                            <h3 class="mb-0">{{ $list->campaigns_count ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Created</h6>
                            <small>{{ $list->created_at->format('M d, Y') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Last Updated</h6>
                            <small>{{ $list->updated_at->format('M d, Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle"></i> List Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p>
                                <strong>List Name:</strong><br>
                                {{ $list->name }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>List ID:</strong><br>
                                <code>{{ $list->id }}</code>
                            </p>
                        </div>
                    </div>
                    @if($list->description)
                        <div>
                            <p>
                                <strong>Description:</strong><br>
                                {{ $list->description }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Campaigns Using This List -->
            @if($list->campaigns_count > 0)
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-envelope"></i> Campaigns Using This List
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Campaign Name</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($list->campaigns as $campaign)
                                    <tr>
                                        <td>{{ $campaign->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $campaign->status === 'sent' ? 'success' : ($campaign->status === 'draft' ? 'secondary' : 'warning') }}">
                                                {{ ucfirst($campaign->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $campaign->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('mailrelay.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No campaigns found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
