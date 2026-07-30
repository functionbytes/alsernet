@extends('helpdesk::portal.layouts.base')

@section('title', 'Mis conversaciones')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0">Mis conversaciones</h4>
            <small class="text-muted">Hola, {{ $customer->name }}</small>
        </div>
        <span class="badge bg-secondary rounded-pill fs-6">
            {{ $conversations->total() }} {{ Str::plural('conversacion', $conversations->total()) }}
        </span>
    </div>

    @if ($conversations->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:72px;height:72px;">
                    <i class="far fa-comments fa-2x text-muted"></i>
                </div>
                <h5 class="fw-semibold">Sin conversaciones aún</h5>
                <p class="text-muted mb-0">Cuando contactes a nuestro equipo de soporte aparecerán aquí.</p>
            </div>
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($conversations as $conv)
                <a href="{{ route('helpdesk.portal.conversation', $conv->id) }}"
                   class="conversation-card card text-decoration-none text-reset">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        {{-- Channel icon --}}
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:44px;height:44px;background:#f0f0f0;">
                            @php $channelInfo = $conv->channel_info @endphp
                            <i class="{{ $channelInfo['icon'] }} fa-lg" style="color:{{ $channelInfo['color'] }};"></i>
                        </div>

                        {{-- Main info --}}
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="fw-semibold text-truncate">
                                    {{ $conv->subject ?? 'Conversación #'.$conv->id }}
                                </span>
                                <small class="text-muted flex-shrink-0">
                                    {{ $conv->last_message_at?->diffForHumans() ?? $conv->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <small class="text-muted d-block text-truncate">
                                {{ $channelInfo['label'] }}
                                @if ($conv->inbox)
                                    · {{ $conv->inbox->name }}
                                @endif
                            </small>
                        </div>

                        {{-- Status badge --}}
                        <div class="flex-shrink-0 d-flex align-items-center gap-2">
                            @if ($conv->status)
                                <span class="badge rounded-pill"
                                      style="background-color: {{ $conv->status->is_open ? '#13C672' : '#6c757d' }}">
                                    {{ $conv->status->name }}
                                </span>
                            @endif
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($conversations->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $conversations->links() }}
            </div>
        @endif
    @endif
@endsection
