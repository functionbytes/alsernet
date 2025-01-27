@extends('layouts.managers')

@section('content')

  <div class="row">
     <div class="col-lg-8 col-sm-12 d-flex align-items-stretch">

      <div class="card w-100">

        <form id="formCanned" enctype="multipart/form-data" role="form" onSubmit="return false">

          {{ csrf_field() }}

          <input type="hidden" id="id" name="id" value="{{ $canned->id }}">
          <input type="hidden" id="slack" name="slack" value="{{ $canned->uid }}">
          <textarea style="display: none"  id="description" name="description">{!! $canned->messages !!}</textarea>

          <div class="card-body border-top">
            <div class="d-flex no-block align-items-center">
              <h5 class="mb-0">Editar respuestas</h5>

            </div>
            <p class="card-subtitle mb-3 mt-3">
              Este espacio está diseñado para que puedas actualizar y modificar la información de manera eficiente y segura. A continuación, encontrarás diversos <mark><code>campos</code></mark> que corresponden a los datos previamente suministrados. Te invitamos a revisar y ajustar cualquier información que consideres necesario actualizar para mantener tus datos al día.
            </p>

            <div class="row">

              <div class="col-12">
                <div class="mb-3">
                    <label  class="control-label col-form-label">Titulo</label>
                    <input type="text" class="form-control" id="title"  name="title"  placeholder="Ingresa titulo" value=" {{ $canned->title  }}">
                  </div>
              </div>
              <div class="col-12">
                <div class="mb-3">
                  <label class="control-label col-form-label">Estado</label>
                  <div class="input-group">
                    {!! Form::select('available', $availables, $canned->available , ['class' => 'select2 form-control' ,'name' => 'available', 'id' => 'available' ]) !!}
                  </div>
                  <label id="available-error" class="error d-none" for="available"></label>
                </div>
              </div>
        <div class="col-12">
          <label class="control-label col-form-label">Contenido</label>
          <div class="">
            <div id="descriptions">{!! $canned->messages !!}</div>
          </div>
          <label id="description-error" class="error d-none" for="description"></label>
        </div>
            </div>

          </div>


          <div class="action-form border-top mt-4">
            <div class="text-center">
              <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                Guardar
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="col-lg-4 col-sm-12 d-flex align-items-stretch">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">Respuesta Predeterminados</h4>
          <table class="table mt-3 table-borderless v-middle">
            <tbody>
              <tr>

                <td class="ps-0">&lcub;&lcub;app_name&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>El nombre de la aplicación</strong></td>
              </tr>
              <tr>

                <td class="ps-0">&lcub;&lcub;site_url&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>La URL del sitio</strong></td>
              </tr>
              <tr>

                <td class="ps-0">&lcub;&lcub;ticket_id&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>El ID del billete</strong></td>
              </tr>
              <tr>

                <td class="ps-0">&lcub;&lcub;ticket_user&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>El nombre del cliente que ha abierto el ticket.</strong></td>
              </tr>
              <tr>

                <td class="ps-0">&lcub;&lcub;ticket_title&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>El título del ticket</strong></td>
              </tr>
              <tr>

                <td class="ps-0">&lcub;&lcub;ticket_priority&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>La prioridad del ticket</strong></td>
              </tr>
              <tr>

                <td class="ps-0">&lcub;&lcub;user_reply&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>El nombre del empleado que responde al ticket.</strong></td>
              </tr>
              <tr>

                <td class="ps-0">&lcub;&lcub;user_role&rcub;&rcub;</td>
                <td class="ps-0 text-end"><strong>El papel del empleado</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

@endsection



@push('scripts')

  <script type="text/javascript">

    $(document).ready(function() {


      $("#formCanned").validate({
        submit: false,
        ignore: ".ignore",
        rules: {
          title: {
            required: true,
            minlength: 3,
            maxlength: 100,
          },
          available: {
            required: true,
          },
          description: {
            required: true,
          },

        },
        messages: {
          title: {
            required: "El parametro es necesario.",
            minlength: "Debe contener al menos 3 caracter",
            maxlength: "Debe contener al menos 100 caracter",
          },
          available: {
            required: "Es necesario un respuestas.",
          },
          description: {
            required: "Es necesario un descripción.",
          },
        },
        submitHandler: function(form) {

          var $form = $('#formCanned');
          var formData = new FormData($form[0]);
          var id = $("#id").val();
          var title = $("#title").val();
          var available = $("#available").val();
          var description = $("#description").val();

          formData.append('id', id);
          formData.append('title', title);
          formData.append('available', available);
          formData.append('description', description);

          $.ajax({
            url: "{{ route('manager.chats.canneds.update') }}",
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            contentType: false,
            processData: false,
            data: formData,
            success: function(data) {
             window.location = "{{ route('manager.chats.canneds') }}";
            }
          });

        }

      });
var toolbarOptions = [
['bold', 'italic', 'underline', 'strike'],
['blockquote', 'code-block'],
[{
'header': 1
}, {
'header': 2
}],
[{
'list': 'ordered'
}, {
'list': 'bullet'
}],
[{
'script': 'sub'
}, {
'script': 'super'
}],
[{
'indent': '-1'
}, {
'indent': '+1'
}],
[{
'direction': 'rtl'
}],
[{
'size': ['small', false, 'large', 'huge']
}],
[{
'header': [1, 2, 3, 4, 5, 6, false]
}],
['link', 'image', 'video'],
[{
'color': []
}, {
'background': []
}],
[{
'font': []
}],
[{
'align': []
}],

['clean']
];

// https://quilljs.com/docs/quickstart/
var toolbarOption = [
['clean']
];

var description = new Quill('#descriptions', {

modules: {
toolbar: toolbarOption,
clipboard: {
matchVisual: false
}
},
placeholder: 'Escriba aquí...',
theme: 'snow'
});

//Avoid Quick Search Toggle
description.on('selection-change', function (range, oldRange, source) {
if (range === null && oldRange !== null) {
$('body').removeClass('overlay-disabled');
} else if (range !== null && oldRange === null) {
$('body').addClass('overlay-disabled');
}
});

description.on('text-change', function (delta, oldDelta, source) {

var text = description.container.firstChild.innerHTML.replaceAll("<p><br></p>", "");
$('#description').val(text);
});

    });

  </script>


@endpush



