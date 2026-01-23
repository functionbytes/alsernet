@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Web Widgets</h2>
            <a href="{{ route('admin.channels.webs.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Create Web Widget
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if($widgets->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No Web Widgets Yet</h5>
                        <p class="text-muted">Create your first web widget to start receiving messages from your website.</p>
                        <a href="{{ route('admin.channels.webs.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus-circle"></i> Create Web Widget
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Website URL</th>
                                    <th>Widget Color</th>
                                    <th>Pre-chat Form</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($widgets as $widget)
                                    <tr>
                                        <td>
                                            <strong>{{ $widget->inbox->name }}</strong>
                                        </td>
                                        <td>
                                            <a href="{{ $widget->website_url }}" target="_blank" class="text-decoration-none">
                                                {{ $widget->website_url }}
                                                <i class="bi bi-box-arrow-up-right small"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $widget->widget_color }}">
                                                {{ $widget->widget_color }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($widget->pre_chat_form_enabled)
                                                <span class="badge bg-success">Enabled</span>
                                            @else
                                                <span class="badge bg-secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td>{{ $widget->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.channels.webs.show', $widget) }}" class="btn btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.channels.webs.edit', $widget) }}" class="btn btn-warning" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" title="Delete" onclick="confirmDelete({{ $widget->id }})">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>

                                            <form id="delete-form-{{ $widget->id }}" action="{{ route('admin.channels.webs.destroy', $widget) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(widgetId) {
    if (confirm('Are you sure you want to delete this web widget? This action cannot be undone.')) {
        document.getElementById('delete-form-' + widgetId).submit();
    }
}
</script>
@endpush
