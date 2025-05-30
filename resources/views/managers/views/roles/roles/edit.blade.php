@extends('layouts.managers')

@section('content')
    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">
            <div class="card w-100">
                <form id="formRoles" enctype="multipart/form-data" role="form" onSubmit="return false">
                    {{ csrf_field() }}
                    <input type="hidden" name="id" id="id" value="{{ $role->id }}">


                    <div class="card-body border-top">
                        <h5 class="mb-0">Editar rol</h5>
                        <p class="card-subtitle mb-3 mt-3">Complete la información para registrar un nuevo rol en el sistema.</p>

                        <div class="row">
                            <div class="col-6 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" name="name"  value="{{ $role->name }}" placeholder="Ej: inventory-supervisor" required>
                            </div>
                            <div class="col-6 mb-3">
                                    <label class="form-label">Guard</label>
                                    <select class="form-select select2" name="guard_name">
                                        <option value="web" {{ $role->guard_name == 'web' ? 'selected' : '' }}>Web</option>
                                        <option value="api" {{ $role->guard_name == 'api' ? 'selected' : '' }}>API</option>
                                    </select>
                            </div>

                            <div class="col-12">
                                <div class="border-top pt-1 mt-4">
                                    <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $("#formRoles").validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 50,
                    },
                    guard_name: {
                        required: true,
                    }
                },
                messages: {
                    name: {
                        required: "El nombre del rol es obligatorio.",
                        minlength: "Debe contener al menos 3 caracteres.",
                        maxlength: "No puede exceder los 50 caracteres."
                    },
                    guard_name: {
                        required: "Debe seleccionar un guard."
                    }
                },
                submitHandler: function (form) {
                    var formData = new FormData(form);
                    var $submitButton = $(form).find('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.roles.update') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function (response) {
                            if (response.success === true) {
                                toastr.success(response.message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right",
                                    timeOut: 1000,
                                    onHidden: function () {
                                        window.location.href = "{{ route('manager.roles') }}";
                                    }
                                });
                            } else {
                                toastr.warning(response.message, "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right",
                                    timeOut: 1000,
                                    onHidden: function () {
                                        $submitButton.prop('disabled', false);
                                    }
                                });
                            }
                        },
                        error: function (xhr) {
                            let errorMessage = 'Error desconocido';
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).map(err => err[0]).join('<br>');
                            }
                            toastr.error(errorMessage, "Error de validación", {
                                closeButton: true,
                                progressBar: true,
                                positionClass: "toast-bottom-right"
                            });
                            $('.errors').html(errorMessage).removeClass('d-none');
                            $submitButton.prop('disabled', false);
                        }
                    });
                }
            });
        });
    </script>
@endpush
