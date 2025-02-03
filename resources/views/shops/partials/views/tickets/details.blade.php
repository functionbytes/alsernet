<div class="card">
    <div class="card-body">
        <h4 class="card-title">Ticket Información</h4>
        <table class="table mt-3 table-borderless v-middle">
            <tbody>
                <tr>
                    <td class="ps-0">Ticket ID</td>
                    <td class="ps-0 text-end"><strong>{{ $ticket->number }}</strong></td>
                </tr>
                <tr>
                    <td class="ps-0">Fecha</td>
                    <td class="ps-0 text-end">
                        {{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('time_format'))}}</td>
                </tr>
                <tr>

                    <td class="ps-0">Categoria</td>
                    <td class="ps-0 text-end">
                        <div class="bg-light-primary badge">
                            <p class="fs-3 text-primary fw-semibold mb-0">{{ $ticket->category->title }}</p>
                            <p class="fs-3 mb-0"><i class="fa-duotone fa-gear-code"></i></p>
                        </div>
                    </td>
                </tr>
                <tr>

                    <td class="ps-0">Prioridad</td>
                    <td class="ps-0 text-end">
                        <div class="bg-light-primary badge">
                            <p class="fs-3 text-primary fw-semibold mb-0">{{ $ticket->priority->title }}</p>
                           <p class="fs-3 mb-0"><i class="fa-duotone fa-gear-code"></i></p>
                        </div>
                </tr>
                <tr>

                    <td class="ps-0">Estado</td>
                    <td class="ps-0 text-end">
                        <div class="bg-light-primary badge">
                            <p class="fs-3 text-primary fw-semibold mb-0">{{ $ticket->status->title }}</p>
                            <p class="fs-3 mb-0"><i class="fa-duotone fa-gear-code"></i></p>
                        </div>
                </tr>
              
            </tbody>
        </table>
    </div>
</div>





@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {
$('body').on('click','#selfassigid', function(e){

e.preventDefault();

let id = $(this).data('id');

$.ajax({
method:'POST',
url: '',
data: {
id : id,
},
success: (data) => {
toastr.success(data.success);
location.reload();
},
error: function(data){

}
});
})
        });

</script>


@endpush
