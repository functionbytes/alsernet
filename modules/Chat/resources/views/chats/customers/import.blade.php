@extends('layouts.theme')

@section('title', 'Import Contacts')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Import Contacts</h1>
            <p class="text-muted">Upload a CSV file to import contacts in bulk</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Instructions -->
            <div class="alert alert-info mb-3">
                <h5 class="alert-heading">CSV Format Requirements</h5>
                <ul class="mb-0">
                    <li>CSV file must contain a <strong>"name"</strong> column (required)</li>
                    <li>Optional columns: <strong>"email"</strong>, <strong>"phone_number"</strong></li>
                    <li>First row should be the header row with column names</li>
                    <li>Duplicate contacts (matching email or phone) will be skipped</li>
                    <li>Maximum file size: 10MB</li>
                </ul>
            </div>

            <!-- Example CSV -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Example CSV Format</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded mb-0"><code>name,email,phone_number
John Doe,john@example.com,+1234567890
Jane Smith,jane@example.com,+0987654321
Bob Johnson,bob@example.com,</code></pre>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('chat.customers.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror"
                                   id="file" name="file" accept=".csv,.txt" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Accepted formats: .csv, .txt (max 10MB)</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Import Contacts
                            </button>
                            <a href="{{ route('chat.customers.index') }}" class="btn btn-secondary">
                                <i class="fa fa-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
