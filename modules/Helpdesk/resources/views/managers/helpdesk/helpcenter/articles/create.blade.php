@extends('layouts.theme')

@section('content')

  <div class="row">
    <div class="col-lg-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formArticle" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <input type="hidden" id="body" name="body" value="">

          <div class="card-body">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Crear Artículo</h5>
            </div>
            <p class="card-subtitle mb-3 mt-3">
              Completa los campos necesarios para crear un nuevo artículo del centro de ayuda.
            </p>

            <div class="row">
              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Título *</label>
                  <input type="text" class="form-control" id="title" name="title"
                         placeholder="Ingresa el título del artículo">
                </div>
              </div>

              <div class="col-md-8">
                <div class="mb-3">
                  <label class="control-label col-form-label">Descripción breve</label>
                  <textarea class="form-control" id="description" name="description" rows="2"
                            placeholder="Ingresa una descripción breve (se mostrará en las búsquedas)"></textarea>
                  <small class="form-text text-muted">
                    Resumen corto del artículo que aparecerá en los resultados de búsqueda
                  </small>
                </div>
              </div>

              <div class="col-md-4">
                <div class="mb-3">
                  <label class="control-label col-form-label">Posición</label>
                  <input type="number" class="form-control" id="position" name="position"
                         placeholder="0" value="0" min="0">
                  <small class="form-text text-muted">
                    Orden de visualización (menor primero)
                  </small>
                </div>
              </div>

              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Meta Descripción (SEO)</label>
                  <textarea class="form-control" id="meta_description" name="meta_description" rows="2"
                            placeholder="Descripción optimizada para motores de búsqueda"></textarea>
                  <small class="form-text text-muted">
                    Descripción que aparecerá en los resultados de Google (150-160 caracteres recomendados)
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <label class="control-label col-form-label">Sección *</label>
                  <select class="form-select select2" id="section_id" name="section_id">
                    <option value="">Selecciona una sección</option>
                    @foreach($sections as $sectionItem)
                      <option value="{{ $sectionItem->id }}"
                        @if(isset($section) && $section->id == $sectionItem->id) selected @endif>
                        {{ $sectionItem->parent ? $sectionItem->parent->name . ' > ' : '' }}{{ $sectionItem->name }}
                      </option>
                    @endforeach
                  </select>
                  <small class="form-text text-muted">
                    Selecciona la sección donde se publicará este artículo
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <label class="control-label col-form-label">Etiquetas</label>
                  <select class="form-select select2-tags" id="tags" name="tags[]" multiple>
                  </select>
                  <small class="form-text text-muted">
                    Escribe y presiona Enter para crear nuevas etiquetas
                  </small>
                </div>
              </div>

              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Imagen Destacada</label>
                  <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                  <small class="form-text text-muted">
                    Imagen que se mostrará en la lista de artículos (recomendado: 800x600px)
                  </small>
                </div>
              </div>

              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Contenido *</label>
                  <div id="bodyEditor"></div>
                  <small class="form-text text-muted">
                    Escribe el contenido completo del artículo
                  </small>
                  <label id="body-error" class="error d-none" for="body"></label>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="draft" name="draft" value="1" checked>
                    <label class="form-check-label" for="draft">
                      Guardar como borrador (no publicado)
                    </label>
                  </div>
                  <small class="form-text text-muted">
                    Los borradores no son visibles para los usuarios en el centro de ayuda público
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="hide_from_structure" name="hide_from_structure" value="1">
                    <label class="form-check-label" for="hide_from_structure">
                      Ocultar de la estructura de navegación
                    </label>
                  </div>
                  <small class="form-text text-muted">
                    El artículo será accesible por URL directa pero no aparecerá en listas
                  </small>
                </div>
              </div>

              <div class="col-12">
                <div class="border-top pt-1 mt-4">
                  <a href="{{ route('manager.helpdesk.helpcenter.articles') }}"
                     class="btn btn-secondary px-4 waves-effect waves-light mt-2 me-2">
                    Cancelar
                  </a>
                  <button type="submit" class="btn btn-primary px-4 waves-effect waves-light mt-2">
                    Guardar
                  </button>
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

      // Initialize Select2 for section selector
      $('#section_id').select2({
        placeholder: 'Selecciona una sección',
        allowClear: true,
        width: '100%'
      });

      // Initialize Select2 for tags with tagging support
      $('.select2-tags').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Escribe etiquetas y presiona Enter',
        allowClear: true,
        width: '100%'
      });

      // Quill Editor Configuration
      var toolbarOptions = [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ 'header': 1 }, { 'header': 2 }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'script': 'sub'}, { 'script': 'super' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        [{ 'direction': 'rtl' }],
        [{ 'size': ['small', false, 'large', 'huge'] }],
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [ 'link', 'image', 'video' ],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'font': [] }],
        [{ 'align': [] }],
        ['clean']
      ];

      var bodyEditor = new Quill('#bodyEditor', {
        modules: {
          toolbar: toolbarOptions,
          clipboard: {
            matchVisual: false
          }
        },
        placeholder: 'Escribe el contenido del artículo aquí...',
        theme: 'snow'
      });

      // Update hidden field on editor change
      bodyEditor.on('text-change', function(delta, oldDelta, source) {
        var text = bodyEditor.container.firstChild.innerHTML.replaceAll("<p><br></p>", "");
        $('#body').val(text);
      });

      // Handle editor focus
      bodyEditor.on('selection-change', function (range, oldRange, source) {
        if (range === null && oldRange !== null) {
          $('body').removeClass('overlay-disabled');
        } else if (range !== null && oldRange === null) {
          $('body').addClass('overlay-disabled');
        }
      });

      // Form Validation
      $("#formArticle").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
          title: {
            required: true,
            minlength: 3,
            maxlength: 255,
          },
          description: {
            maxlength: 1000,
          },
          section_id: {
            required: true,
          },
          body: {
            required: true,
            minlength: 10,
          },
          position: {
            number: true,
            min: 0,
          },
          meta_description: {
            maxlength: 500,
          },
        },
        messages: {
          title: {
            required: "El título es necesario.",
            minlength: "El título debe tener al menos 3 caracteres.",
            maxlength: "El título no puede exceder 255 caracteres.",
          },
          description: {
            maxlength: "La descripción no puede exceder 1000 caracteres.",
          },
          section_id: {
            required: "Debes seleccionar una sección.",
          },
          body: {
            required: "El contenido del artículo es necesario.",
            minlength: "El contenido debe tener al menos 10 caracteres.",
          },
          position: {
            number: "La posición debe ser un número.",
            min: "La posición debe ser mayor o igual a 0.",
          },
          meta_description: {
            maxlength: "La meta descripción no puede exceder 500 caracteres.",
          },
        },
        errorElement: "label",
        errorClass: "error",
        errorPlacement: function (error, element) {
          if (element.attr("name") == "body") {
            $("#body-error").removeClass("d-none").html(error.html());
          } else {
            error.insertAfter(element);
          }
        },
        submitHandler: function (form, event) {
          event.preventDefault();

          // Create FormData to handle file upload
          var formData = new FormData(form);

          $.blockUI({
            message: '<i class="icon-spinner4 spinner"></i>',
            overlayCSS: {
              backgroundColor: '#1b2024',
              opacity: 0.8,
              zIndex: 1200,
              cursor: 'wait'
            },
            css: {
              border: 0,
              color: '#fff',
              padding: 0,
              zIndex: 1201,
              backgroundColor: 'transparent'
            }
          });

          $.ajax({
            type: 'POST',
            url: "{{ route('manager.helpdesk.helpcenter.articles.store') }}",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
              $.unblockUI();
              if (data.success) {
                toastr.success(data.message);
                setTimeout(function () {
                  window.location.href = data.redirect;
                }, 1000);
              }
            },
            error: function (xhr) {
              $.unblockUI();
              if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                $.each(errors, function (key, value) {
                  toastr.error(value[0]);
                });
              } else {
                toastr.error('Ocurrió un error al guardar el artículo');
              }
            }
          });
        }
      });

    });

  </script>

@endpush
