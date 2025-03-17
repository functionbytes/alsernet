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
            <div class="d-flex">
                <div class="w-30 d-none d-lg-block border-end user-chat-box">
                    <div class="px-4 pt-9 pb-6">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="position-relative">
                                    <img src="./images/profile/user-1.jpg" alt="user1" width="54" height="54" class="rounded-circle">
                                    <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                      <span class="visually-hidden">New alerts</span>
                                    </span>
                                </div>
                                <div class="ms-3">

                                    @if($operatorID)
                                        <div class="d-flex align-items-center ms-2">
                                            @if($user->find($operatorID)->image == null)
                                                <span class="avatar brround" style="background-image: url(../uploads/profile/user-profile.png)"></span>
                                            @else
                                                <span class="avatar brround" style="background-image: url(../uploads/profile/{{$user->find($operatorID)->image}})"></span>
                                            @endif
                                                <h6 class="fw-semibold mb-2">{{$user->find($operatorID)->name}}</h6>
                                                <p class="mb-0 fs-2">Mis chats abiertos</p>
                                        </div>
                                    @else

                                        <h6 class="fw-semibold mb-2">Nuevo chat</h6>
                                        <p class="mb-0 fs-2">Mis chats abiertos</p>
                                    @endif

                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="text-dark fs-6 nav-icon-hover text-decoration-none" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item d-flex align-items-center gap-2 border-bottom" href="javascript:void(0)"><span><i class="ti ti-settings fs-4"></i></span>Setting</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)"><span><i class="ti ti-help fs-4"></i></span>Help and feadback</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)"><span><i class="ti ti-layout-board-split fs-4"></i></span>Enable split View mode</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2 border-bottom" href="javascript:void(0)"><span><i class="ti ti-table-shortcut fs-4"></i></span>Keyboard shortcut</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)"><span><i class="ti ti-login fs-4"></i></span>Sign Out</a></li>
                                </ul>
                            </div>
                        </div>
                        <form class="position-relative mb-4">
                            <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh" placeholder="Search Contact" />
                            <i class="ti ti-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                        </form>
                        <div class="dropdown">
                            <a class="text-muted fw-semibold d-flex align-items-center" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Recent Chats<i class="ti ti-chevron-down ms-1 fs-5"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="javascript:void(0)">Sort by time</a></li>
                                <li><a class="dropdown-item border-bottom" href="javascript:void(0)">Sort by Unread</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0)">Hide favourites</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="app-chat">
                        <ul class="chat-users" style="height: calc(100vh - 496px)" data-simplebar>

                            @php
                                $emptyConversation = true;
                            @endphp

                            @foreach ($filteredLiveCust as $LiveCust)
                                @if($LiveCust && !$LiveCust->lastMessage->delete)
                                    {{$emptyConversation = false}}
                                    <li data-id={{$LiveCust->id}}>
                                        <a href="javascript:void(0)" class="px-4 py-3 bg-hover-light-black d-flex align-items-start justify-content-between chat-user bg-light" id="chat_user_1" data-user-id="1">
                                            <div class="d-flex align-items-center">
                                                  <span class="position-relative" id="new-chat-user-bg" style="background-color: {{ randomColorGenerator(0.5) }}">
                                                       @php
                                                           $currentOnlineUsers = setting('liveChatCustomerOnlineUsers');
                                                           $onlineUsersArray = explode(',', $currentOnlineUsers);
                                                           $userOnline = in_array($LiveCust->id, $onlineUsersArray);
                                                       @endphp
                                                      @if($userOnline)
                                                          <span class="avatar-status bg-green"></span>
                                                      @else
                                                          <span class="avatar-status "></span>
                                                      @endif
                                                    <img src="./images/profile/user-1.jpg" alt="user1" width="48" height="48" class="rounded-circle" />
                                                    <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                                      <span class="visually-hidden">{{ strtoupper(substr($LiveCust->username, 0, 1)) }}</span>
                                                    </span>
                                                  </span>
                                                <div class="ms-3 d-inline-block w-75">
                                                    <h6 class="mb-1 fw-semibold chat-title" data-username="{{$LiveCust->username}}">{{$LiveCust->username}}</h6>
                                                    <span class="fs-3 text-truncate text-body-color d-block" >
                                                        {{ $LiveCust->lastMessage->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="fs-2 mb-0 text-muted" data-initial-24time='{{ \Carbon\Carbon::parse($LiveCust->lastMessage->created_at)->timezone(setting('default_timezone')) }}'>{{ $LiveCust->lastMessage->created_at->diffForHumans() }}</p>

                                            <div class="dropdown">
                                                <a class="text-dark fs-6 nav-icon-hover text-decoration-none" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2 border-bottom reAssignModalTrigger" href="javascript:void(0)" custId={{$LiveCust->id}} data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                                            <span><i class="ti ti-settings fs-4"></i></span>Re asignar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item d-flex align-items-center gap-2"  href="javascript:void(0);" deleteRouteLink="{{route('admin.livechatConversationDelete')}}?unqid={{$LiveCust->cust_unique_id}}">
                                                            <span><i class="ti ti-help fs-4"></i></span> Eliminar
                                                        </a>
                                                    </li>

                                                </ul>
                                            </div>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                            @if($emptyConversation)
                                 <div class="text-center mt-5 p-2 bg-warning-transparent text-default">
                                     <span>A partir de ahora, no hay discusiones de chat en curso.</span>
                                 </div>
                            @endif

                        </ul>
                    </div>
                </div>
                <div class="w-50 w-xs-100 chat-container">
                    <div class="chat-box-inner-part h-100">
                        <div class="chat-not-selected h-100 d-none">
                            <div class="d-flex align-items-center justify-content-center h-100 p-5">
                                <div class="text-center">
                        <span class="text-primary">
                          <i class="ti ti-message-dots fs-10"></i>
                        </span>
                                    <h6 class="mt-2">Open chat from the list</h6>
                                </div>
                            </div>
                        </div>
                        <div class="chatting-box d-block">
                            <div class="p-9 border-bottom chat-meta-user d-flex align-items-center justify-content-between">
                                <div class="hstack gap-3 current-chat-user-name">
                                    <div class="position-relative">
                                        <img src="./images/profile/user-1.jpg" alt="user1" width="48" height="48" class="rounded-circle" />
                                        <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                            <span class="visually-hidden">New alerts</span>
                                        </span>
                                    </div>
                                    <div class="">
                                        <h6 class="mb-1 name fw-semibold"></h6>
                                        <p class="mb-0">Away</p>
                                    </div>
                                </div>
                                <ul class="list-unstyled mb-0 d-flex align-items-center">
                                    <li><a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 text-decoration-none" href="javascript:void(0)"><i class="ti ti-phone"></i></a></li>
                                    <li><a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 text-decoration-none" href="javascript:void(0)"><i class="ti ti-video"></i></a></li>
                                    <li>
                                        <!-- <a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 text-decoration-none" href="javascript:void(0)" type="button" data-bs-toggle="offcanvas" data-bs-target="#app-chat-offcanvas" aria-controls="offcanvasScrolling">
                                          <i class="ti ti-menu-2"></i>
                                        </a> -->
                                        <a class="chat-menu text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)">
                                            <i class="ti ti-menu-2"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="position-relative overflow-hidden d-flex">
                                <div class="position-relative d-flex flex-grow-1 flex-column">
                                    <div class="chat-box p-9" style="height: calc(100vh - 442px)" data-simplebar>
                                        <div class="chat-list chat active-chat" data-user-id="1">
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 d-inline-block fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> I want more detailed information. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark mb-1 d-inline-block rounded-1  fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 fs-3"> They got there early, and they got really good seats. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="rounded-2 overflow-hidden">
                                                        <img src="./images/products/product-1.jpg" alt="" class="w-100">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- 2 -->
                                        <div class="chat-list chat" data-user-id="2">
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 d-inline-block fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> I want more detailed information. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark mb-1 d-inline-block rounded-1  fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 fs-3"> They got there early, and they got really good seats. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="rounded-2 overflow-hidden">
                                                        <img src="./images/products/product-1.jpg" alt="" class="w-100">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- 3 -->
                                        <div class="chat-list chat" data-user-id="3">
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 d-inline-block fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> I want more detailed information. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark mb-1 d-inline-block rounded-1  fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 fs-3"> They got there early, and they got really good seats. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="rounded-2 overflow-hidden">
                                                        <img src="./images/products/product-1.jpg" alt="" class="w-100">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- 4 -->
                                        <div class="chat-list chat" data-user-id="4">
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 d-inline-block fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> I want more detailed information. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark mb-1 d-inline-block rounded-1  fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 fs-3"> They got there early, and they got really good seats. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="rounded-2 overflow-hidden">
                                                        <img src="./images/products/product-1.jpg" alt="" class="w-100">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- 5 -->
                                        <div class="chat-list chat" data-user-id="5">
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 d-inline-block fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="p-2 bg-light rounded-1 d-inline-block text-dark fs-3"> I want more detailed information. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-end">
                                                <div class="text-end">
                                                    <h6 class="fs-2 text-muted">2 hours ago</h6>
                                                    <div class="p-2 bg-light-info text-dark mb-1 d-inline-block rounded-1  fs-3"> If I don’t like something, I’ll stay away from it. </div>
                                                    <div class="p-2 bg-light-info text-dark rounded-1 fs-3"> They got there early, and they got really good seats. </div>
                                                </div>
                                            </div>
                                            <div class="hstack gap-3 align-items-start mb-7 justify-content-start">
                                                <img src="./images/profile/user-8.jpg" alt="user8" width="40" height="40" class="rounded-circle" />
                                                <div>
                                                    <h6 class="fs-2 text-muted">Andrew, 2 hours ago</h6>
                                                    <div class="rounded-2 overflow-hidden">
                                                        <img src="./images/products/product-1.jpg" alt="" class="w-100">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-9 py-6 border-top chat-send-message-footer">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2 w-85">
                                                <a class="position-relative nav-icon-hover z-index-5" href="javascript:void(0)"> <i class="ti ti-mood-smile text-dark bg-hover-primary fs-7"></i></a>
                                                <input type="text" class="form-control message-type-box text-muted border-0 p-0 ms-2" placeholder="Type a Message" />
                                            </div>
                                            <ul class="list-unstyledn mb-0 d-flex align-items-center">
                                                <li><a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 text-decoration-none" href="javascript:void(0)"><i class="ti ti-photo-plus"></i></a></li>
                                                <li><a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 text-decoration-none" href="javascript:void(0)"><i class="ti ti-paperclip"></i></a></li>
                                                <li><a class="text-dark px-2 fs-7 bg-hover-primary nav-icon-hover position-relative z-index-5 text-decoration-none" href="javascript:void(0)"><i class="ti ti-microphone"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="app-chat-offcanvas border-start" style="height: calc(100vh - 380px)" data-simplebar="">
                                    <div class="p-3 d-flex align-items-center justify-content-between">
                                        <h6 class="fw-semibold mb-0">Media <span class="text-muted">(36)</span></h6>
                                        <a class="chat-menu d-lg-none d-block text-dark fs-6 bg-hover-primary nav-icon-hover position-relative z-index-5" href="javascript:void(0)">
                                            <i class="ti ti-x"></i>
                                        </a>
                                    </div>
                                    <div class="offcanvas-body p-9">
                                        <div class="media-chat mb-7">
                                            <div class="row">
                                                <div class="col-4 px-1">
                                                    <div class="rounded-1 overflow-hidden mb-2"><img src="./images/products/product-1.jpg" alt="" class="w-100"></div>
                                                </div>
                                                <div class="col-4 px-1">
                                                    <div class="rounded-1 overflow-hidden mb-2"><img src="./images/products/product-2.jpg" alt="" class="w-100"></div>
                                                </div>
                                                <div class="col-4 px-1">
                                                    <div class="rounded-1 overflow-hidden mb-2"><img src="./images/products/product-3.jpg" alt="" class="w-100"></div>
                                                </div>
                                                <div class="col-4 px-1">
                                                    <div class="rounded-1 overflow-hidden mb-2"><img src="./images/products/product-4.jpg" alt="" class="w-100"></div>
                                                </div>
                                                <div class="col-4 px-1">
                                                    <div class="rounded-1 overflow-hidden mb-2"><img src="./images/products/product-1.jpg" alt="" class="w-100"></div>
                                                </div>
                                                <div class="col-4 px-1">
                                                    <div class="rounded-1 overflow-hidden mb-2"><img src="./images/products/product-2.jpg" alt="" class="w-100"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="files-chat">
                                            <h6 class="fw-semibold mb-3">Files <span class="text-muted">(36)</span></h6>
                                            <a href="javascript:void(0)" class="hstack gap-3 file-chat-hover justify-content-between mb-9">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-1 bg-light p-6">
                                                        <img src="./images/chat/icon-adobe.svg" alt=""  width="24" height="24">
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-semibold">service-task.pdf</h6>
                                                        <div class="d-flex align-items-center gap-3 fs-2 text-muted"><span>2 MB</span><span>2 Dec 2023</span></div>
                                                    </div>
                                                </div>
                                                <span class="position-relative nav-icon-hover download-file">
                                <i class="ti ti-download text-dark fs-6 bg-hover-primary"></i>
                              </span>
                                            </a>
                                            <a href="javascript:void(0)" class="hstack gap-3 file-chat-hover justify-content-between mb-9">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-1 bg-light p-6">
                                                        <img src="./images/chat/icon-figma.svg" alt=""  width="24" height="24">
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-semibold">homepage-design.fig</h6>
                                                        <div class="d-flex align-items-center gap-3 fs-2 text-muted"><span>2 MB</span><span>2 Dec 2023</span></div>
                                                    </div>
                                                </div>
                                                <span class="position-relative nav-icon-hover download-file">
                                <i class="ti ti-download text-dark fs-6 bg-hover-primary"></i>
                              </span>
                                            </a>
                                            <a href="javascript:void(0)" class="hstack gap-3 file-chat-hover justify-content-between mb-9">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-1 bg-light p-6">
                                                        <img src="./images/chat/icon-chrome.svg" alt=""  width="24" height="24">
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-semibold">about-us.html</h6>
                                                        <div class="d-flex align-items-center gap-3 fs-2 text-muted"><span>2 MB</span><span>2 Dec 2023</span></div>
                                                    </div>
                                                </div>
                                                <span class="position-relative nav-icon-hover download-file">
                                <i class="ti ti-download text-dark fs-6 bg-hover-primary"></i>
                              </span>
                                            </a>
                                            <a href="javascript:void(0)" class="hstack gap-3 file-chat-hover justify-content-between mb-9">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-1 bg-light p-6">
                                                        <img src="./images/chat/icon-zip-folder.svg" alt=""  width="24" height="24">
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-semibold">work-project.zip</h6>
                                                        <div class="d-flex align-items-center gap-3 fs-2 text-muted"><span>2 MB</span><span>2 Dec 2023</span></div>
                                                    </div>
                                                </div>
                                                <span class="position-relative nav-icon-hover download-file">
                                <i class="ti ti-download text-dark fs-6 bg-hover-primary"></i>
                              </span>
                                            </a>
                                            <a href="javascript:void(0)" class="hstack gap-3 file-chat-hover justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-1 bg-light p-6">
                                                        <img src="./images/chat/icon-javascript.svg" alt=""  width="24" height="24">
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-semibold">custom.js</h6>
                                                        <div class="d-flex align-items-center gap-3 fs-2 text-muted"><span>2 MB</span><span>2 Dec 2023</span></div>
                                                    </div>
                                                </div>
                                                <span class="position-relative nav-icon-hover download-file">
                                <i class="ti ti-download text-dark fs-6 bg-hover-primary"></i>
                              </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-20 d-none d-lg-block border-end user-chat-box">
                    <div class="px-4 pt-9 pb-6">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="position-relative">
                                    <img src="./images/profile/user-1.jpg" alt="user1" width="54" height="54" class="rounded-circle">
                                    <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                      <span class="visually-hidden">New alerts</span>
                                    </span>
                                </div>
                                <div class="ms-3">

                                    @if($operatorID)
                                        <div class="d-flex align-items-center ms-2">
                                            @if($user->find($operatorID)->image == null)
                                                <span class="avatar brround" style="background-image: url(../uploads/profile/user-profile.png)"></span>
                                            @else
                                                <span class="avatar brround" style="background-image: url(../uploads/profile/{{$user->find($operatorID)->image}})"></span>
                                            @endif
                                            <h6 class="fw-semibold mb-2">{{$user->find($operatorID)->name}}</h6>
                                            <p class="mb-0 fs-2">Mis chats abiertos</p>
                                        </div>
                                    @else

                                        <h6 class="fw-semibold mb-2">Nuevo chat</h6>
                                        <p class="mb-0 fs-2">Mis chats abiertos</p>
                                    @endif

                                </div>
                            </div>
                            <div class="dropdown">
                                <a class="text-dark fs-6 nav-icon-hover text-decoration-none" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item d-flex align-items-center gap-2 border-bottom" href="javascript:void(0)"><span><i class="ti ti-settings fs-4"></i></span>Setting</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)"><span><i class="ti ti-help fs-4"></i></span>Help and feadback</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)"><span><i class="ti ti-layout-board-split fs-4"></i></span>Enable split View mode</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2 border-bottom" href="javascript:void(0)"><span><i class="ti ti-table-shortcut fs-4"></i></span>Keyboard shortcut</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)"><span><i class="ti ti-login fs-4"></i></span>Sign Out</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="app-chat">
                        <ul class="chat-users" style="height: calc(100vh - 496px)" data-simplebar>


                            @if($user->isNotEmpty())
                                <div class="mb-5"><input class="form-control" id="availableAgentsSearchInput" placeholder="Search For Operator" type="text"></div>
                                <div class="noUser" id="noUserMessage" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex gap-3">
                                            <p>Ningún usuario encontrado </p>
                                        </div>
                                    </div>
                                </div>
                                <ul class="list-unstyled agents-list overflow-y-scroll" id="agents-list">
                                    @foreach($user as $users)
                                        <li data-id={{$LiveCust->id}}>
                                            <a href="javascript:void(0)" class="px-4 py-3 bg-hover-light-black d-flex align-items-start justify-content-between chat-user bg-light" id="chat_user_1" data-user-id="1">
                                                <div class="d-flex align-items-center">
                                                  <span class="position-relative" id="new-chat-user-bg" style="background-color: {{ randomColorGenerator(0.5) }}">
                                                       @php
                                                           $currentOnlineUsers = setting('liveChatCustomerOnlineUsers');
                                                           $onlineUsersArray = explode(',', $currentOnlineUsers);
                                                           $userOnline = in_array($LiveCust->id, $onlineUsersArray);
                                                       @endphp
                                                      @if($userOnline)
                                                          <span class="avatar-status bg-green"></span>
                                                      @else
                                                          <span class="avatar-status "></span>
                                                      @endif

                                                      @if($users->image == null)
                                                              <img src="../uploads/profile/user-profile.png" alt="user1" width="48" height="48" class="rounded-circle" />
                                                      @else
                                                              <img src="../uploads/profile/{{$users->image}}" alt="user1" width="48" height="48" class="rounded-circle" />
                                                      @endif


                                                    <span class="position-absolute bottom-0 end-0 p-1 badge rounded-pill bg-success">
                                                      <span class="visually-hidden">{{$users->name}}</span>
                                                    </span>
                                                  </span>
                                                    <div class="ms-3 d-inline-block w-75">
                                                        <h6 class="mb-1 fw-semibold chat-title" data-username="{{$users->name}}">{{$users->name}}</h6>
                                                        <span class="fs-3 text-truncate text-body-color d-block" >
                                                        {{$users->email}}
                                                    </span>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if($user->isEmpty())
                                <div class="text-center mt-5 p-1 bg-warning-transparent text-default">
                                    <span>No se encontraron operadores</span>
                                </div>
                            @endif



                        </ul>
                    </div>
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

