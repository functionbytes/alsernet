@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formFloors" action="{{ route('manager.warehouse.floors.store') }}" method="POST" role="form">

                    {{ csrf_field() }}

                    <div class="card-body border-top">
                        <div class="d-flex no-block align-items-center">
                            <h5 class="mb-0">Crear nuevo pPiso</h5>
                        </div>
                        <p class="card-subtitle mb-3 mt-3">
                            Complete los datos del piso que desea registrar en el sistema de almacén.
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
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"  id="code" name="code" value="{{ old('code') }}"   placeholder="P1, P2, S0, etc."   maxlength="50" required>
                                    @error('code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"   id="name" name="name" value="{{ old('name') }}"   placeholder="Planta 1, Sótano, etc." maxlength="100" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Orden</label>
                                    <input type="number" class="form-control @error('order') is-invalid @enderror" id="order" name="order" value="{{ old('order', 0) }}"  min="0" placeholder="0">
                                    @error('order')
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
                                            <option value="1" {{ old('available', '1') == '1' ? 'selected' : '' }}>Disponible</option>
                                            <option value="0" {{ old('available', '1') == '0' ? 'selected' : '' }}>No disponible</option>
                                        </select>
                                    </div>
                                    @error('available')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <label id="available-error" class="error d-none" for="available"></label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"  placeholder="Descripción adicional del piso"  rows="3">{{ old('description') }}</textarea>
                                    @error('description')
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
                                    <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
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
