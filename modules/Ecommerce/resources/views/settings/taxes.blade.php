@extends('layouts.theme')

@section('title', 'Impuestos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Impuestos'])
@endsection

@section('content')
    @include('core::components.alerts')

    @php
        $taxesEnabled = old('taxes_enabled', $settings['taxes_enabled'] ?? '') == '1';
    @endphp

    <form action="{{ route('settings.ecommerce.taxes.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">

                    {{-- Habilitar impuestos --}}
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="taxes_enabled" value="1" id="taxes_enabled"
                                {{ $taxesEnabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="taxes_enabled">Habilitar impuestos</label>
                        </div>
                    </div>

                    {{-- Campos condicionales --}}
                    <div id="taxes-fields-block" style="{{ $taxesEnabled ? '' : 'display:none' }}">
                        <div class="row g-3 mt-0">

                            {{-- Tasa predeterminada --}}
                            <div class="col-12">
                                <label for="default_tax_rate" class="form-label fw-semibold">Tasa impositiva predeterminada</label>
                                <select name="default_tax_rate" id="default_tax_rate"
                                    class="form-select @error('default_tax_rate') is-invalid @enderror">
                                    <option value="">No</option>
                                    @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}"
                                            {{ old('default_tax_rate', $settings['default_tax_rate'] ?? '') == $tax->id ? 'selected' : '' }}>
                                            {{ $tax->title }} ({{ $tax->percentage }}%)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Importante: se aplicará si no se selecciona ningún impuesto en el producto.</div>
                                @error('default_tax_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Precio con impuestos --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="display_product_price_including_taxes" value="1" id="display_product_price_including_taxes"
                                        {{ old('display_product_price_including_taxes', $settings['display_product_price_including_taxes'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="display_product_price_including_taxes">Mostrar el precio del producto incluyendo impuestos</label>
                                </div>
                            </div>

                            {{-- Campos de factura en checkout --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="display_tax_fields_at_checkout_page" value="1" id="display_tax_fields_at_checkout_page"
                                        {{ old('display_tax_fields_at_checkout_page', $settings['display_tax_fields_at_checkout_page'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="display_tax_fields_at_checkout_page">Mostrar campos de información de la factura de la empresa en la página de pago</label>
                                </div>
                                <div class="form-text">Si está habilitado, los campos de información de la factura de la empresa se mostrarán en la página de pago. Es obligatorio cumplimentar los campos de información de la empresa.</div>
                            </div>

                            {{-- Información fiscal en precio --}}
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="display_tax_in_product_price" value="1" id="display_tax_in_product_price"
                                        {{ old('display_tax_in_product_price', $settings['display_tax_in_product_price'] ?? '') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="display_tax_in_product_price">¿Mostrar información fiscal en el precio del producto?</label>
                                </div>
                                <div class="form-text">Mostrar texto como "(Incluido X% GST)" o "(Excluido X% GST)" junto a los precios del producto en la página de detalles del producto.</div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
        </div>
    </form>

    {{-- Gestión de impuestos --}}
    <div class="mb-2 d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-0">Gestión de impuestos</h5>
            <div class="text-muted small">Ver y administrar sus impuestos</div>
        </div>
        <a href="{{ route('ecommerce.taxes.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Crear
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Porcentaje</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxes as $tax)
                        <tr>
                            <td>{{ $tax->title }}</td>
                            <td>{{ $tax->percentage }}%</td>
                            <td>{{ $tax->priority }}</td>
                            <td>
                                @if($tax->status === 'published')
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('ecommerce.taxes.edit', $tax) }}">Editar</a></li>
                                        <li>
                                            <a class="dropdown-item" href="#"
                                               onclick="event.preventDefault(); if(confirm('¿Eliminar este impuesto?')) document.getElementById('del-tax-{{ $tax->id }}').submit();">
                                                Eliminar
                                            </a>
                                            <form id="del-tax-{{ $tax->id }}" action="{{ route('ecommerce.taxes.destroy', $tax) }}" method="POST" class="d-none">
                                                @csrf @method('DELETE')
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay impuestos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#taxes_enabled').on('change', function () {
        $('#taxes-fields-block').slideToggle(150);
    });
});
</script>
@endpush
