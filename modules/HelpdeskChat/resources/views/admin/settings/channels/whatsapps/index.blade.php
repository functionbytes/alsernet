@extends('layouts.admin')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>WhatsApp Channels</h2>
            <a href="{{ route('admin.channels.whatsapps.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Add WhatsApp
            </a>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                @if($whatsapps->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-whatsapp fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No WhatsApp Channels</h5>
                        <a href="{{ route('admin.channels.whatsapps.create') }}" class="btn btn-primary">Add WhatsApp</a>
                    </div>
                @else
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Phone Number</th><th>Inbox</th><th>Provider</th><th>Templates</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($whatsapps as $whatsapp)
                                <tr>
                                    <td><code>{{ $whatsapp->phone_number }}</code></td>
                                    <td>{{ $whatsapp->inbox->name ?? 'N/A' }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($whatsapp->provider) }}</span></td>
                                    <td>{{ count($whatsapp->message_templates ?? []) }} templates</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.channels.whatsapps.show', $whatsapp) }}" class="btn btn-info" title="View"><i class="fa fa-eye"></i></a>
                                            <a href="{{ route('admin.channels.whatsapps.edit', $whatsapp) }}" class="btn btn-warning" title="Edit Credentials"><i class="bi bi-shield-lock"></i></a>
                                            <button type="button" class="btn btn-danger" title="Delete" onclick="confirmDelete({{ $whatsapp->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>

                                        <form id="delete-form-{{ $whatsapp->id }}" action="{{ route('admin.channels.whatsapps.destroy', $whatsapp) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(whatsappId) {
    if (confirm('Are you sure you want to disconnect this WhatsApp Channel? This action cannot be undone.')) {
        document.getElementById('delete-form-' + whatsappId).submit();
    }
}
</script>
@endpush
