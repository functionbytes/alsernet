<div class="card w-100">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title fw-semibold">Historial asignaciones</h5>
                <p class="card-subtitle mb-0">Asignaciones de tickets</p>
            </div>
            <a href="javascript:void(0)"
                class="text-dark fs-6 position-relative nav-icon-hover z-index-5 text-decoration-none"><i
                    class="ti ti-dots-vertical"></i></a>
        </div>
        <div class="card shadow-none mt-3 mb-0">
            <div class="ticket-activity">
                @if($ticket->user_id != null && $ticket->cust != null )
                        <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                            @if ($ticket->cust->image == null)
                                <img src="{{asset('/managers/images/profile/profile.jpg')}}" class="profile-pic rounded-circle" alt="default">
                            @else
                                <img src="{{asset('uploads/profile/'.$ticket->cust->image)}}" class="profile-pic rounded-circle" alt="{{$ticket->cust->image}}">
                            @endif
                            <div>
                                <h6 class="mb-0 fw-semibold">{{$ticket->cust->firstname}} {{$ticket->cust->lastname}}</h6>
                                <span  class="fs-2 text-black fw-semibold">( )</span>
                            </div>
                            <div class="ms-auto text-end">
                                <h6 class="mb-0 text-black">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('date_format'))}}</h6>
                                <span class="fs-2 text-black">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('time_format'))}}</span>
                            </div>
                        </div>
                @endif
                @if($ticket->user_id != null && $ticket->users != null )
                        <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                            @if ($ticket->users->image == null)
                            <img src="{{asset('/managers/images/profile/profile.jpg')}}" class="profile-pic rounded-circle" alt="default">
                            @else
                            <img src="{{asset('uploads/profile/'.$ticket->users->image)}}" class="profile-pic rounded-circle" alt="{{$ticket->users->image}}">
                            @endif
                            <div>
                                <h6 class="mb-0 fw-semibold">{{$ticket->users->firstname}} {{$ticket->users->lastname}}</h6>
                                <span  class="fs-2 text-black fw-semibold">()</span>
                            </div>
                            <div class="ms-auto text-end">
                                <h6 class="mb-0 text-black">
                                    {{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('date_format'))}}</h6>
                                <span
                                    class="fs-2 text-black">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('time_format'))}}</span>
                            </div>
                        </div>
                @endif
                @if($ticket->selfassignuser_id != null && $ticket->selfassign != null)
                    <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                        @if ($ticket->selfassign->image == null)
                            <img src="{{asset('/managers/images/profile/profile.jpg')}}" class="profile-pic rounded-circle" alt="default">
                        @else
                            <img src="{{asset('uploads/profile/'.$ticket->selfassign->image)}}" class="profile-pic rounded-circle" alt="{{$ticket->selfassign->image}}">
                        @endif
                        <div>
                            <h6 class="mb-0 text-black">{{$ticket->selfassign->firstname}} {{$ticket->selfassign->lastname}}</h6>
                            <span class="fs-2 text-black fw-semibold">({{ $ticket->selfassign->type() }})</span>
                            <span class="mb-0 text-black">Self Assigned</span>
                        </div>
                        <div class="ms-auto text-end">
                            <span class="fs-2 text-black">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('time_format'))}}</span>
                        </div>
                    </div>
                @endif
                @if($ticket->selfassignuser_id == null && $ticket->myassignuser != null)
                    <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                            @if ($ticket->myassignuser->image == null)
                            <img src="{{asset('/managers/images/profile/profile.jpg')}}" class="profile-pic rounded-circle" alt="default">
                            @else
                            <img src="{{asset('uploads/profile/'.$ticket->myassignuser->image)}}" class="profile-pic rounded-circle"
                                alt="{{$ticket->myassignuser->image}}">
                            @endif
                            <div>
                                <h6 class="mb-0 fw-semibold">{{$ticket->myassignuser->firstname}} {{$ticket->myassignuser->lastname}}</h6>
                                <span  class="fs-2 text-black fw-semibold">({{ $ticket->myassignuser->type() }})</span>
                            <h6 class="mb-0 text-black">Assigner</h6>
                            </div>
                            <div class="ms-auto text-end">
                               <h6 class="mb-0 text-black">
                                    {{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('date_format'))}}</h6>
                                <span
                                    class="fs-2 text-black ">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('time_format'))}}</span>
                            </div>
                    </div>
                @endif
                @if($ticket->assigns->isNOtEmpty())
                        @foreach ($ticket->assigns as $toassignuser)
                                @if($toassignuser->toassignuser != null)
                                        <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                                            @if ($ticket->toassignuser->image == null)
                                            <img src="{{asset('/managers/images/profile/profile.jpg')}}" class="profile-pic rounded-circle" alt="default">
                                            @else
                                            <img src="{{asset('uploads/profile/'.$ticket->toassignuser->image)}}" class="profile-pic rounded-circle"
                                                alt="{{$ticket->toassignuser->image}}">
                                            @endif
                                            <div>
                                                <h6 class="mb-0 fw-semibold">{{$ticket->toassignuser->firstname}} {{$ticket->toassignuser->lastname}}</h6>
                                                <span  class="fs-2 text-black fw-semibold">({{ $ticket->toassignuser->type() }})</span>
                                                <h6 class="mb-0 text-black">Asignado</h6>
                                            </div>
                                           <div class="ms-auto text-end">
                                                <h6 class="mb-0 text-black"> {{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('date_format'))}}</h6>
                                                <span class="fs-2 text-black ">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('time_format'))}}</span>
                                            </div>
                                        </div>
                                @endif
                        @endforeach
                @endif
                @if($ticket->closedby_user != null)
                    <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                        @if ($ticket->closed->image == null)
                            <img src="{{asset('/managers/images/profile/profile.jpg')}}" class="profile-pic rounded-circle" alt="default">
                        @else
                            <img src="{{asset('uploads/profile/'.$ticket->closed->image)}}" class="profile-pic rounded-circle"
                            alt="{{$ticket->closed->image}}">
                        @endif
                        <div>
                            <h6 class="mb-0 fw-semibold">{{$ticket->closed->firstname}} {{$ticket->closed->lastname}}</h6>
                            <span  class="fs-2 text-black fw-semibold">({{ $ticket->closed->type() }})</span>
                            <span class="fs-2 text-black fw-semibold">Cerrado</span>
                        </div>
                        <div class="ms-auto text-end">
                            <h6 class="mb-0 text-black">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('date_format'))}}</h6>
                            <span class="fs-2 text-black ">{{$ticket->created_at->timezone(setting('default_timezone'))->format(setting('time_format'))}}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
