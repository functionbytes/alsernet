@extends('layouts.managers')

@section('content')

    @include('managers.includes.card', ['title' => 'Actividades'])



    <div class="card overflow-hidden chat-application">
        <div class="d-flex align-items-center justify-content-between gap-3 m-3 d-lg-none">
            <button class="btn btn-primary d-flex" type="button" data-bs-toggle="offcanvas" data-bs-target="#chat-sidebar" aria-controls="chat-sidebar">
                <i class="ti ti-menu-2 fs-5"></i>
            </button>
            <form class="position-relative w-100">
                <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh" placeholder="Search Contact">
                <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
            </form>
        </div>
        <div class="d-flex w-100">
            <div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">

                <ul class="list-group" style="height: calc(100vh - 400px)" data-simplebar>
                    <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">CATEGORIES</li>
                    @foreach ($models as $key => $model)
                        <li class="list-group-item border-0 p-0 mx-9">
                            <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 list-model" data-model="{{$model}}" data-user="{{$user->uid}}">
                                <i class="ti ti-bookmark fs-5 text-primary"></i>{{$model}} {{ $modelCounts[$model] ?? 0 }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="d-flex w-100">
                <div class="min-width-340">
                    <div class="border-end user-chat-box h-100">
                        <div class="px-4 pt-9 pb-6 d-none d-lg-block">
                            <form class="position-relative">
                                <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh" placeholder="Search" />
                                <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                            </form>
                        </div>
                        <div class="app-chat">
                            <ul class="chat-users items-activitys" data-simplebar>
                                @foreach($activities as $activitie)
                                                                    <li>
                                                                        <a  class="px-4 py-3 bg-hover-light-black d-flex align-items-center chat-user bg-light action-activity" id="chat_user_1" data-activity="{{ $activitie->id }}">
                                                                            <span class="position-relative">
                                                                              <img src="./images/profile/user-4.jpg" alt="user-4" width="40" height="40" class="rounded-circle">
                                                                            </span>
                                                                            <div class="ms-6 d-inline-block w-75">
                                                                                <h6 class="mb-1 fw-semibold chat-title" data-username="James Anderson">
                                                                                    {{ $activitie->description }} </h6>
                                                                                <span class="fs-2 text-body-color d-block">{{ $activitie->event }}</span>
                                                                            </div>
                                                                        </a>
                                                                    </li>
                                @endforeach


                            </ul>
                        </div>
                    </div>
                </div>
                <div class="w-100">
                    <div class="chat-container h-100 w-100">
                        <div class="chat-box-inner-part h-100">
                            <div class="chatting-box app-email-chatting-box">
                                <div class="p-9 py-3 border-bottom chat-meta-user d-flex align-items-center justify-content-between">
                                    <h5 class="text-dark mb-0 fw-semibold">Contact Details</h5>
                                    <ul class="list-unstyled mb-0 d-flex align-items-center">
                                        <li class="d-lg-none d-block">
                                            <a class="text-dark back-btn px-2 fs-5 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)">
                                                <i class="ti ti-arrow-left"></i>
                                            </a>
                                        </li>
                                        <li class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="important">
                                            <a class="text-dark px-2 fs-5 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)">
                                                <i class="ti ti-star"></i>
                                            </a>
                                        </li>
                                        <li class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Edit">
                                            <a class="d-block text-dark px-2 fs-5 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                        </li>
                                        <li class="position-relative" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete">
                                            <a class="text-dark px-2 fs-5 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)">
                                                <i class="ti ti-trash"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="position-relative overflow-hidden">
                                    <div class="position-relative">
                                        <div class="chat-box p-9" style="height: calc(100vh - 428px)" data-simplebar>
                                            <div class="chat-list chat active-chat" data-user-id="1">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-4.jpg" alt="user4" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Dr. Bonnie Barstow </h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr>

                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="2">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-4.jpg" alt="user4" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Jonathan Higgins</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="3">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-3.jpg" alt="user3" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Michael Knight </h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="4">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-8.jpg" alt="user8" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Angus MacGyver</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="5">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-2.jpg" alt="user2" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Rick Wright</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="6">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-1.jpg" alt="user1" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Sledge Hammer</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="7">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-3.jpg" alt="user3" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Peter Thornton</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="8">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-1.jpg" alt="user1" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Devon Miles</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="9">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-1.jpg" alt="user1" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Michael Knight</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                            <div class="chat-list chat" data-user-id="10">
                                                <div class="hstack align-items-start mb-7 pb-1 align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="./images/profile/user-1.jpg" alt="user1" width="72" height="72" class="rounded-circle" />
                                                        <div>
                                                            <h6 class="fw-semibold fs-4 mb-0">Mike Torello</h6>
                                                            <p class="mb-0">Sales Manager</p>
                                                            <p class="mb-0">Digital Arc Pvt. Ltd.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">Phone number</p>
                                                        <h6 class="fw-semibold mb-0">+1 (203) 3458</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Email address</p>
                                                        <h6 class="fw-semibold mb-0">alexandra@modernize.com</h6>
                                                    </div>
                                                    <div class="col-12 mb-9">
                                                        <p class="mb-1 fs-2">Address</p>
                                                        <h6 class="fw-semibold mb-0">312, Imperical Arc, New western corner</h6>
                                                    </div>
                                                    <div class="col-4 mb-7">
                                                        <p class="mb-1 fs-2">City</p>
                                                        <h6 class="fw-semibold mb-0">New York</h6>
                                                    </div>
                                                    <div class="col-8 mb-7">
                                                        <p class="mb-1 fs-2">Country</p>
                                                        <h6 class="fw-semibold mb-0">United Stats</h6>
                                                    </div>
                                                </div>
                                                <div class="border-bottom pb-7 mb-4">
                                                    <p class="mb-2 fs-2">Notes</p>
                                                    <p class="mb-3 text-dark">
                                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque bibendum
                                                        hendrerit lobortis. Nullam ut lacus eros. Sed at luctus urna, eu fermentum diam.
                                                        In et tristique mauris.
                                                    </p>
                                                    <p class="mb-0 text-dark">Ut id ornare metus, sed auctor enim. Pellentesque nisi magna, laoreet a augue eget, tempor volutpat diam.</p>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-primary fs-2">Edit</button>
                                                    <button class="btn btn-danger fs-2">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="offcanvas offcanvas-start user-chat-box" tabindex="-1" id="chat-sidebar" aria-labelledby="offcanvasExampleLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="offcanvasExampleLabel"> Contact </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <ul class="list-group" style="height: calc(100vh - 150px)" data-simplebar>
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)">
                            <i class="ti ti-inbox fs-5"></i>All Contacts </a>
                    </li>
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)">
                            <i class="ti ti-star"></i>Starred </a>
                    </li>
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)">
                            <i class="ti ti-file-text fs-5"></i>Pening Approval </a>
                    </li>
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)">
                            <i class="ti ti-alert-circle"></i>Blocked </a>
                    </li>
                    <li class="border-bottom my-3"></li>
                    <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">CATEGORIES</li>
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)">
                            <i class="ti ti-bookmark fs-5 text-primary"></i>Engineers </a>
                    </li>
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)">
                            <i class="ti ti-bookmark fs-5 text-warning"></i>Support Staff </a>
                    </li>
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-2 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1" href="javascript:void(0)">
                            <i class="ti ti-bookmark fs-5 text-success"></i>Sales Team </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

@endsection



@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {

            $(".list-model").on("click", function() {

                var model = $(this).data('model');
                var user = $(this).data('user');

                $.ajax({
                    url: "{{ route('manager.enterprises.users.lists') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "POST",
                    contentType: false,
                    processData: false,
                    data: [
                        'user' = user,
                        'model' = model,
                    ],
                    success: function(data) {



                        <li>
                            <a href="javascript:void(0)" class="px-4 py-3 bg-hover-light-black d-flex align-items-center chat-user bg-light" id="chat_user_1" data-user-id="1">
                            <span class="position-relative">
                              <img src="./images/profile/user-4.jpg" alt="user-4" width="40" height="40" class="rounded-circle">
                            </span>
                                <div class="ms-6 d-inline-block w-75">
                                    <h6 class="mb-1 fw-semibold chat-title" data-username="James Anderson">Dr. Bonnie Barstow </h6>
                                    <span class="fs-2 text-body-color d-block">barstow@ modernize.com</span>
                                </div>
                            </a>
                        </li>

                    }
                    });

                    });



                    });

@endpush

