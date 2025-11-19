@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formSlots" action="{{ route('manager.warehouse.slots.store') }}" method="POST" role="form">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Crear nueva Posición de Inventario</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Complete los datos de la posición que desea registrar en el sistema de almacén.
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
                                    <label class="control-label col-form-label">Estantería <span class="text-danger">*</span></label>
                                    <select name="stand_id" id="stand_id" class="select2 form-control @error('stand_id') is-invalid @enderror" required>
                                        <option value="">Seleccionar estantería</option>
                                        @foreach($stands as $stand)
                                            <option value="{{ $stand->id }}" {{ old('stand_id') == $stand->id ? 'selected' : '' }}>
                                                {{ $stand->code }} ({{ $stand->floor->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('stand_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Cara <span class="text-danger">*</span></label>
                                    <select name="face" id="face" class="select2 form-control @error('face') is-invalid @enderror" required>
                                        <option value="">Seleccionar cara</option>
                                        <option value="left" {{ old('face') == 'left' ? 'selected' : '' }}>Izquierda</option>
                                        <option value="right" {{ old('face') == 'right' ? 'selected' : '' }}>Derecha</option>
                                        <option value="front" {{ old('face') == 'front' ? 'selected' : '' }}>Frente</option>
                                        <option value="back" {{ old('face') == 'back' ? 'selected' : '' }}>Atrás</option>
                                    </select>
                                    @error('face')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nivel <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('level') is-invalid @enderror" id="level" name="level" value="{{ old('level') }}" min="1" required>
                                    @error('level')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Sección <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('section') is-invalid @enderror" id="section" name="section" value="{{ old('section') }}" min="1" required>
                                    @error('section')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Código de Barras</label>
                                    <input type="text" class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder="Opcional" maxlength="100">
                                    @error('barcode')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Producto</label>
                                    <select name="product_id" id="product_id" class="select2 form-control @error('product_id') is-invalid @enderror">
                                        <option value="">Sin producto (vacío)</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                {{ $product->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Cantidad</label>
                                    <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" value="{{ old('quantity', 0) }}" min="0">
                                    @error('quantity')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Cantidad Máxima</label>
                                    <input type="number" class="form-control @error('max_quantity') is-invalid @enderror" id="max_quantity" name="max_quantity" value="{{ old('max_quantity') }}" min="1" placeholder="Opcional">
                                    @error('max_quantity')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Peso Actual (kg)</label>
                                    <input type="number" class="form-control @error('weight_current') is-invalid @enderror" id="weight_current" name="weight_current" value="{{ old('weight_current', 0) }}" step="0.01" min="0">
                                    @error('weight_current')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Peso Máximo (kg)</label>
                                    <input type="number" class="form-control @error('weight_max') is-invalid @enderror" id="weight_max" name="weight_max" value="{{ old('weight_max') }}" step="0.01" min="0" placeholder="Opcional">
                                    @error('weight_max')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
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
