<!-- Add note-->
<div class="modal fade" id="newNotes" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

             <form id="formNotes" enctype="multipart/form-data" role="form" onSubmit="return false">
                <input type="hidden" name="slack" id="slack" value="{{  $ticket->uid }}">
                @csrf

                <div class="modal-body">
                    <div class="form-group">
                        <textarea class="form-control" rows="10" name="notes"  id="notes" required></textarea>
                        <label id="notes-error" class="error d-none" for="notes"></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-outline-danger" data-bs-dismiss="modal">Cerrar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End  Add note  -->


@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {

        /* When user click add note button */
        $('#new-note').on('click', function () {
            $('#note_form').trigger("reset");
            $('.modal-title').html(`Agregar nota`);
            $('#newNotes').modal('show');
        });




$("#formNotes").validate({
    submit: false,
    ignore: ".ignore",
    rules: {
        notes: {
            required: true,
        },
    },
    messages: {
        notes: {
            required: "Es necesario un descripción.",
        },
    },
    submitHandler: function(form) {

    var $form = $('#formNotes');
    var formData = new FormData($form[0]);
    var notes = $('#formNotes').find("[name='notes']").val();
    var slack = $('#formNotes').find("[name='slack']").val();

    formData.append('notes', notes);
    formData.append('slack', slack);

    $.ajax({
        url: "{{ route('callcenter.tickets.notes.store') }}",
            headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            contentType: false,
            processData: false,
            data: formData,
            success: function(data) {
                console.log("data");
            }
        });

    }

    });


        // Note Submit button
        $('body').on('submit', '#note_form', function (e) {
            e.preventDefault();
            var actionType = $('#btnsave').val();
            var fewSeconds = 2;
            $('#btnsave').html(`Sending`);
            $('#btnsave').prop('disabled', true);
            setTimeout(function(){
                $('#btnsave').prop('disabled', false);
            }, fewSeconds*1000);
        var formData = new FormData(this);
        $.ajax({
            type:'POST',
            url: SITEURL + "/admin/note/create",
            data: formData,
            cache:false,
            contentType: false,
            processData: false,

        success: (data) => {
        $('#note_form').trigger("reset");
        $('#newNotes').modal('hide');
        $('#btnsave').html('Guardar');
        location.reload();
        toastr.success(data.success);

        },
        error: function(data){
        console.log('Error:', data);
        $('#btnsave').html('Guardar');
        }
        });
        });


        });

</script>


@endpush
