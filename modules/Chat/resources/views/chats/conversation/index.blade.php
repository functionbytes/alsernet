@extends('layouts.theme')

@section('container-class', 'mw-100')

@section('content')

    @php
        $filter = $filter ?? null;
        $activeFilters = $activeFilters ?? [];
    @endphp

    <div class="chat">
        <div class="card overflow-hidden chat-application">
            <div class="d-flex align-items-center justify-content-between gap-3 m-3 d-lg-none">
                <button class="btn btn-primary d-flex" type="button" data-bs-toggle="offcanvas" data-bs-target="#chat-sidebar" aria-controls="chat-sidebar">
                    <i class="fas fa-bars fs-5"></i>
                </button>
                <form class="position-relative w-100">
                    <input type="text" class="form-control search-chat py-2 ps-5" id="text-srh" placeholder="Search Conversation">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                </form>
            </div>
            <div class="d-flex">
                <!-- Left Sidebar - Filters -->
                @include('Chat::chats.conversation.partials.layout.sidebar-left', compact('filter', 'filterCounts', 'statusCounts', 'priorityCounts', 'inboxes', 'activeFilters', 'teams', 'labels'))

                <!-- Middle Column - Conversation List -->
                <div class="container-inbox-items min-width-340">
                    <div class="border-end user-chat-box h-100 d-flex flex-column">
                        <!-- Search and Filters Section -->
                        <div class="p-3 d-none d-lg-block flex-shrink-0 border-bottom">
                            <!-- Search Bar -->
                            <div class="mb-3">
                                <div class="input-group input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="conversation-search" placeholder="Buscar conversaciones, mensajes, contactos..." autocomplete="off">
                                </div>
                            </div>

                            <!-- Advanced Filters Toggle -->
                            <button class="btn btn-secondary w-100" type="button" data-bs-toggle="modal" data-bs-target="#advancedFiltersModal">
                                <i class="fas fa-filter me-1"></i>
                            </button>
                        </div>

                        <!-- Conversations List -->
                        @include('Chat::chats.conversation.partials.lists.conversation-list', compact('conversations', 'nextCursor', 'hasMore', 'filter'))
                    </div>
                </div>

                <!-- Right Column - Conversation Detail -->
                <div class="container-inbox-chat-details flex-fill" id="conversation-detail">
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-comments fs-1 text-muted mb-3"></i>
                            <p class="text-muted">Selecciona una conversación para ver los detalles</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- New Conversation Modal -->
    @include('Chat::chats.conversation.partials.modals.new-conversation', compact('inboxes'))

    <!-- Advanced Filters Modal -->
    @include('Chat::chats.conversation.partials.modals.advanced-filters', compact('teams', 'labels'))


@push('scripts')
<!-- Conversations Management -->
<script src="{{ asset('chat-module/js/chat/ui/conversations.js') }}"></script>
<!-- Contacts Search in New Conversation Modal -->
<script src="{{ asset('chat-module/js/chat/ui/contacts-search.js') }}"></script>
<!-- Quick Search Dropdown -->
<script src="{{ asset('chat-module/js/chat/ui/quick-search.js') }}"></script>
@endpush
</div>
@endsection
