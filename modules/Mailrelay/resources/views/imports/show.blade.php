@extends('layouts.theme')

@section('title', 'Import Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('mailrelay.imports.index') }}">Imports</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Import #{{ $import->id }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ $import->file_name }}</h1>
                    <p class="text-muted mb-0">Import Details and Progress</p>
                </div>
                <div>
                    @if($import->status === 'processing')
                        <button class="btn btn-outline-primary" onclick="window.location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                        </button>
                    @endif
                    <a href="{{ route('mailrelay.imports.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Import Progress</h5>
                </div>
                <div class="card-body">
                    @php
                        $progress = $import->total_rows > 0
                            ? round(($import->processed_rows / $import->total_rows) * 100)
                            : 0;
                    @endphp

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold">Overall Progress</span>
                            <span class="text-muted">{{ number_format($import->processed_rows) }} / {{ number_format($import->total_rows) }} rows</span>
                        </div>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar
                                @if($import->status === 'completed') bg-success
                                @elseif($import->status === 'failed') bg-danger
                                @elseif($import->status === 'processing') bg-info progress-bar-striped progress-bar-animated
                                @else bg-secondary
                                @endif"
                                role="progressbar"
                                style="width: {{ $progress }}%;"
                                aria-valuenow="{{ $progress }}"
                                aria-valuemin="0"
                                aria-valuemax="100">
                                <strong>{{ $progress }}%</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <i class="bi bi-check-circle text-success display-6"></i>
                                    <h3 class="mt-2 mb-0">{{ number_format($import->successful_rows) }}</h3>
                                    <p class="text-muted mb-0">Successful</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <i class="bi bi-x-circle text-danger display-6"></i>
                                    <h3 class="mt-2 mb-0">{{ number_format($import->failed_rows) }}</h3>
                                    <p class="text-muted mb-0">Failed</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <i class="bi bi-hourglass-split text-warning display-6"></i>
                                    <h3 class="mt-2 mb-0">{{ number_format($import->total_rows - $import->processed_rows) }}</h3>
                                    <p class="text-muted mb-0">Remaining</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($import->status === 'processing')
                        <div class="alert alert-info mt-4 mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Import is currently processing. This page will automatically refresh every 5 seconds.
                        </div>
                    @endif

                    @if($import->status === 'completed')
                        <div class="alert alert-success mt-4 mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            Import completed successfully! All records have been processed.
                        </div>
                    @endif

                    @if($import->status === 'failed')
                        <div class="alert alert-danger mt-4 mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Import failed. Please check the error log below for details.
                        </div>
                    @endif
                </div>
            </div>

            @if($import->errors && count(json_decode($import->errors, true)) > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>Error Log
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">Row</th>
                                        <th>Error Message</th>
                                        <th style="width: 120px;">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(json_decode($import->errors, true) as $error)
                                        <tr>
                                            <td>
                                                <span class="badge bg-danger">{{ $error['row'] ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <small>{{ $error['message'] ?? $error }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $error['time'] ?? '-' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if($import->mapping && count(json_decode($import->mapping, true)) > 0)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-map me-2"></i>Column Mapping
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>File Column</th>
                                        <th>Mapped To</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(json_decode($import->mapping, true) as $fileCol => $mappedCol)
                                        <tr>
                                            <td><code>{{ $fileCol }}</code></td>
                                            <td><strong>{{ $mappedCol }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Import Information</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">
                            @if($import->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($import->status === 'processing')
                                <span class="badge bg-info">Processing</span>
                            @elseif($import->status === 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif($import->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Import Type</dt>
                        <dd class="col-sm-7">{{ ucfirst($import->import_type ?? 'N/A') }}</dd>

                        <dt class="col-sm-5">File Type</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-secondary">{{ strtoupper($import->file_type) }}</span>
                        </dd>

                        <dt class="col-sm-5">File Size</dt>
                        <dd class="col-sm-7">{{ number_format($import->file_size / 1024, 2) }} KB</dd>

                        <dt class="col-sm-5">Created</dt>
                        <dd class="col-sm-7">{{ $import->created_at->format('M d, Y h:i A') }}</dd>

                        <dt class="col-sm-5">Updated</dt>
                        <dd class="col-sm-7">{{ $import->updated_at->diffForHumans() }}</dd>

                        @if($import->started_at)
                            <dt class="col-sm-5">Started</dt>
                            <dd class="col-sm-7">{{ $import->started_at->format('M d, Y h:i A') }}</dd>
                        @endif

                        @if($import->completed_at)
                            <dt class="col-sm-5">Completed</dt>
                            <dd class="col-sm-7">{{ $import->completed_at->format('M d, Y h:i A') }}</dd>
                        @endif

                        @if($import->started_at && $import->completed_at)
                            <dt class="col-sm-5">Duration</dt>
                            <dd class="col-sm-7">{{ $import->started_at->diffInSeconds($import->completed_at) }} seconds</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if($import->description)
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Description</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 small">{{ $import->description }}</p>
                    </div>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($import->status === 'completed' || $import->status === 'failed')
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-download me-2"></i>Download Report
                            </button>
                        @endif

                        @if($import->failed_rows > 0)
                            <button class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-file-earmark-excel me-2"></i>Export Failed Rows
                            </button>
                        @endif

                        <button class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this import?')">
                            <i class="bi bi-trash me-2"></i>Delete Import
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($import->status === 'processing')
<script>
    setTimeout(function() {
        window.location.reload();
    }, 5000);
</script>
@endif
@endsection
