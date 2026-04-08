@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="template-wrapper">
    {!! $renderedContent !!}
</div>
@endsection
