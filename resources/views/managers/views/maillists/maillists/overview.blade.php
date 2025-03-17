@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Estadistica lista '. $list->title])

@endsection

@push('scripts')

@endpush
