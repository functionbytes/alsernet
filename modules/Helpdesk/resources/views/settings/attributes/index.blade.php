@extends('layouts.theme')

@section('title', 'Atributos personalizados')

@section('page_header')
    @include('core::components.card', ['title' => 'Atributos personalizados'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-body">
                <div class="mb-0 border-bottom pb-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="{{ route('settings.helpdesk.attributes.create') }}" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-1"></i>Nuevo atributo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card">
            <div class="card-body">
                <h5 class="mb-1">Filtros de búsqueda</h5>
                <p class="text-muted mb-3">Filtra los atributos por nombre, formato o permisos</p>
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Búsqueda</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Buscar atributo..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Formato</label>
                            <select name="format" class="form-select select2" id="formatSelect">
                                <option value="">Todos los formatos</option>
                                <option value="text"          {{ request('format') === 'text'          ? 'selected' : '' }}>Texto</option>
                                <option value="textarea"      {{ request('format') === 'textarea'      ? 'selected' : '' }}>Área de texto</option>
                                <option value="number"        {{ request('format') === 'number'        ? 'selected' : '' }}>Número</option>
                                <option value="switch"        {{ request('format') === 'switch'        ? 'selected' : '' }}>Interruptor</option>
                                <option value="rating"        {{ request('format') === 'rating'        ? 'selected' : '' }}>Calificación</option>
                                <option value="select"        {{ request('format') === 'select'        ? 'selected' : '' }}>Selección</option>
                                <option value="checkboxGroup" {{ request('format') === 'checkboxGroup' ? 'selected' : '' }}>Grupo de checkboxes</option>
                                <option value="date"          {{ request('format') === 'date'          ? 'selected' : '' }}>Fecha</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Permisos</label>
                            <select name="permission" class="form-select select2" id="permissionSelect">
                                <option value="">Todos los permisos</option>
                                <option value="userCanView"  {{ request('permission') === 'userCanView'  ? 'selected' : '' }}>Usuario puede ver</option>
                                <option value="userCanEdit"  {{ request('permission') === 'userCanEdit'  ? 'selected' : '' }}>Usuario puede editar</option>
                                <option value="agentCanEdit" {{ request('permission') === 'agentCanEdit' ? 'selected' : '' }}>Agente puede editar</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100 d-block">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabs por tipo de modelo --}}
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="attrTypeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-contact-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-contact-attrs" type="button" role="tab">
                            <i class="fas fa-user me-1"></i>Atributos de contacto
                            <span class="badge bg-secondary ms-1">{{ $contactCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-conversation-btn" data-bs-toggle="tab"
                                data-bs-target="#tab-conversation-attrs" type="button" role="tab">
                            <i class="fas fa-comments me-1"></i>Atributos de conversación
                            <span class="badge bg-secondary ms-1">{{ $conversationCount }}</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-contact-attrs" role="tabpanel">
                        @include('helpdesk::settings.attributes._table', ['rows' => $contactAttributes])
                    </div>
                    <div class="tab-pane fade" id="tab-conversation-attrs" role="tabpanel">
                        @include('helpdesk::settings.attributes._table', ['rows' => $conversationAttributes])
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#formatSelect, #permissionSelect').select2({
        placeholder: 'Selecciona una opción',
        allowClear: true,
        width: '100%',
    });

    $('#filterForm select').on('change', function () {
        $('#filterForm').submit();
    });

    $(document).on('change', '.attribute-toggle', function () {
        var $toggle = $(this);
        var url     = $toggle.data('url');

        $toggle.prop('disabled', true);

        $.ajax({
            url: url,
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                toastr.success(response.message || 'Estado actualizado', 'Éxito');
            },
            error: function (xhr) {
                // Revert the visual state on failure
                $toggle.prop('checked', !$toggle.prop('checked'));
                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Error al actualizar el estado';
                toastr.error(msg, 'Error');
            },
            complete: function () {
                $toggle.prop('disabled', false);
            },
        });
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
