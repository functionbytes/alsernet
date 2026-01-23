<!-- resources/views/mailrelay/create-campaign.blade.php -->

@extends('layouts.theme')

@section('title', 'Crear Campaña')

@section('content')
    <div class="container">
        <h1>Crear Nueva Campaña</h1>

        <!-- Mostrar mensaje de éxito o error -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('mailrelay.campaigns.store') }}" method="POST">
            @csrf

            <!-- Asunto -->
            <div class="form-group">
                <label for="subject">Asunto</label>
                <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
                @error('subject')
                <div class="alert alert-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Texto de vista previa -->
            <div class="form-group">
                <label for="preview_text">Texto de Vista Previa</label>
                <input type="text" name="preview_text" class="form-control" value="{{ old('preview_text') }}" required>
                @error('preview_text')
                <div class="alert alert-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Contenido HTML -->
            <div class="form-group">
                <label for="html">Contenido HTML</label>
                <textarea name="html" class="form-control" rows="5" required>{{ old('html') }}</textarea>
                @error('html')
                <div class="alert alert-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- IDs de grupo -->
            <div class="form-group">
                <label for="group_ids">IDs de Grupo (separados por coma)</label>
                <input type="text" name="group_ids" class="form-control" value="{{ old('group_ids') }}" required>
                @error('group_ids')
                <div class="alert alert-danger">{{ $message }}</div>
                @enderror
            </div>

            <!-- Botón de envío -->
            <button type="submit" class="btn btn-primary">Crear Campaña</button>
        </form>
    </div>
@endsection
