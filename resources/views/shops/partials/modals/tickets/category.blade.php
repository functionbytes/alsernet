<!-- Category List-->
<div class="modal fade sprukosearchcategory" id="addcategory" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="sprukocategory_form" name="sprukocategory_form">
                <input type="hidden" name="ticket_id" class="ticket_id">
                @csrf
                @honeypot
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Seleccionar categoria</label>
                        <div class="custom-controls-stacked d-md-flex">
                            <select class="form-control select4-show-search"
                                data-placeholder="Seleccionar categoria" name="category" id="sprukocategorylist">

                            </select>
                        </div>
                        <span id="CategoryError" class="text-danger"></span>
                    </div>
                    <div class="form-group" id="envatopurchase">
                    </div>
                    <div class="form-group" id="selectssSubCategory" style="display: none;">

                        <label class="form-label mb-0 mt-2">Categoria</label>
                        <select class="form-control subcategoryselect" data-placeholder="Seleccionar categoria"
                            name="subscategory" id="subscategory">

                        </select>
                        <span id="subsCategoryError" class="text-danger alert-message"></span>

                    </div>
                    <div class="form-group" id="selectSubCategory">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-outline-danger" data-bs-dismiss="modal">Cerrar</a>
                    <button type="submit" class="btn btn-secondary sprukoapiblock"
                        id="btnsave">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Category List  -->


@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {


// category submit form
$('body').on('submit', '#sprukocategory_form', function(e){
e.preventDefault();
var actionType = $('#pribtnsave').val();
var fewSeconds = 2;
$('#btnsave').html('Sending..');
var formData = new FormData(this);
$.ajax({
type:'POST',
url: SITEURL + "/admin/category/change",
data: formData,
cache:false,
contentType: false,
processData: false,

success: (data) => {
$('#CategoryError').html('');
$('#sprukocategory_form').trigger("reset");
$('#addcategory').modal('hide');
$('#pribtnsave').html('Guardar');
toastr.success(data.success);
window.location.reload();


},
error: function(data){
$('#CategoryError').html('');
$('#CategoryError').html(data.responseJSON.errors.category);
$('#btnsave').html('Guardar');
}
});
})

// category list
$('body').on('click', '.sprukocategory', function(){

var category_id = $(this).data('id');
$('.modal-title').html(`Category`);
$('#CategoryError').html('');
$('#addcategory').modal('show');
$.ajax({
type: "get",
url: SITEURL + "/admin/category/list/" + category_id,
success: function(data){
$('.select4-show-search').select2({
dropdownParent: ".sprukosearchcategory",
});
$('.subcategoryselect').select2({
dropdownParent: ".sprukosearchcategory",
});
$('#sprukocategorylist').html(data.table_data);
$('.ticket_id').val(data.ticket.id);

if(data.ticket.project != null){
$('#subcategory')?.empty();
$('#selectSubCategory .removecategory')?.remove();
let selectDiv = document.querySelector('#selectSubCategory');
let Divrow = document.createElement('div');
Divrow.setAttribute('class','removecategory');
let selectlabel = document.createElement('label');
selectlabel.setAttribute('class','form-label')
selectlabel.innerText = "Projects";
let selecthSelectTag = document.createElement('select');
selecthSelectTag.setAttribute('class','form-control select2-shows-search');
selecthSelectTag.setAttribute('id', 'subcategory');
selecthSelectTag.setAttribute('name', 'project');
selecthSelectTag.setAttribute('data-placeholder','Select Projects');
let selectoption = document.createElement('option');
selectoption.setAttribute('label','Select Projects')
selectDiv.append(Divrow);
// Divrow.append(Divcol3);
Divrow.append(selectlabel);
Divrow.append(selecthSelectTag);
selecthSelectTag.append(selectoption);
$('.select2-shows-search').select2({
dropdownParent: ".sprukosearchcategory",
});
$('#subcategory').append(data.projectop);

}

if(data.ticket.subcategory != null){

$('#selectssSubCategory').show()
$('#subscategory').html(data.subcategoryt);

}else{

if(!data.subcategoryt){
$('#selectssSubCategory').hide();
}else{
$('#selectssSubCategory').show()
$('#subscategory').html(data.subcategoryt);
}
}



},
error: function(data){

}
});
});

        });

</script>


@endpush