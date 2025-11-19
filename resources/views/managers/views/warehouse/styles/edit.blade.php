@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formStylesEdit" action="{{ route('manager.warehouse.styles.update') }}" method="POST" role="form">

                    {{ csrf_field() }}
                    <input type="hidden" name="uid" value="{{ $style->uid }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Editar Estilo: {{ $style->code }}</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Actualice los datos del estilo según sea necesario.
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Código <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                           id="code" name="code" value="{{ old('code', $style->code) }}"
                                           placeholder="ROW, ISLAND, WALL, etc."
                                           maxlength="50" required>
                                    @error('code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $style->name) }}"
                                           placeholder="Pasillo Lineal, Isla, Pared, etc."
                                           maxlength="100" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Caras Disponibles <span class="text-danger">*</span></label>
                                    <div class="row">
                                        @foreach(['left' => 'Izquierda', 'right' => 'Derecha', 'front' => 'Frente', 'back' => 'Atrás'] as $value => $label)
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input @error('faces') is-invalid @enderror" type="checkbox" id="face_{{ $value }}" name="faces[]" value="{{ $value }}" {{ in_array($value, old('faces', $style->faces ?? [])) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="face_{{ $value }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('faces')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Niveles por Defecto <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('default_levels') is-invalid @enderror"
                                           id="default_levels" name="default_levels" value="{{ old('default_levels', $style->default_levels) }}"
                                           min="1" max="20" required>
                                    @error('default_levels')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Secciones por Defecto <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('default_sections') is-invalid @enderror"
                                           id="default_sections" name="default_sections" value="{{ old('default_sections', $style->default_sections) }}"
                                           min="1" max="30" required>
                                    @error('default_sections')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        <select name="available" id="available" class="select2 form-control @error('available') is-invalid @enderror">
                                            <option value="">Seleccionar estado</option>
                                            <option value="1" {{ old('available', $style->available ? '1' : '0') == '1' ? 'selected' : '' }}>Disponible</option>
                                            <option value="0" {{ old('available', $style->available ? '1' : '0') == '0' ? 'selected' : '' }}>No disponible</option>
                                        </select>
                                    </div>
                                    @error('available')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description"
                                              placeholder="Descripción adicional del estilo"
                                              rows="3">{{ old('description', $style->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border-top pt-3 mt-4">
                                    <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2">
                                        <i class="fa-duotone fa-check"></i> Actualizar Estilo
                                    </button>
                                    <a href="{{ route('manager.warehouse.styles') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2">
                                        <i class="fa-duotone fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>

                        </div>

                    </div>
                </form>
            </div>

        </div>

    </div>

@endsection
