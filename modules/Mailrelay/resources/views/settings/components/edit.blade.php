@extends('layouts.theme')

@section('title', 'Editar componente')

@section('content')
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Editar componente</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('settings.mailrelay.components.index') }}">Componentes</a></li>
                    <li class="breadcrumb-item active">{{ $component->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('settings.mailrelay.components.duplicate', $component->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-info">
                    <i class="fas fa-copy me-2"></i>Duplicar
                </button>
            </form>
            <form action="{{ route('settings.mailrelay.components.destroy', $component->id) }}" method="POST" class="d-inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
            </form>
        </div>
    </div>

    <form action="{{ route('settings.mailrelay.components.update', $component->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('mailrelay::settings.components._form')
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-form').on('submit', function(e) {
        if (!confirm('¿Estás seguro de eliminar este componente? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
