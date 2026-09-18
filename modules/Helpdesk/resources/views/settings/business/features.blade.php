@extends('layouts.theme')

@section('title', 'Panel de administración')

@section('page_header')
    @include('core::components.card', ['title' => 'Panel de administración'])
@endsection

@section('content')

    @include('core::components.alerts')

    @include('helpdesk::settings.business._features-panel')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
