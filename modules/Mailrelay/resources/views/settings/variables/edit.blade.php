@extends('layouts.theme')

@section('title', 'Editar variable')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Editar variable</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('settings.mailrelay.variables.index') }}">Variables</a></li>
                    <li class="breadcrumb-item active">{{ $variable->name }}</li>
                </ol>
            </nav>
        </div>
        @if(!$variable->is_system)
            <form action="{{ route('settings.mailrelay.variables.destroy', $variable->id) }}" method="POST" class="d-inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
            </form>
        @endif
    </div>

    @if($variable->is_system)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Esta es una variable del sistema. Puede editarse pero no eliminarse.
        </div>
    @endif

    <form action="{{ route('settings.mailrelay.variables.update', $variable->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('mailrelay::settings.variables._form')
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-form').on('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar esta variable? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
