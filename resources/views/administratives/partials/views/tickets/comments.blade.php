

@empty($comments)
        
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4 ">
                <h4 class="mb-0 fw-semibold">Comentarios</h4>
                <span class="badge bg-light-primary text-primary fs-4 fw-semibold px-6 py-8 rounded">{{ count($comments)
                    }}</span>
            </div>
            <div class="position-relative">
                @foreach ($comments as $comment)
                @if($comment->user_id != null)
                @if ($loop->first)
                <div class="p-4 rounded-2 bg-light mb-3">
                    <div class="d-flex align-items-center gap-3">
                        @if ($comment->cust->image == null)
                        <img src="{{asset('uploads/profile/user-profile.png')}}" class="rounded-circle" alt="default">
                        @else
                        <img src="{{asset('uploads/profile/'. $comment->cust->image)}}" class="rounded-circle"
                            alt="default">
                        @endif
                        <h6 class="fw-semibold mb-0 fs-4">{{ $comment->cust->username }}</h6>
                        <span class="p-1 bg-light-dark rounded-circle d-inline-block">{{ $comment->cust->userType }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <small class="text-muted"><i class="feather feather-clock"></i>
                            @if($comment->created_at->timezone(setting('default_timezone'))->format('Y-m-d') ==
                            now()->timezone(setting('default_timezone'))->format('Y-m-d'))
                            {{ $comment->created_at->timezone(setting('default_timezone'))->format('h:i A') }} ({{
                            $comment->created_at->timezone(setting('default_timezone'))->diffForHumans() }})
                            @else
                            {{ $comment->created_at->timezone(setting('default_timezone'))->format('D, d M Y, h:i A') }} ({{
                            $comment->created_at->timezone(setting('default_timezone'))->diffForHumans() }})
                            @endif
                        </small>
                    </div>
                    <p class="my-3">{!! $comment->comment !!}</p>
                    <div class="d-flex align-items-center gap-2">
                        <a class="text-white d-flex align-items-center justify-content-center bg-secondary p-2 fs-4 rounded-circle"
                            href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="Reply">
                            <i class="ti ti-arrow-back-up"></i>
                        </a>
                    </div>
                </div>
                @else
                <div class="p-4 rounded-2 bg-light mb-3">
                    <div class="d-flex align-items-center gap-3">
                        @if ($comment->cust->image == null)
                        <img src="{{asset('uploads/profile/user-profile.png')}}" class="rounded-circle" alt="default">
                        @else
                        <img src="{{asset('uploads/profile/'. $comment->cust->image)}}" class="rounded-circle"
                            alt="default">
                        @endif

                        <h6 class="fw-semibold mb-0 fs-4">{{ $comment->cust->username }}</h6>
                        <span class="p-1 bg-light-dark rounded-circle d-inline-block">{{ $comment->cust->userType }}</span>
                    </div>
                    <p class="my-3">{!! $comment->comment !!}</p>
                    <div class="d-flex align-items-center gap-2">
                        <a class="text-white d-flex align-items-center justify-content-center bg-secondary p-2 fs-4 rounded-circle"
                            href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-title="Reply">
                            <i class="ti ti-arrow-back-up"></i>
                        </a>
                    </div>
                </div>
                @endif
                @else
                <div class="p-4 rounded-2 bg-light mb-3 ms-7">
                    <div class="d-flex align-items-center gap-3">
                        <img src="./managers/images/profile/user-3.jpg" alt="" class="rounded-circle" width="40"
                            height="40">
                        <h6 class="fw-semibold mb-0 fs-4">Irene Hanson</h6>
                        <span class="p-1 bg-light-dark rounded-circle d-inline-block"></span>
                    </div>
                    <p class="my-3">Uborofgic be rof lom sedge vapozi ohoinu nutremcuc ro ko atmeg anrov git ve vuj ki
                        teb or.
                        Lohi hafa faddegon horoz ebema kew idful ducam nev rol iga wikkobsu sucdu gud.
                    </p>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>

@endempty



@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {

// // ticket comment delete
// $('body').on('click', '#deletecomment', function () {
// var id = $(this).data("id");
// console.log(id);
// swal({
// title: `Are you sure you want to delete this comment?`,
// text: "This might erase your records permanently.",
// icon: "warning",
// buttons: true,
// dangerMode: true,
// })
// .then((willDelete) => {
// if (willDelete) {

// $.ajax({
// type: "get",
// url: SITEURL + "/admin/ticket/deletecomment/"+id,
// success: function (data) {
// toastr.success(data.success);
// location.reload();

// },
// error: function (data) {
// console.log('Error:', data);
// }
// });
// }
// });
// });

// // Scrolling Js Start
// var page = 1;
// $(window).scroll(function() {
// if($(window).scrollTop() + $(window).height() >= $(document).height()) {
// page++;
// loadMoreData(page);
// }
// });

// function loadMoreData(page){
// $.ajax(
// {
// url: '?page=' + page,
// type: "get",

// })
// .done(function(data)
// {
// $("#spruko_loaddata").append(data.html);
// console.log(data.html);
// })
// .fail(function(jqXHR, ajaxOptions, thrownError)
// {
// alert('server not responding...');
// });
// }
// // End Scrolling Js

// // ReadMore JS
// let readMore = document.querySelectorAll('.readmores')
// readMore.forEach(( element, index)=>{
// if(element.clientHeight <= 200) { element.children[0].classList.add('end') } else{
//     element.children[0].classList.add('readMore') } }) $(`.readMore`).showmore({ closedHeight: 300,
//     buttonTextMore: 'Read More' , buttonTextLess: 'Read Less' , buttonCssClass: 'showmore-button' , animationSpeed: 0.5
//     });

         });

</script>


@endpush
