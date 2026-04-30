@extends('layouts.theme')

@section('container-class', 'mw-100')

@section('content')

    <div class="chat">
        <div class="card overflow-hidden chat-application h-100">
            <div class="d-flex h-100">
                <!-- Main Conversation Area (full width) -->
                <div class="w-100">
                    @include('Chat::chats.conversation.partials.detail', compact(
                        'conversation',
                        'statuses',
                        'teams',
                        'users',
                        'priorities',
                        'labelsByTitle',
                        'macros',
                        'mimeIconMap',
                        'attachEnabled',
                        'acceptAttr',
                        'attachMaxMb',
                        'attachMaxCount',
                        'attachTypes',
                        'attachmentConfig',
                        'acceptAttribute'
                    ))
                </div>
            </div>
        </div>
    </div>

@endsection
