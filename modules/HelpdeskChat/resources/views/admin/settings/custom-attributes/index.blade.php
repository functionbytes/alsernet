@extends('layouts.admin')

@section('title', 'Custom Attributes')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Custom Attributes</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.custom-attributes.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Create Attribute
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Model Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.custom-attributes.index', ['model' => 'contact']) }}"
                   class="btn btn-{{ $model === 'contact' ? 'primary' : 'outline-primary' }}">
                    Contact Attributes
                </a>
                <a href="{{ route('admin.custom-attributes.index', ['model' => 'conversation']) }}"
                   class="btn btn-{{ $model === 'conversation' ? 'primary' : 'outline-primary' }}">
                    Conversation Attributes
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Display Name</th>
                            <th>Key</th>
                            <th>Type</th>
                            <th>Model</th>
                            <th>Created</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attributes as $attribute)
                            <tr>
                                <td><strong>{{ $attribute->attribute_display_name }}</strong></td>
                                <td><code>{{ $attribute->attribute_key }}</code></td>
                                <td>
                                    @php
                                        $types = ['Text', 'Number', 'Currency', 'Percent', 'Link', 'Date', 'List', 'Checkbox'];
                                    @endphp
                                    <span class="badge bg-secondary">{{ $types[$attribute->attribute_display_type] ?? 'Unknown' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $attribute->attribute_model === 0 ? 'Contact' : 'Conversation' }}</span>
                                </td>
                                <td>{{ $attribute->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.custom-attributes.edit', $attribute) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete('{{ route('admin.custom-attributes.destroy', $attribute) }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No custom attributes found. <a href="{{ route('admin.custom-attributes.create') }}">Create one now</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $attributes->appends(['model' => $model])->links() }}
            </div>
        </div>
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this custom attribute?')) {
        const form = $('#delete-form');
        form.attr('action', url);
        form.submit();
    }
}
</script>
@endsection
