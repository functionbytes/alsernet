@extends('layouts.theme')

@section('title', 'Nueva categoria')

@section('content')
    @include('core::components.card', ['title' => 'Nueva categoria'])
    <div class="widget-content searchable-container list">
        <form action="{{ route('ecommerce.categories.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria padre</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Ninguna</option>
                            @foreach($parents as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripcion</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="published">Publicado</option>
                            <option value="draft">Borrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" name="order" class="form-control" value="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                        <label class="form-check-label" for="is_featured">Destacado</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.categories.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
@endsection
