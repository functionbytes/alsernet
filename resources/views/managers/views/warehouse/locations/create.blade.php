@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formlocations" action="{{ route('manager.warehouse.locations.store', [$warehouse->uid, $floor->uid]) }}" method="POST" role="form">

                    {{ csrf_field() }}

                    <!-- Hidden fields para IDs -->
                    <input type="hidden" name="warehouse_uid" value="{{ $warehouse->uid }}">
                    <input type="hidden" name="floor_uid" value="{{ $floor->uid }}">

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Crear nueva Ubicación</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Complete los datos de la ubicación (estantería) que desea registrar en <strong>{{ $floor->name }}</strong>.
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

                            <div class="col-12">
                                <div class="alert alert-info">
                                    <strong>Almacén:</strong> {{ $warehouse->name }} | <strong>Piso:</strong> {{ $floor->name }} ({{ $floor->code }})
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estilo de Ubicación <span class="text-danger">*</span></label>
                                    <select name="style_id" id="style_id" class="select2 form-control @error('style_id') is-invalid @enderror" required>
                                        <option value="">Seleccionar estilo</option>
                                        @foreach($styles as $id => $name)
                                            <option value="{{ $id }}" {{ old('style_id') == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('style_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Código <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="PASILLO13A, ISLA02, etc." maxlength="50" required>
                                    @error('code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Código de Barras</label>
                                    <input type="text" class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder="Código de barras físico" maxlength="100">
                                    @error('barcode')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Posición X (metros) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('position_x') is-invalid @enderror" id="position_x" name="position_x" value="{{ old('position_x', 0) }}" min="0" required>
                                    @error('position_x')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Posición Y (metros) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('position_y') is-invalid @enderror" id="position_y" name="position_y" value="{{ old('position_y', 0) }}" min="0" required>
                                    @error('position_y')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Posición Z (altura)</label>
                                    <input type="number" class="form-control @error('position_z') is-invalid @enderror" id="position_z" name="position_z" value="{{ old('position_z', 0) }}" min="0">
                                    @error('position_z')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Niveles Totales <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('total_levels') is-invalid @enderror" id="total_levels" name="total_levels" value="{{ old('total_levels') }}" min="1" max="20" required>
                                    @error('total_levels')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Secciones Totales <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('total_sections') is-invalid @enderror" id="total_sections" name="total_sections" value="{{ old('total_sections') }}" min="1" max="30" required>
                                    @error('total_sections')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Capacidad Máxima (kg)</label>
                                    <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity') }}" step="0.01" min="0" placeholder="Opcional">
                                    @error('capacity')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Estado</label>
                                    <div class="input-group">
                                        <select name="available" id="available" class="select2 form-control @error('available') is-invalid @enderror">
                                            <option value="1" {{ old('available', 1) == 1 ? 'selected' : '' }}>Disponible</option>
                                            <option value="0" {{ old('available', 1) == 0 ? 'selected' : '' }}>No disponible</option>
                                        </select>
                                    </div>
                                    @error('available')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Notas</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" placeholder="Notas adicionales: mantenimiento, daños, etc." rows="3" maxlength="500">{{ old('notes') }}</textarea>
                                    <small class="text-muted d-block mt-1">Máximo 500 caracteres</small>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <p class="text-muted"><small>
                                    <i class="fa-duotone fa-circle-info"></i>
                                    Las posiciones de inventario se crearán automáticamente basadas en el estilo y configuración de niveles/secciones.
                                </small></p>
                            </div>

                            <div class="col-12">
                                <div class="errors d-none">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border-top pt-1 mt-4">
                                    <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                    </button>
                                </div>

                        </div>

                    </div>
                </form>
            </div>

        </div>

    </div>

@endsection
