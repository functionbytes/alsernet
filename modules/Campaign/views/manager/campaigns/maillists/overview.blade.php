@extends('layouts.managers')

@section('content')

    @include('core::components.card', ['title' => 'Estadistica lista '. $list->title])

@endsection

@push('scripts')

@endpush
