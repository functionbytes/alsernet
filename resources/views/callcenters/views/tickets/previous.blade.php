@extends('layouts.callcenters')

@section('content')
<div class="container-fluid">
	<div class="row">

		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $totaltickets }}</h4>
					<p class="mb-2 fs-3">Tickets totales</p>
					<div id="expense"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $totalactivetickets }}</h4>
					<p class="mb-1 fs-3">Tickets activos</p>
					<div id="sales" class="sales-chart"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $totalclosedtickets }}</h4>
					<p class="mb-1 fs-3">Tickets cerrados</p>
					<div id="sales" class="sales-chart"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $suspendticketcount }}</h4>
					<p class="mb-1 fs-3">Tickets suspendidos</p>
					<div id="sales" class="sales-chart"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $selfassigncount }}</h4>
					<p class="mb-1 fs-3">Tickets asignados</p>
					<div id="sales" class="sales-chart"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $recentticketcount }}</h4>
					<p class="mb-1 fs-3">Tickets recientes</p>
					<div id="sales" class="sales-chart"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $myassignedticketcount }}</h4>
					<p class="mb-1 fs-3">Mis asignados</p>
					<div id="sales" class="sales-chart"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
			<div class="card w-100">
				<div class="card-body p-4">
					<h4 class="fw-semibold">{{ $myclosedticketcount }}</h4>
					<p class="mb-1 fs-3">Mis cerrados</p>
					<div id="sales" class="sales-chart"></div>
				</div>
			</div>
		</div>

	</div>

	<div class="row">
		<!-- Weekly Stats -->

		<!-- Top Performers -->
		<div class="col-lg-12 d-flex align-items-strech">
			<div class="card w-100">
				<div class="card-body">
					<div class="d-sm-flex d-block align-items-center justify-content-between mb-7">
						<div class="mb-3 mb-sm-0">
							<h5 class="card-title fw-semibold">Top Performers</h5>
							<p class="card-subtitle mb-0">Best Employees</p>
						</div>
						<div>
							<select class="form-select">
								<option value="1">March 2023</option>
								<option value="2">April 2023</option>
								<option value="3">May 2023</option>
								<option value="4">June 2023</option>
							</select>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table align-middle text-nowrap mb-0">
							<thead>
								<tr class="text-muted fw-semibold">
									<th scope="col">Detalle</th>
									<th scope="col">Cliente</th>
									<th scope="col">Estado</th>
									<th scope="col">Acción</th>
								</tr>
							</thead>
							<tbody class="border-top">
								@foreach($tickets as $ticket)
								<tr class="ticket-details">
									<td class="ps-0">
										<div class="d-flex align-items-center">
											<div>
												<h6 class="fw-semibold mb-1">
													<a href="{{route('callcenter.tickets.view', $ticket->uid)}}"
														class="text-inherit subject">{{ $ticket->subject }}</a>
												</h6>
												<p class="fs-2 mb-0 ">
												<ul class="d-flex custom-ul">
													<li class="pe-2 ">#{{$ticket->number}}</li>
													<li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top"
														title="Fecha">
														<i class="fa-duotone fa-calendar-days"></i>
														{{$ticket->created_at->format(setting('date_format'))}}
													</li>
													@if($ticket->priority != null)
													<li class="px-2 preference preference-{{ $ticket->priority->slug }}"
														data-bs-toggle="tooltip" data-bs-placement="top"
														title="{{ $ticket->priority->title }}">
														{{ $ticket->priority->title }}
													</li>
													@else
													~
													@endif
													@if($ticket->category != null)
													<li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top"
														title="{{ $ticket->category->title }}">
														<i class="fa-duotone fa-layer-group"></i>
														{{Str::limit($ticket->category->title, '40')}}
													</li>
													@else
													~
													@endif
													@if($ticket->last_reply == null)
													<li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top"
														title="Última respuesta">
														<i class="fa-duotone fa-clock"></i>
														{{$ticket->created_at->diffForHumans()}}
													</li>
													@else
													<li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top"
														title="Última respuesta">
														<i class="fa-duotone fa-clock"></i>
														{{$ticket->last_reply->diffForHumans()}}
													</li>
													@endif
												</ul>
												</p>
											</div>
										</div>
									</td>
									<td>
										{{ $ticket->user->firstname }} {{ $ticket->user->lastname }}
									</td>

									<td>
										<span
											class="badge bg-light-{{ $ticket->status->slug }} fw-semibold fs-2 gap-1 d-inline-flex align-items-center">
											{{ $ticket->status->title }}
										</span>
									</td>

									<td>
										<div class="dropdown dropstart">
											<a href="#" class="text-muted" id="dropdownMenuButton"
												data-bs-toggle="dropdown" aria-expanded="false">
												<i class="ti ti-dots fs-5"></i>
											</a>
											<ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
												<li>
													<a class="dropdown-item d-flex align-items-center gap-3"
														href="{{route('callcenter.tickets.view', $ticket->uid)}}">Visualizar</a>
												</li>
												<li>
													<a class="dropdown-item d-flex align-items-center gap-3"
														href="{{route('callcenter.tickets.view', $ticket->uid)}}">Edit</a>
												</li>
												<li>
													<a class="dropdown-item d-flex align-items-center gap-3"
														href="{{route('callcenter.tickets.view', $ticket->uid)}}"></i>Delete</a>
												</li>
											</ul>
										</div>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>

@endsection





@section('modal')

    @include ('supports.partials.modals.tickets.assign')
    @include ('supports.partials.modals.tickets.notes')
    @include ('supports.partials.modals.tickets.priority')
    @include ('supports.partials.modals.tickets.category')

@endsection


@push('scripts')


<script type="text/javascript">
    "use strict";

var ticrat = {!! json_encode(setting('ticketrating') == 'off') !!}

if(ticrat){
    if(document.getElementById('closed')){
        if(document.getElementById('closed').checked){
            document.getElementById('ratingonoff').classList.add('d-block');
            document.getElementById('ratingonoff').classList.remove('d-none');
        }
        document.getElementById('closed').addEventListener("click", function(){
            document.getElementById('ratingonoff').classList.add('d-block');
            document.getElementById('ratingonoff').classList.remove('d-none');
        });
    }

    if(document.getElementById('onhold')){
        document.getElementById('onhold').addEventListener("click", function(){
            document.getElementById('ratingonoff').classList.add('d-none');
            document.getElementById('ratingonoff').classList.remove('d-block');
        });
    }

    if(document.getElementById('Inprogress1')){
        document.getElementById('Inprogress1').addEventListener("click", function(){
            document.getElementById('ratingonoff').classList.add('d-none');
            document.getElementById('ratingonoff').classList.remove('d-block');
        });
    }

    if(document.getElementById('Inprogress2')){
        document.getElementById('Inprogress2').addEventListener("click", function(){
            document.getElementById('ratingonoff').classList.add('d-none');
            document.getElementById('ratingonoff').classList.remove('d-block');
        });
    }

    if(document.getElementById('Inprogress3')){
        document.getElementById('Inprogress3').addEventListener("click", function(){
            document.getElementById('ratingonoff').classList.add('d-none');
            document.getElementById('ratingonoff').classList.remove('d-block');
        });
    }
}

// Image Upload
var uploadedDocumentMap = {}
Dropzone.options.documentDropzone = {
	url: '{{url('/admin/ticket/imageupload/' .$ticket->ticket_id)}}',
	maxFilesize: '{{setting('FILE_UPLOAD_MAX')}}', // MB
	addRemoveLinks: true,
	acceptedFiles: '{{setting('FILE_UPLOAD_TYPES')}}',
	headers: {
	'X-CSRF-TOKEN': "{{ csrf_token() }}"
	},
	success: function (file, response) {
	$('form').append('<input type="hidden" name="comments[]" value="' + response.name + '">')
	uploadedDocumentMap[file.name] = response.name
	},
	removedfile: function (file) {
	file.previewElement.remove()
	var name = ''
	if (typeof file.file_name !== 'undefined') {
		name = file.file_name
	} else {
		name = uploadedDocumentMap[file.name]
	}
	$('form').find('input[name="comments[]"][value="' + name + '"]').remove()
	},
	init: function () {
	@if(isset($project) && $project->document)
		var files =
		{!! json_encode($project->document) !!}
		for (var i in files) {
		var file = files[i]
		this.options.addedfile.call(this, file)
		file.previewElement.classList.add('dz-complete')
		$('form').append('<input type="hidden" name="comments[]" value="' + file.file_name + '">')
		}
	@endif
	this.on('error', function(file, errorMessage) {
		if (errorMessage.message) {
			var errorDisplay = document.querySelectorAll('[data-dz-errormessage]');
			errorDisplay[errorDisplay.length - 1].innerHTML = errorMessage.message;
		}
	});
	}
}



// Edit Form
function showEditForm(id) {
	var x = document.querySelector(`#supportnote-icon-${id}`);

	if (x.style.display == "block") {
		x.style.display = "none";
	}
	else {

		x.style.display = "block";
	}
}


// Variables
var SITEURL = '{{url('')}}';

// Csrf field
$.ajaxSetup({
	headers: {
	'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	}
});


let ticket_status = {!! json_encode($ticket->status) !!};

if(ticket_status == 'Closed'){
    let status= false;
    let tickettoarticleList = document.querySelectorAll('.tickettoarticle');
    tickettoarticleList.forEach(ele =>{
        ele.addEventListener('click', ()=>{
            for (let index = 0; index < tickettoarticleList.length; index++) {
                if(tickettoarticleList[index].checked){
                    $('#ticket_to_article').removeClass("disabled");
                    status = false;
                    break;
                }else{
                    $('#ticket_to_article').addClass("disabled");
                    console.log('else');
                }
            }
            if(status){
                console.log(status);
                $('#ticket_to_article').addClass("disabled");
            }
        })
    })

    $('body').on('click', '#ticket_to_article', function () {
        let ticket_Id = {!! json_encode($ticket->ticket_id) !!};
        var ticket_to_article_Id = [];
        let tickettoarticle = document.querySelectorAll('.tickettoarticle');

        if(tickettoarticle.length){
            tickettoarticle.forEach(e => {
                if(e.checked){
                    ticket_to_article_Id.push(e.getAttribute('value'))
                }
            });
        }

        console.log(ticket_to_article_Id,ticket_Id);

        if(ticket_to_article_Id.length){
            var per = {!! json_encode(Auth::user()->can('Article Create')) !!}
            if(per){
                window.location.href = `${SITEURL}/admin/ticketarticle/${ticket_Id}/${ticket_to_article_Id}`;
            }else{
                toastr.error('You do not have permission to create an article.');
            }
        }else{
            toastr.error('Please select the field');
        }

    });
}



// Remove the assigned from the ticket
$('body').on('click', '#btnremove', function () {
	var asid = $(this).data("id");

	swal({
			title: `Are you sure you want to unassign this agent?`,
			text: "This agent may no longer exist for this ticket.",
			icon: "warning",
			buttons: true,
			dangerMode: true,
		})
		.then((willDelete) => {
		if (willDelete) {

			$.ajax({
				type: "get",
				url: SITEURL + "/admin/assigned/update/"+asid,
				success: function (data) {
				toastr.success(data.success);
				location.reload();

				},
				error: function (data) {
				console.log('Error:', data);
				}
				});

		}
	});



});

// Reopen the ticket
$('body').on('click', '#reopen', function(){
	var reopenid = $(this).data('id');
	$.ajax({
		type:'POST',
		url: SITEURL + "/admin/ticket/reopen/" + reopenid,
		data: {
			reopenid:reopenid
		},
		success:function(data){
			console.log(data);
			toastr.success(data.success);
			location.reload();

		},
		error:function(data){
			toastr.error(data);
		}
	});

});


// delete note dunction
function deletePost(event) {
	var id  = $(event).data("id");
	let _url = `{{url('/admin/ticketnote/delete/${id}')}}`;

	let _token   = $('meta[name="csrf-token"]').attr('content');

	swal({
		title: `Are you sure you want to continue?`,
		text: "This might erase your records permanently",
		icon: "warning",
		buttons: true,
		dangerMode: true,
	})
	.then((willDelete) => {
		if (willDelete) {
			$.ajax({
				url: _url,
				type: 'DELETE',
				data: {
				_token: _token
				},
				success: function(response) {
					toastr.success(response.success);
					$("#ticketnote_"+id).remove();
				},
				error: function (data) {
					console.log('Error:', data);
				}
			});
		}
	});
}



// if(ticket_status != 'Closed' && ticket_status != 'Suspend'){

// 	let ticketId = {!! json_encode($ticket->id) !!};

// 	let dataEntry = document.querySelector('.note-editable')

// 	dataEntry.addEventListener('blur', function(){
// 	console.log('blur');
// 	let userid = {!! json_encode(Auth::user()->id) !!};

// 	$.ajax({
// 	method:'POST',
// 	url: SITEURL + "/admin/employeesreplyingremove/",
// 	data: {
// 		userID : userid,
// 		ticketId : ticketId,
// 	},
// 	});
// 	})
// 	dataEntry.addEventListener('focus', function(){
// 		console.log('focus');

// 	let userid = {!! json_encode(Auth::user()->id) !!};

// 	$.ajax({
// 	method:'POST',
// 	url: SITEURL + "/admin/employeesreplyingstore/",
// 	data: {
// 	userID : userid,
// 	ticketId : ticketId,
// 	},
// 	});
// 	})

// 	setInterval(() => {
//     $.ajax({
//     method:'GET',
//     url: SITEURL + "/admin/getemployeesreplying/"+ticketId,
//     success : function (data) {
//     let replyStatus = document.querySelector('#replyStatus');
//     replyStatus.innerHTML = '';
//     // replyStatus.innerText = data;
//     if(data['employees'].length ){
//     let mainCard = document.createElement('div');
//     mainCard.classList.add('d-flex','gap-2', 'mt-sm-0', 'mt-3');

//     let divCard = document.createElement('div');
//     divCard.classList.add('px-1','py-1','d-flex','align-items-center','border','rounded-pill');
//     let avatar;
//     if (data['employees'][0].image == null){
//     avatar = `<span class="avatar  brround border border-success avatar-typing-active ms-3" style="background-image: url(../../uploads/profile/user-profile.png)"></span>`
//     }else{
//     avatar = `<span class="avatar  brround border border-success avatar-typing-active" style="background-image: url(../../uploads/profile/${data['employees'][0].image})"></span>`
//     }
//     let icon = document.createElement('span');

//     icon.classList.add('avatar','brround','me-0','bg-transparent')
//     icon.style.backgroundImage = 'url(../../assets/images/typing.gif)'
//     let p = document.createElement('p');
//     p.classList.add('font-weight-semibold', 'd-block','d-sm-flex','my-auto');
// 	let span = document.createElement('span');
//     span.classList.add('font-weight-semibold','text-nowrap');
//     span.textContent = data['employees'][0].name;
//     // let small = document.createElement('small');
//     // small.classList.add('text-muted','ms-1','text-nowrap');
//     // small.textContent = 'Working on it';
//     p.append(span);
//     // p.append(small);
//     divCard.append(icon);
//     divCard.append(p);
//     divCard.insertAdjacentHTML('beforeend' ,avatar);
//     mainCard.append(divCard);

//     let divCardStacked = document.createElement('div');
//     divCardStacked.classList.add('px-1','d-flex','align-items-center','rounded-pill','avatar-list','avatar-list-stacked');
//     let spanCount;

//     data['employees'].forEach(function(emp, i){
//     if(i != 0){
//     let avatar;
//     if (data['employees'][0].image == null){
//     avatar = `<span class="avatar  brround" style="background-image: url(../../uploads/profile/user-profile.png)" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="${emp['name']}"></span>`
//     }else{
//     avatar = `<span class="avatar  brround" style="background-image: url(../../uploads/profile/${emp.image})" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="${emp['name']}"></span>`
//     }

//     divCardStacked.insertAdjacentHTML('beforeend' ,avatar);
//     }
//     mainCard.append(divCardStacked);
//     })

//     replyStatus.append(mainCard);
//     const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
//     const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
//     };
//     }
//     });

//     }, 1000);


// //store the data of textarea on local storage
// $('.note-editable').on('keyup', function(e){
// localStorage.setItem(`usermessage${ticketId}`, e.target.innerHTML)
// })

// $(window).on('load', function(){
// document.querySelector(".note-editable p").remove();
// if(localStorage.getItem(`usermessage${ticketId}`) == '' || localStorage.getItem(`usermessage${ticketId}`) == null || localStorage.getItem(`usermessage${ticketId}`) == undefined){
// document.querySelector(".note-editable").innerHTML = document.querySelector(".note-editable").innerHTML
// $('#btnsprukodisable').attr ('disabled', true);
// }else{
// document.querySelector(".note-editable").innerHTML += localStorage.getItem(`usermessage${ticketId}`)
// $('#btnsprukodisable').attr ('disabled', false);
// }
// });

$('.deletelocalstorage').click(function(){
localStorage.removeItem(`usermessage${ticketId}`)
});

$('body').on('keyup keydown', '#holdremove textarea', function(e){
	if((e.target.value == '') || $('.summernote').val() == ''){
		$('#btnsprukodisable').attr ('disabled', true);
	}else{
		$('#btnsprukodisable').attr('disabled', false);
	}

});


// TICKET DELETE SCRIPT
$('body').on('click', '#show-delete', function () {
var _id = $(this).data("id");
swal({
title: `Are you sure you want to continue?`,
text: "This might erase your records permanently",
icon: "warning",
buttons: true,
dangerMode: true,
})
.then((willDelete) => {
if (willDelete) {
$.ajax({
type: "get",
url: SITEURL + "/admin/delete-ticket/"+_id,
success: function (data) {
toastr.success(data.success);
location.replace('{{route('callcenter.dashboard')}}');
},
error: function (data) {
console.log('Error:', data);
}
});
}
});

});

// TICKET DELETE SCRIPT END

let suspend = document.getElementById('suspend'),
	unsuspend = document.getElementById('unsuspend');
/*** Suspend Ticket ***/
	if(suspend != null){

		suspend.addEventListener('click', function(event){
			event.preventDefault();

			const ticket_id = suspend.getAttribute('data-id');

			swal({
				title: `Are you sure you want to continue?`,
				text: "This might suspend the ticket permanently",
				icon: "warning",
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete) {
					let xhr = new XMLHttpRequest();
					let url = "";
					let _token   = $('meta[name="csrf-token"]').attr('content');
					xhr.open('POST', url, true);
					xhr.setRequestHeader("Content-Type", "application/json");

					// Create a state change callback
					xhr.onreadystatechange = function () {
						if (xhr.readyState === 4 && xhr.status === 200) {
							let data = JSON.parse(this.responseText)
							// Print received data from server
							toastr.success(data.success)
							location.reload();

						}

					};

					// Converting JSON data to string
					var data = JSON.stringify({ "ticket_id": ticket_id, "_token": _token});

					// Sending data with the request
					xhr.send(data);
				}
			});

		})
	}
/*** End Suspend Ticket ***/
/*** UnSuspend Ticket ***/
	if(unsuspend != null)
	{
		unsuspend.addEventListener('click', function(event){
			event.preventDefault();

			const ticket_id = unsuspend.getAttribute('data-id')
				unsuspend = 'Inprogress';

			swal({
				title: `Are you sure you want to continue?`,
				text: "This action may remove the ticket from suspension.}",
				icon: "warning",
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete) {
					let xhr = new XMLHttpRequest();
					let url = "";
					let _token   = $('meta[name="csrf-token"]').attr('content');
					xhr.open('POST', url, true);
					xhr.setRequestHeader("Content-Type", "application/json");

					// Create a state change callback
					xhr.onreadystatechange = function () {
						if (xhr.readyState === 4 && xhr.status === 200) {
							let data = JSON.parse(this.responseText)
							// Print received data from server
							toastr.success(data.success)
							location.reload();

						}

					};

					// Converting JSON data to string
					var data = JSON.stringify({ "ticket_id": ticket_id, "unsuspend": unsuspend, "_token": _token});

					// Sending data with the request
					xhr.send(data);
				}
			});

		})
	}
/*** End UnSuspend Ticket ***/

</script>


@endpush
