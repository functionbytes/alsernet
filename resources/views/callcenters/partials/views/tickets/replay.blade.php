
@if ($allowreply)
    @if ($ticket->status->slug != 'closed' && $ticket->status->slug != 'suspend' )

            <div class="card">
                    <form id="formrReplay" enctype="multipart/form-data" role="form" onSubmit="return false">
                        @csrf
                        <input type="hidden" name="slack" value="{{ $ticket->id }}">
                            <textarea style="display: none" id="comment" name="comment"></textarea>

                        <div class="card-body">
                            <h4 class="mb-4 fw-semibold">Respuesta</h4>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="canned" class="control-label col-form-label">Categorias</label>
                                    <select class="form-control select2" id="canned" name="canned">
                                        @foreach($canneds as $id => $name)
                                            <option value="{{ $id }}" >{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="status" class="control-label col-form-label">Estado</label>
                                    <select class="form-control select2" id="status" name="status">
                                        @foreach($status as $id => $name)
                                            <option value="{{ $id }}" >{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Comentario</label>
                                    <div class="">
                                        <div id="comments"></div>
                                    </div>
                                    <label id="comment-error" class="error d-none" for="comment"></label>
                                </div>
                            </div>

                            <button class="btn btn-primary w-100">Enviar</button>

                        </div>
                    </form>
                </div>
    @endif
@endif


@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {


        @if($ticket->status != "Closed")

        // onhold ticket status
        let hold = document.getElementById('onhold');
        let text = document.querySelector('.status');
        let hold1 = document.querySelectorAll('.hold');
        let status = false;


        if(hold != null){
            hold.addEventListener('click',(e)=>{
            if( status == false){

            }
        }, false)

        if(document.getElementById('onhold').hasAttribute("checked") == true){
        statusDiv();
        status = true;
        }

        }



        function statusDiv(){
        let Div = document.createElement('div')
        Div.setAttribute('class','d-block pt-4');
        Div.setAttribute('id','holdremove');

        let newField = document.createElement('textarea');
        newField.setAttribute('type','text');
        newField.setAttribute('name','note');
        newField.setAttribute('class',`form-control @error('note') is-invalid @enderror`);
        newField.setAttribute('rows',3);
        newField.setAttribute('placeholder','"Leave a message for On-Hold');
        newField.innerText = `{{old('note',$ticket->note)}}`;
        Div.append(newField);
        text.append(Div);
        }


        hold1.forEach((element,index)=>{
        element.addEventListener('click',()=>{
        let myobj = document.getElementById("holdremove");
        myobj?.remove();

        status = false
        }, false)
        })

        @endif


    $('.sprukostatuschange').on('click', function(e){

            $(e.target).prop("checked", false);
            if(this){
                $(this).prop("checked", true);
            }else{
                $(this).prop("checked", false);
            }

            if(e.target.value == 'On-Hold'){

                let teatareasecond = $('#holdremove textarea').val();

                if(teatareasecond == ''){
                    $('#btnsprukodisable').attr('disabled', true);
                }else{
                    $('#btnsprukodisable').attr('disabled', false);
                }
                }else{
                    if($('#summernoteempty').val() == ''){
                        $('#btnsprukodisable').attr('disabled', true);
                    }else{
                        $('#btnsprukodisable').attr('disabled', false);
                    }
            }

        });

        // Canned Maessage Select2
        $('#canneds').select2({
            minimumResultsForSearch: '',
            placeholder: "Search",
            width: '100%'
        });

        var cannedJson=@php echo json_encode($cannedsjson);@endphp

        $('.select2').on('click', () => {
            let selectField = document.querySelectorAll('.select2-search__field')
            selectField.forEach((element,index)=>{
                    element?.focus();
            });
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


      var toolbarOption = [
      ['clean']
      ];

      $('#mySelect').on('change', function() {
        var value = $(this).val(); // Obtener el valor seleccionado

        if(value == 5){

            statusDiv();
            status = true;

        }else if(value == 6){

        }else if(value == 9){2

        }

    });


      var comment = new Quill('#comments', {
        modules: {
            toolbar: toolbarOption,
            clipboard: {
                matchVisual: false
            }
        },
        placeholder: 'Escriba aquí...',
        theme: 'snow'
      });


      comment.on('selection-change', function (range, oldRange, source) {
        if (range === null && oldRange !== null) {
            $('body').removeClass('overlay-disabled');
        } else if (range !== null && oldRange === null) {
            $('body').addClass('overlay-disabled');
        }
      });

      comment.on('text-change', function (delta, oldDelta, source) {
         var text = comment.container.firstChild.innerHTML.replaceAll("<p><br></p>", "");
        $('#comment').val(text);
      });


      // On Change Canned Messages display
    $('body').on('change', '#canneds', function(){
        let optval = $(this).val();
        console.log(optval);
        comment.insertText(1,cannedJson[optval].messages);
    });


// $('.summernote').on('summernote.keyup summernote.keydown', function(we, e) {
// if((e.target.value == '') || $('.summernote').val() == ''){
// $('#btnsprukodisable').attr ('disabled', true);
// }else{
// $('#btnsprukodisable').attr('disabled', false);
// }
// });

    });

</script>


@endpush
