@extends('layouts.callcenters')

@section('content')
    <div class="container-fluid">
        <div class="row">

            <div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold">$10,230</h4>
                        <p class="mb-2 fs-3">Expense</p>
                        <div id="expense"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body p-4">
                        <h4 class="fw-semibold">$65,432</h4>
                        <p class="mb-1 fs-3">Sales</p>
                        <div id="sales" class="sales-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="p-2 bg-light-primary rounded-2 d-inline-block mb-3">
                            <img src="../../dist/images/svgs/icon-cart.svg" alt="" class="img-fluid" width="24" height="24">
                        </div>
                        <div id="sales-two" class="mb-3"></div>
                        <h4 class="mb-1 fw-semibold d-flex align-content-center">$16.5k<i class="ti ti-arrow-up-right fs-5 text-success"></i></h4>
                        <p class="mb-0">Sales</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-items-stretch">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="p-2 bg-light-info rounded-2 d-inline-block mb-3">
                            <img src="../../dist/images/svgs/icon-bar.svg" alt="" class="img-fluid" width="24" height="24">
                        </div>
                        <div id="growth" class="mb-3"></div>
                        <h4 class="mb-1 fw-semibold d-flex align-content-center">24%<i class="ti ti-arrow-up-right fs-5 text-success"></i></h4>
                        <p class="mb-0">Growth</p>
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
                                                    <a href="{{route('callcenter.tickets.view', $ticket->uid)}}" class="text-inherit subject">{{ $ticket->subject }}</a>
                                                </h6>
                                                <p class="fs-2 mb-0 ">
                                                <ul class="d-flex custom-ul">
                                                    <li class="pe-2 ">#{{$ticket->ticket_id}}</li>
                                                    <li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top" title="Fecha">
                                                        <i class="fa-duotone fa-calendar-days"></i>
                                                        {{$ticket->created_at->format(setting('date_format'))}}
                                                    </li>

                                                    @if($ticket->category != null)
                                                        <li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $ticket->category->title }}">
                                                            <i class="fa-duotone fa-layer-group"></i>
                                                            {{Str::limit($ticket->category->title, '40')}}
                                                        </li>
                                                    @else
                                                            ~
                                                    @endif
                                                    @if($ticket->last_reply == null)
                                                        <li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top" title="Última respuesta">
                                                            <i class="fa-duotone fa-clock"></i>
                                                            {{$ticket->created_at->diffForHumans()}}
                                                        </li>
                                                    @else
                                                        <li class="px-2 " data-bs-toggle="tooltip" data-bs-placement="top" title="Última respuesta">
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
                                        <span class="badge bg-light-{{ $ticket->status->slug }} text-{{ $ticket->status->slug }} fw-semibold fs-2 gap-1 d-inline-flex align-items-center">{{ $ticket->status->title }}</span>
                                    </td>
                                    <td>
                                        <div class="dropdown dropstart">
                                            <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots fs-5"></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-3" href="{{route('callcenter.tickets.view', $ticket->uid)}}"><i class="fs-4 ti ti-plus"></i>Visualizar</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-3" href="#"><i class="fs-4 ti ti-edit"></i>Edit</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-3" href="#"><i class="fs-4 ti ti-trash"></i>Delete</a>
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




