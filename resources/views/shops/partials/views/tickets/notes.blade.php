<div class="card">
    <div class="card-body">

        
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title fw-semibold">Notas</h5>
                <p class="card-subtitle mb-0">The Benefits of Being a Trending Creator</p>
            </div>
            @if ($ticket->status != 'Closed')
          
                 <a href="javascript:void(0)" id="new-note" class="text-dark fs-6 position-relative nav-icon-hover z-index-5 text-decoration-none">
                    <i  class="ti ti-dots-vertical"></i>
                </a>

            @endif
            
        </div>
    </div>
    @if($notes->isNotEmpty())
        <div class="notes-widgets">

            @foreach ($notes as $note)
                <!-- Comment Row -->
                <div class="notes-item mt-0 p-3 " id="ticketnote_{{$note->id}}">
                

                        <div class="notes-content">
                                <div class="d-flex align-items-ri">
                                    <span class="fs-3 ms-auto">{{ $note->created_at->timezone(setting('default_timezone'))->format('h:i A') }}
                                    ({{ $note->created_at->timezone(setting('default_timezone'))->diffForHumans() }})</span>
                                </div>
                                <h6 class="mt-3">{{$note->notes}}...</h6>
                        </div>

                        <div class="notes-action">
                            <div class="action-icons">
                                @if($note->user_id == Auth::id() || Auth::user()->role == 'manager')
                                <a href="javascript:void(0)" class="note-delete" data-id="{{$note->id}}" onclick="deletePost(event.target)">
                                    <i class="ti ti-edit fs-5" data-id="{{$note->id}}"></i>
                                </a>
                                @endif
                            </div>
                        </div>


                </div>
                <!-- Comment Row -->
            @endforeach
        </div>
    @endif
</div>




@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {

   

    });

</script>


@endpush