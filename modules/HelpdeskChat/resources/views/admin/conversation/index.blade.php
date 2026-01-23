{{-- All data is now passed from the controller --}}
@extends('layouts.admin')

@section('content')
<div class="container-fluid p-0 h-100">
    <div class="row g-0 h-100">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 px-0 bg-light border-end" style="height: calc(100vh - 56px); overflow-y: auto;">
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted text-uppercase small fw-bold mb-0">Conversations</h6>
                </div>

                <!-- New Conversation Button -->
                <button type="button" class="btn btn-primary btn-sm w-100 mb-3" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                    <i class="bi bi-pen me-1"></i> Nueva Conversación
                </button>

                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               class="form-control"
                               id="conversation-search"
                               placeholder="Search conversations, messages, contacts..."
                               autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="clear-search" style="display: none;">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <!-- Search Results Dropdown -->
                    <div id="search-results" class="list-group mt-2" style="display: none; max-height: 400px; overflow-y: auto;"></div>
                </div>

                <!-- Advanced Filters Toggle -->
                <button class="btn btn-outline-secondary btn-sm w-100 mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                    <i class="bi bi-funnel me-1"></i> Advanced Filters
                    @if(request()->hasAny(['date_from', 'date_to', 'last_activity_from', 'last_activity_to', 'team_id', 'labels', 'search']))
                        <span class="badge bg-primary ms-1">Active</span>
                    @endif
                </button>

                <!-- Advanced Filters Panel -->
                <div class="collapse {{ request()->hasAny(['date_from', 'date_to', 'last_activity_from', 'last_activity_to', 'team_id', 'labels', 'search']) ? 'show' : '' }}" id="advancedFilters">
                    <div class="card card-body p-2 mb-3">
                        <form method="GET" action="{{ route('admin.conversation.index') }}" id="advanced-search-form">
                            <!-- Text Search -->
                            <div class="mb-2">
                                <label class="form-label small mb-1">Search Text</label>
                                <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Contact, message...">
                            </div>

                            <!-- Team Filter -->
                            <div class="mb-2">
                                <label class="form-label small mb-1">Team</label>
                                <select class="form-select form-select-sm" name="team_id">
                                    <option value="">All Teams</option>
                                    @foreach(auth()->user()->account->teams as $team)
                                        <option value="{{ $team->id }}" {{ request('team_id') == $team->id ? 'selected' : '' }}>
                                            {{ $team->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Label Filter -->
                            <div class="mb-2">
                                <label class="form-label small mb-1">Labels</label>
                                <select class="form-select form-select-sm" name="labels">
                                    <option value="">All Labels</option>
                                    @php
                                        $labels = auth()->user()->account->labels()->pluck('title')->unique();
                                    @endphp
                                    @foreach($labels as $label)
                                        <option value="{{ $label }}" {{ request('labels') == $label ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Date Range -->
                            <div class="mb-2">
                                <label class="form-label small mb-1">Created Date</label>
                                <div class="row g-1">
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}" placeholder="From">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}" placeholder="To">
                                    </div>
                                </div>
                            </div>

                            <!-- Last Activity Range -->
                            <div class="mb-2">
                                <label class="form-label small mb-1">Last Activity</label>
                                <div class="row g-1">
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" name="last_activity_from" value="{{ request('last_activity_from') }}" placeholder="From">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" name="last_activity_to" value="{{ request('last_activity_to') }}" placeholder="To">
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-1">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search me-1"></i> Apply Filters
                                </button>
                                <a href="{{ route('admin.conversation.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle me-1"></i> Clear All
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Main Filters -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ !isset($filter) || $filter === 'all' ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 d-flex justify-content-between align-items-center"
                           href="{{ route('admin.conversation.index') }}">
                            <span><i class="bi bi-inbox me-2"></i>All Conversations</span>
                            @if($filterCounts['all'] > 0)
                                <span class="badge bg-secondary">{{ $filterCounts['all'] }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ isset($filter) && $filter === 'mine' ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 d-flex justify-content-between align-items-center"
                           href="{{ route('admin.conversation.mine') }}">
                            <span><i class="bi bi-person-check me-2"></i>Mine</span>
                            @if($filterCounts['mine'] > 0)
                                <span class="badge bg-secondary">{{ $filterCounts['mine'] }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ isset($filter) && $filter === 'unassigned' ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 d-flex justify-content-between align-items-center"
                           href="{{ route('admin.conversation.unassigned') }}">
                            <span><i class="bi bi-person-x me-2"></i>Unassigned</span>
                            @if($filterCounts['unassigned'] > 0)
                                <span class="badge bg-warning">{{ $filterCounts['unassigned'] }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ isset($filter) && $filter === 'mentions' ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 d-flex justify-content-between align-items-center"
                           href="{{ route('admin.conversation.mentions') }}">
                            <span><i class="bi bi-at me-2"></i>Mentions</span>
                            @if($filterCounts['mentions'] > 0)
                                <span class="badge bg-info">{{ $filterCounts['mentions'] }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ isset($filter) && $filter === 'unattended' ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 d-flex justify-content-between align-items-center"
                           href="{{ route('admin.conversation.unattended') }}">
                            <span><i class="bi bi-exclamation-circle me-2"></i>Unattended</span>
                            @if($filterCounts['unattended'] > 0)
                                <span class="badge bg-danger">{{ $filterCounts['unattended'] }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <hr class="my-3">

                <!-- Channels Section -->
                <div class="mb-3">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">
                        <a class="text-decoration-none text-muted d-flex justify-content-between align-items-center"
                           data-bs-toggle="collapse" href="#channelsCollapse" role="button" aria-expanded="true">
                            <span>Channels</span>
                            <i class="bi bi-chevron-down"></i>
                        </a>
                    </h6>
                    <div class="collapse show" id="channelsCollapse">
                        <ul class="nav flex-column">
                            @foreach($inboxes as $channelInbox)
                                <li class="nav-item">
                                    <a class="nav-link {{ isset($inbox) && $inbox->id === $channelInbox->id ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 small d-flex justify-content-between align-items-center"
                                       href="{{ route('admin.conversation.byInbox', $channelInbox->id) }}">
                                        <span class="text-truncate">
                                            @if($channelInbox->channel_type === 'App\Models\Channels\WebWidget')
                                                <i class="bi bi-globe me-1"></i>
                                            @elseif($channelInbox->channel_type === 'App\Models\Channels\Email')
                                                <i class="bi bi-envelope me-1"></i>
                                            @elseif($channelInbox->channel_type === 'App\Models\Channels\FacebookPage')
                                                <i class="bi bi-facebook me-1"></i>
                                            @elseif($channelInbox->channel_type === 'App\Models\Channels\Instagram')
                                                <i class="bi bi-instagram me-1"></i>
                                            @elseif($channelInbox->channel_type === 'App\Models\Channels\Whatsapp')
                                                <i class="bi bi-whatsapp me-1"></i>
                                            @else
                                                <i class="bi bi-chat-dots me-1"></i>
                                            @endif
                                            {{ $channelInbox->name }}
                                        </span>
                                        @if($channelInbox->conversations_count > 0)
                                            <span class="badge bg-secondary">{{ $channelInbox->conversations_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Teams Section -->
                <div class="mb-3">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">
                        <a class="text-decoration-none text-muted d-flex justify-content-between align-items-center"
                           data-bs-toggle="collapse" href="#teamsCollapse" role="button" aria-expanded="true">
                            <span>Teams</span>
                            <i class="bi bi-chevron-down"></i>
                        </a>
                    </h6>
                    <div class="collapse show" id="teamsCollapse">
                        <ul class="nav flex-column">
                            @forelse($teams as $userTeam)
                                <li class="nav-item">
                                    <a class="nav-link {{ isset($team) && $team->id === $userTeam->id ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 small d-flex justify-content-between align-items-center"
                                       href="{{ route('admin.conversation.byTeam', $userTeam->id) }}">
                                        <span class="text-truncate">
                                            <i class="bi bi-people me-1"></i>{{ $userTeam->name }}
                                        </span>
                                        @if($userTeam->conversations_count > 0)
                                            <span class="badge bg-secondary">{{ $userTeam->conversations_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @empty
                                <li class="nav-item">
                                    <small class="text-muted ms-3">No teams assigned</small>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Labels Section -->
                <div class="mb-3">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">
                        <a class="text-decoration-none text-muted d-flex justify-content-between align-items-center"
                           data-bs-toggle="collapse" href="#labelsCollapse" role="button" aria-expanded="true">
                            <span>Labels</span>
                            <i class="bi bi-chevron-down"></i>
                        </a>
                    </h6>
                    <div class="collapse show" id="labelsCollapse">
                        <ul class="nav flex-column">
                            @forelse($labels as $labelItem)
                                <li class="nav-item">
                                    <a class="nav-link {{ isset($label) && $label->id === $labelItem->id ? 'active bg-primary text-white' : 'text-dark' }} rounded mb-1 small d-flex justify-content-between align-items-center"
                                       href="{{ route('admin.conversation.byLabel', $labelItem->title) }}">
                                        <span class="text-truncate">
                                            <span class="badge rounded-circle me-1" style="background-color: {{ $labelItem->color }}; width: 10px; height: 10px; display: inline-block;"></span>
                                            {{ $labelItem->title }}
                                        </span>
                                        @if($labelItem->conversations_count > 0)
                                            <span class="badge bg-secondary">{{ $labelItem->conversations_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @empty
                                <li class="nav-item">
                                    <small class="text-muted ms-3">No labels created</small>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Priority Section -->
                <div class="mb-3">
                    <h6 class="text-muted text-uppercase small fw-bold mb-2">
                        <a class="text-decoration-none text-muted d-flex justify-content-between align-items-center"
                           data-bs-toggle="collapse" href="#priorityCollapse" role="button" aria-expanded="true">
                            <span>Priority</span>
                            <i class="bi bi-chevron-down"></i>
                        </a>
                    </h6>
                    <div class="collapse show" id="priorityCollapse">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link text-dark rounded mb-1 small d-flex justify-content-between align-items-center"
                                   href="{{ route('admin.conversation.index', ['priority' => 'urgent']) }}">
                                    <span><span class="badge bg-danger me-2">●</span> Urgent</span>
                                    @if($priorityCounts['urgent'] > 0)
                                        <span class="badge bg-danger">{{ $priorityCounts['urgent'] }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark rounded mb-1 small d-flex justify-content-between align-items-center"
                                   href="{{ route('admin.conversation.index', ['priority' => 'high']) }}">
                                    <span><span class="badge bg-warning me-2">●</span> High</span>
                                    @if($priorityCounts['high'] > 0)
                                        <span class="badge bg-warning">{{ $priorityCounts['high'] }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark rounded mb-1 small d-flex justify-content-between align-items-center"
                                   href="{{ route('admin.conversation.index', ['priority' => 'medium']) }}">
                                    <span><span class="badge bg-info me-2">●</span> Medium</span>
                                    @if($priorityCounts['medium'] > 0)
                                        <span class="badge bg-info">{{ $priorityCounts['medium'] }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark rounded mb-1 small d-flex justify-content-between align-items-center"
                                   href="{{ route('admin.conversation.index', ['priority' => 'low']) }}">
                                    <span><span class="badge bg-secondary me-2">●</span> Low</span>
                                    @if($priorityCounts['low'] > 0)
                                        <span class="badge bg-secondary">{{ $priorityCounts['low'] }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area - Conversation List -->
        <div class="col-md-9 col-lg-10 px-0">
            <div class="p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">
                            @if(isset($filter))
                                @if($filter === 'mine')
                                    My Conversations
                                @elseif($filter === 'unassigned')
                                    Unassigned Conversations
                                @elseif($filter === 'mentions')
                                    Mentions
                                @elseif($filter === 'unattended')
                                    Unattended Conversations
                                @elseif($filter === 'inbox')
                                    {{ $inbox->name }}
                                @elseif($filter === 'team')
                                    {{ $team->name }}
                                @elseif($filter === 'label')
                                    Label: {{ $label->title }}
                                @else
                                    All Conversations
                                @endif
                            @else
                                All Conversations
                            @endif
                        </h4>
                        <small class="text-muted">{{ $conversations->total() }} conversation(s)</small>
                    </div>

                    <!-- Quick Filters -->
                    <div class="btn-group me-2" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-funnel"></i> Status
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?status=open">Open</a></li>
                            <li><a class="dropdown-item" href="?status=pending">Pending</a></li>
                            <li><a class="dropdown-item" href="?status=resolved">Resolved</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url()->current() }}">All</a></li>
                        </ul>
                    </div>

                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-flag"></i> Priority
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="?priority=urgent">
                                    <span class="badge bg-danger me-2">●</span> Urgent
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?priority=high">
                                    <span class="badge bg-warning me-2">●</span> High
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?priority=medium">
                                    <span class="badge bg-info me-2">●</span> Medium
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?priority=low">
                                    <span class="badge bg-secondary me-2">●</span> Low
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url()->current() }}">All Priorities</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Conversations List -->
                @if($conversations->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">No Conversations Found</h5>
                        <p class="text-muted">
                            @if(request()->has('status'))
                                Try adjusting your filters to see more results.
                            @else
                                Conversations will appear here when visitors start chatting.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="conversations-grid">
                        @foreach($conversations as $conversation)
                            <a href="{{ route('admin.conversation.show', $conversation) }}"
                               class="conversation-card">
                                <div class="conversation-card-header">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <!-- Avatar -->
                                        <div class="conversation-avatar me-3">
                                            @if($conversation->contact->avatar_url)
                                                <img src="{{ $conversation->contact->avatar_url }}"
                                                     alt="{{ $conversation->contact->name }}"
                                                     class="rounded-circle"
                                                     width="48" height="48">
                                            @else
                                                <div class="avatar-placeholder">
                                                    {{ strtoupper(substr($conversation->contact->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Contact Info -->
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <h6 class="conversation-contact-name mb-0">{{ $conversation->contact->name }}</h6>
                                                <small class="conversation-time ms-2">{{ $conversation->last_activity_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="conversation-contact-info">
                                                {{ $conversation->contact->email ?? $conversation->contact->phone_number }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="conversation-card-body">
                                    <!-- Last Message Preview -->
                                    @if($conversation->messages->last())
                                        <p class="conversation-last-message">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($conversation->messages->last()->content), 120) }}
                                        </p>
                                    @endif

                                    <!-- Meta Info -->
                                    <div class="conversation-meta">
                                        <!-- Channel Badge -->
                                        <span class="channel-badge channel-badge-{{
                                            $conversation->inbox->channel_type === 'App\Models\Channels\Whatsapp' ? 'whatsapp' :
                                            ($conversation->inbox->channel_type === 'App\Models\Channels\FacebookPage' ? 'facebook' :
                                            ($conversation->inbox->channel_type === 'App\Models\Channels\Instagram' ? 'instagram' :
                                            ($conversation->inbox->channel_type === 'App\Models\Channels\Email' ? 'email' : 'default')))
                                        }}">
                                            @if($conversation->inbox->channel_type === 'App\Models\Channels\WebWidget')
                                                <i class="bi bi-globe"></i>
                                            @elseif($conversation->inbox->channel_type === 'App\Models\Channels\Email')
                                                <i class="bi bi-envelope"></i>
                                            @elseif($conversation->inbox->channel_type === 'App\Models\Channels\FacebookPage')
                                                <i class="bi bi-facebook"></i>
                                            @elseif($conversation->inbox->channel_type === 'App\Models\Channels\Instagram')
                                                <i class="bi bi-instagram"></i>
                                            @elseif($conversation->inbox->channel_type === 'App\Models\Channels\Whatsapp')
                                                <i class="bi bi-whatsapp"></i>
                                            @elseif($conversation->inbox->channel_type === 'App\Models\Channels\Sms')
                                                <i class="bi bi-chat-dots"></i>
                                            @elseif($conversation->inbox->channel_type === 'App\Models\Channels\TwilioSms')
                                                <i class="bi bi-phone"></i>
                                            @elseif($conversation->inbox->channel_type === 'App\Models\Channels\Api')
                                                <i class="bi bi-code"></i>
                                            @else
                                                <i class="bi bi-chat-dots"></i>
                                            @endif
                                            <span class="ms-1">{{ $conversation->inbox->name }}</span>
                                        </span>

                                        <!-- Status Badge -->
                                        <span class="status-badge status-{{ $conversation->status }}">
                                            <i class="bi bi-circle-fill me-1"></i>
                                            {{ ucfirst($conversation->status) }}
                                        </span>

                                        <!-- Priority Badge -->
                                        @if($conversation->priority && $conversation->priority !== 'low')
                                            <span class="priority-badge priority-{{ $conversation->priority }}">
                                                <i class="bi bi-exclamation-circle me-1"></i>
                                                {{ ucfirst($conversation->priority) }}
                                            </span>
                                        @endif

                                        <!-- Assignee -->
                                        @if($conversation->assignee)
                                            <span class="assignee-badge">
                                                <i class="bi bi-person-circle me-1"></i>
                                                {{ $conversation->assignee->name }}
                                            </span>
                                        @endif

                                        <!-- Team -->
                                        @if($conversation->team)
                                            <span class="team-badge">
                                                <i class="bi bi-people-fill me-1"></i>
                                                {{ $conversation->team->name }}
                                            </span>
                                        @endif

                                        <!-- Labels -->
                                        @foreach($conversation->getLabels() as $conversationLabel)
                                            @php
                                                $labelObj = $labelsByTitle[$conversationLabel] ?? null;
                                            @endphp
                                            @if($labelObj)
                                                <span class="label-badge" style="background-color: {{ $labelObj->color }}20; color: {{ $labelObj->color }}; border: 1px solid {{ $labelObj->color }}40;">
                                                    <i class="bi bi-tag-fill me-1"></i>
                                                    {{ $conversationLabel }}
                                                </span>
                                            @endif
                                        @endforeach

                                        <!-- Snoozed Indicator -->
                                        @if($conversation->isSnoozed())
                                            <span class="snoozed-badge">
                                                <i class="bi bi-alarm-fill me-1"></i>
                                                Snoozed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $conversations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* Sidebar Navigation */
.nav-link {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

.nav-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.nav-link.active {
    font-weight: 500;
}

/* Conversations Grid */
.conversations-grid {
    display: grid;
    gap: 1rem;
}

/* Conversation Card */
.conversation-card {
    display: block;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 0;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.conversation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #0d6efd;
}

.conversation-card-header {
    padding: 1.25rem;
    border-bottom: 1px solid #f3f4f6;
}

.conversation-card-body {
    padding: 1.25rem;
    background: #fafafa;
}

/* Avatar */
.conversation-avatar {
    position: relative;
}

.avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 600;
}

/* Contact Info */
.conversation-contact-name {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
}

.conversation-contact-info {
    font-size: 0.875rem;
    color: #6b7280;
}

.conversation-time {
    font-size: 0.75rem;
    color: #9ca3af;
    white-space: nowrap;
}

/* Last Message */
.conversation-last-message {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Meta Info */
.conversation-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

/* Channel Badges with Brand Colors */
.channel-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    border: 1px solid;
}

.channel-badge-whatsapp {
    background: #E8F5E9;
    color: #25D366;
    border-color: #C8E6C9;
}

.channel-badge-facebook {
    background: #E3F2FD;
    color: #1877F2;
    border-color: #BBDEFB;
}

.channel-badge-instagram {
    background: #FCE4EC;
    color: #E1306C;
    border-color: #F8BBD0;
}

.channel-badge-email {
    background: #FFF3E0;
    color: #F57C00;
    border-color: #FFE0B2;
}

.channel-badge-default {
    background: #F3F4F6;
    color: #6B7280;
    border-color: #E5E7EB;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
}

.status-open {
    background: #FEF3C7;
    color: #D97706;
}

.status-pending {
    background: #E0E7FF;
    color: #6366F1;
}

.status-resolved {
    background: #D1FAE5;
    color: #059669;
}

.status-badge i {
    font-size: 0.5rem;
}

/* Priority Badges */
.priority-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
}

.priority-urgent {
    background: #FEE2E2;
    color: #DC2626;
}

.priority-high {
    background: #FED7AA;
    color: #EA580C;
}

.priority-medium {
    background: #FEF3C7;
    color: #D97706;
}

/* Assignee Badge */
.assignee-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    background: #DBEAFE;
    color: #1E40AF;
}

/* Team Badge */
.team-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    background: #E0E7FF;
    color: #4F46E5;
}

/* Label Badge */
.label-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
}

/* Snoozed Badge */
.snoozed-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    background: #FEF3C7;
    color: #D97706;
}

/* Responsive */
@media (max-width: 768px) {
    .conversation-card-header {
        padding: 1rem;
    }

    .conversation-card-body {
        padding: 1rem;
    }

    .conversation-contact-name {
        font-size: 0.9375rem;
    }
}
</style>

<!-- New Conversation Modal -->
<div class="modal fade" id="newConversationModal" tabindex="-1" aria-labelledby="newConversationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.conversation.store') }}" method="POST" id="new-conversation-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="newConversationModalLabel">
                        <i class="bi bi-pen me-2"></i>Nueva Conversación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Contact Search -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Para:</label>
                        <input type="hidden" name="contact_id" id="selected_contact_id" required>
                        <input type="text"
                               class="form-control"
                               id="contact_search"
                               placeholder="Buscar un contacto con nombre, correo electrónico o número de teléfono"
                               autocomplete="off">

                        <!-- Contact Search Results -->
                        <div id="contact_search_results" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto;"></div>

                        <!-- Selected Contact Display -->
                        <div id="selected_contact" style="display: none;" class="mt-2">
                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                <div class="flex-grow-1">
                                    <strong id="selected_contact_name"></strong><br>
                                    <small class="text-muted" id="selected_contact_email"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="remove_contact">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Inbox Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vía:</label>
                        <select name="inbox_id" class="form-select" required>
                            <option value="">Seleccionar bandeja de entrada...</option>
                            @foreach(auth()->user()->account->inboxes as $inbox)
                                <option value="{{ $inbox->id }}">{{ $inbox->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Initial Message -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mensaje Inicial (opcional):</label>
                        <textarea name="initial_message"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Escriba su mensaje aquí..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Descartar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Crear Conversación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactSearch = document.getElementById('contact_search');
    const contactSearchResults = document.getElementById('contact_search_results');
    const selectedContactId = document.getElementById('selected_contact_id');
    const selectedContactDiv = document.getElementById('selected_contact');
    const selectedContactName = document.getElementById('selected_contact_name');
    const selectedContactEmail = document.getElementById('selected_contact_email');
    const removeContactBtn = document.getElementById('remove_contact');

    let searchTimeout;

    // Search contacts as user types
    contactSearch.addEventListener('input', function() {
        const query = this.value.trim();

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            contactSearchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            $.ajax({
                url: `/admin/api/contacts/search?q=${encodeURIComponent(query)}`,
                type: 'GET',
                dataType: 'json',
                success: function(contacts) {
                    if (contacts.length === 0) {
                        contactSearchResults.innerHTML = '<div class="list-group-item text-muted">No se encontraron contactos</div>';
                        contactSearchResults.style.display = 'block';
                        return;
                    }

                    contactSearchResults.innerHTML = contacts.map(contact => `
                        <button type="button" class="list-group-item list-group-item-action contact-result" data-id="${contact.id}" data-name="${contact.name}" data-email="${contact.email || contact.phone_number || ''}">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <strong>${contact.name}</strong><br>
                                    <small class="text-muted">${contact.email || contact.phone_number || 'Sin email/teléfono'}</small>
                                </div>
                            </div>
                        </button>
                    `).join('');

                    contactSearchResults.style.display = 'block';

                    // Add click handlers to results
                    document.querySelectorAll('.contact-result').forEach(item => {
                        item.addEventListener('click', function() {
                            selectContact(this.dataset.id, this.dataset.name, this.dataset.email);
                        });
                    });
                },
                error: function(error) {
                    console.error('Error searching contacts:', error);
                    contactSearchResults.innerHTML = '<div class="list-group-item text-danger">Error al buscar contactos</div>';
                    contactSearchResults.style.display = 'block';
                }
            });
        }, 300);
    });

    function selectContact(id, name, email) {
        selectedContactId.value = id;
        selectedContactName.textContent = name;
        selectedContactEmail.textContent = email;

        contactSearch.style.display = 'none';
        contactSearchResults.style.display = 'none';
        selectedContactDiv.style.display = 'block';
    }

    removeContactBtn.addEventListener('click', function() {
        selectedContactId.value = '';
        contactSearch.value = '';
        contactSearch.style.display = 'block';
        selectedContactDiv.style.display = 'none';
    });

    // Reset modal when closed
    document.getElementById('newConversationModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('new-conversation-form').reset();
        selectedContactId.value = '';
        contactSearch.style.display = 'block';
        contactSearchResults.style.display = 'none';
        selectedContactDiv.style.display = 'none';
    });

    // =====================================================
    // CONVERSATION SEARCH
    // =====================================================
    const conversationSearch = document.getElementById('conversation-search');
    const searchResults = document.getElementById('search-results');
    const clearSearchBtn = document.getElementById('clear-search');
    let searchTimeout;

    if (conversationSearch && searchResults) {
        conversationSearch.addEventListener('input', function() {
            const query = this.value.trim();

            // Show/hide clear button
            if (query.length > 0) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
                searchResults.style.display = 'none';
                return;
            }

            // Clear previous timeout
            clearTimeout(searchTimeout);

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Show loading state
            searchResults.innerHTML = '<div class="list-group-item text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Searching...</div>';
            searchResults.style.display = 'block';

            // Debounce search
            searchTimeout = setTimeout(() => {
                $.ajax({
                    url: `/admin/api/conversations/search?q=${encodeURIComponent(query)}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(results) {
                        if (results.length === 0) {
                            searchResults.innerHTML = '<div class="list-group-item text-muted text-center">No results found</div>';
                            return;
                        }

                        searchResults.innerHTML = results.map(conversation => {
                            // Status badge
                            let statusBadge = '';
                            if (conversation.status === 'open') {
                                statusBadge = '<span class="badge bg-warning text-dark">Open</span>';
                            } else if (conversation.status === 'resolved') {
                                statusBadge = '<span class="badge bg-success">Resolved</span>';
                            } else {
                                statusBadge = '<span class="badge bg-secondary">Pending</span>';
                            }

                            // Match type indicator
                            const matchType = conversation.match_type === 'message'
                                ? '<i class="bi bi-chat-dots text-primary me-1" title="Match in message"></i>'
                                : '<i class="bi bi-person text-info me-1" title="Match in contact"></i>';

                            return `
                                <a href="${conversation.url}" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                ${matchType}
                                                <strong>${conversation.contact.name}</strong>
                                                <span class="badge bg-info ms-2">#${conversation.id}</span>
                                            </div>
                                            ${conversation.contact.email ? `<small class="text-muted d-block">${conversation.contact.email}</small>` : ''}
                                            ${conversation.contact.phone ? `<small class="text-muted d-block">${conversation.contact.phone}</small>` : ''}
                                        </div>
                                        <div class="text-end">
                                            ${statusBadge}
                                            <small class="text-muted d-block mt-1">${conversation.last_activity_at}</small>
                                        </div>
                                    </div>
                                    ${conversation.last_message ? `
                                        <small class="text-muted">
                                            <i class="bi bi-chat-left-text me-1"></i>
                                            ${conversation.last_message.content}
                                        </small>
                                        <br>
                                        <small class="text-muted">${conversation.last_message.created_at}</small>
                                    ` : ''}
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <i class="bi bi-inbox me-1"></i>${conversation.inbox.name}
                                            ${conversation.assignee ? `<i class="bi bi-person-check ms-2 me-1"></i>${conversation.assignee}` : ''}
                                        </small>
                                    </div>
                                </a>
                            `;
                        }).join('');

                        searchResults.style.display = 'block';
                    },
                    error: function(error) {
                        console.error('Error searching conversation:', error);
                        searchResults.innerHTML = '<div class="list-group-item text-danger text-center">Error searching. Please try again.</div>';
                    }
                });
            }, 400);
        });

        // Clear search button
        clearSearchBtn.addEventListener('click', function() {
            conversationSearch.value = '';
            searchResults.style.display = 'none';
            clearSearchBtn.style.display = 'none';
            conversationSearch.focus();
        });

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!conversationSearch.contains(e.target) && !searchResults.contains(e.target) && !clearSearchBtn.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Keyboard navigation for search results
        conversationSearch.addEventListener('keydown', function(e) {
            if (searchResults.style.display === 'block') {
                const items = searchResults.querySelectorAll('a.list-group-item');
                const currentFocus = searchResults.querySelector('a.list-group-item.active');
                let index = Array.from(items).indexOf(currentFocus);

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    items.forEach(item => item.classList.remove('active'));
                    index = (index + 1) % items.length;
                    items[index].classList.add('active');
                    items[index].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    items.forEach(item => item.classList.remove('active'));
                    index = (index - 1 + items.length) % items.length;
                    items[index].classList.add('active');
                    items[index].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    if (currentFocus) {
                        e.preventDefault();
                        currentFocus.click();
                    }
                } else if (e.key === 'Escape') {
                    searchResults.style.display = 'none';
                }
            }
        });
    }
});
</script>
@endsection

