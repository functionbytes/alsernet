@extends('layouts.theme')

@section('title', 'Kanban · Conversaciones')

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-identity.css') }}"/>
    <style>
        .hd-kanban-wrap {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: var(--bv-bg, #f4f5f7);
            font-family: 'Inter', sans-serif;
        }

        .hd-kanban-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: var(--bv-surface, #fff);
            border-bottom: 1px solid var(--bv-border, #e5e7eb);
            flex-shrink: 0;
            gap: 12px;
        }

        .hd-kanban-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--bv-text, #111827);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hd-kanban-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hd-kanban-board {
            display: flex;
            gap: 16px;
            padding: 16px 20px;
            overflow-x: auto;
            flex: 1;
            align-items: flex-start;
        }

        .hd-kanban-col {
            flex: 0 0 280px;
            min-width: 280px;
            background: var(--bv-bg-subtle, #f8f9fa);
            border-radius: 10px;
            border: 1px solid var(--bv-border, #e5e7eb);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 80px);
        }

        .hd-kanban-col-head {
            padding: 12px 14px 10px;
            border-bottom: 1px solid var(--bv-border, #e5e7eb);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .hd-kanban-col-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .hd-kanban-col-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--bv-text, #111827);
            flex: 1;
        }

        .hd-kanban-col-count {
            font-size: 11px;
            font-weight: 600;
            background: var(--bv-bg, #f4f5f7);
            color: var(--bv-text-muted, #6b7280);
            border-radius: 99px;
            padding: 1px 7px;
        }

        .hd-kanban-cards {
            padding: 10px;
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .hd-kanban-card {
            background: var(--bv-surface, #fff);
            border-radius: 8px;
            border: 1px solid var(--bv-border, #e5e7eb);
            padding: 12px;
            cursor: pointer;
            transition: box-shadow 0.15s, border-color 0.15s;
            user-select: none;
        }

        .hd-kanban-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            border-color: var(--bv-border-strong, #d1d5db);
        }

        .hd-kanban-card.sortable-ghost {
            opacity: 0.4;
            background: #f0f4ff;
            border: 2px dashed #90bb13;
        }

        .hd-kanban-card.sortable-chosen {
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
        }

        .hd-kanban-card-top {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
        }

        .hd-kanban-av {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .hd-kanban-av-1 { background: #6366f1; }
        .hd-kanban-av-2 { background: #ec4899; }
        .hd-kanban-av-3 { background: #f59e0b; }
        .hd-kanban-av-4 { background: #10b981; }
        .hd-kanban-av-5 { background: #3b82f6; }
        .hd-kanban-av-6 { background: #ef4444; }
        .hd-kanban-av-7 { background: #8b5cf6; }
        .hd-kanban-av-8 { background: #14b8a6; }

        .hd-kanban-card-info {
            flex: 1;
            min-width: 0;
        }

        .hd-kanban-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--bv-text, #111827);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hd-kanban-preview {
            font-size: 12px;
            color: var(--bv-text-muted, #6b7280);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
            margin-top: 3px;
        }

        .hd-kanban-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
            gap: 6px;
        }

        .hd-kanban-channel {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: var(--bv-text-muted, #6b7280);
        }

        .hd-kanban-channel i {
            font-size: 12px;
        }

        .hd-kanban-channel.wa i { color: #25d366; }
        .hd-kanban-channel.fb i { color: #1877f2; }
        .hd-kanban-channel.ig i { color: #e1306c; }
        .hd-kanban-channel.email i { color: #ea4335; }
        .hd-kanban-channel.web i { color: #3b82f6; }

        .hd-kanban-time {
            font-size: 11px;
            color: var(--bv-text-muted, #9ca3af);
            white-space: nowrap;
        }

        .hd-kanban-priority {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 4px;
            padding: 2px 6px;
        }

        .hd-kanban-priority.urgent { background: #fef2f2; color: #dc2626; }
        .hd-kanban-priority.high   { background: #fffbeb; color: #d97706; }
        .hd-kanban-priority.normal { background: #f0f9ff; color: #0284c7; }
        .hd-kanban-priority.low    { background: #f9fafb; color: #9ca3af; }

        .hd-kanban-empty {
            padding: 24px 14px;
            text-align: center;
            color: var(--bv-text-muted, #9ca3af);
            font-size: 12px;
        }

        .hd-kanban-empty i {
            font-size: 24px;
            display: block;
            margin-bottom: 8px;
            opacity: .4;
        }

        /* Mobile: hide board, show message */
        .hd-kanban-mobile-msg {
            display: none;
        }

        @media (max-width: 767px) {
            .hd-kanban-board {
                display: none;
            }
            .hd-kanban-mobile-msg {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                flex: 1;
                padding: 40px 24px;
                text-align: center;
                color: var(--bv-text-muted, #6b7280);
            }
            .hd-kanban-mobile-msg i {
                font-size: 48px;
                margin-bottom: 16px;
                opacity: .4;
            }
            .hd-kanban-mobile-msg h5 {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 8px;
                color: var(--bv-text, #374151);
            }
        }
    </style>
@endpush

@section('content')
<div class="hd-kanban-wrap">

    {{-- Header --}}
    <div class="hd-kanban-header">
        <div class="hd-kanban-title">
            <i class="fas fa-table-columns bv-x7"></i>
            Kanban — conversaciones
        </div>
        <div class="hd-kanban-actions">
            <a href="{{ route('manager.helpdesk.conversations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list-ul me-1"></i>Vista lista
            </a>
        </div>
    </div>

    {{-- Mobile fallback --}}
    <div class="hd-kanban-mobile-msg">
        <i class="fas fa-desktop"></i>
        <h5>Vista no disponible en móvil</h5>
        <p class="mb-3">Usa la vista de lista para gestionar conversaciones en pantallas pequeñas.</p>
        <a href="{{ route('manager.helpdesk.conversations.index') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-list-ul me-1"></i>Ir a vista lista
        </a>
    </div>

    {{-- Board --}}
    <div class="hd-kanban-board" id="hd-kanban-board">
        @foreach($statuses as $status)
            @php
                $cards = $byStatus->get($status->id, collect());
            @endphp
            <div class="hd-kanban-col"
                 data-status-id="{{ $status->id }}"
                 data-status-name="{{ e($status->name) }}">

                <div class="hd-kanban-col-head">
                    <span class="hd-kanban-col-dot" style="background:{{ $status->color ?? '#6c757d' }}"></span>
                    <span class="hd-kanban-col-name">{{ $status->name }}</span>
                    <span class="hd-kanban-col-count">{{ $cards->count() }}</span>
                </div>

                <div class="hd-kanban-cards" data-status-id="{{ $status->id }}">
                    @forelse($cards as $conv)
                        @php
                            $cust = $conv->customer;
                            $custName = $cust?->name ?? $conv->subject ?? 'Sin nombre';
                            $initials = mb_strtoupper(
                                collect(preg_split('/\s+/', trim($custName)))
                                    ->take(2)
                                    ->map(fn($w) => mb_substr($w, 0, 1))
                                    ->implode('')
                            ) ?: '?';
                            $colorIdx = (($cust?->id ?? $conv->id) - 1) % 8 + 1;
                            $channelMap = [
                                'whatsapp' => ['cls' => 'wa', 'icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp'],
                                'facebook' => ['cls' => 'fb', 'icon' => 'fab fa-facebook-messenger', 'label' => 'Facebook'],
                                'instagram' => ['cls' => 'ig', 'icon' => 'fab fa-instagram', 'label' => 'Instagram'],
                                'email' => ['cls' => 'email', 'icon' => 'far fa-envelope', 'label' => 'Email'],
                                'widget' => ['cls' => 'web', 'icon' => 'far fa-comment-dots', 'label' => 'Widget'],
                            ];
                            $ch = $channelMap[$conv->channel ?? 'widget'] ?? $channelMap['widget'];
                            $priority = $conv->priority ?? 'normal';
                            $priorityLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
                            $timeAgo = $conv->last_message_at?->diffForHumans(['short' => true]) ?? '';
                        @endphp
                        <div class="hd-kanban-card"
                             data-conv-id="{{ $conv->id }}"
                             data-update-url="{{ route('manager.helpdesk.conversations.update', $conv) }}"
                             data-show-url="{{ route('manager.helpdesk.conversations.show', $conv) }}">

                            <div class="hd-kanban-card-top">
                                <div class="hd-kanban-av hd-kanban-av-{{ $colorIdx }}">{{ $initials }}</div>
                                <div class="hd-kanban-card-info">
                                    <div class="hd-kanban-name">{{ $custName }}</div>
                                    <div class="hd-kanban-preview">
                                        {{ mb_strimwidth(strip_tags($conv->subject ?? ''), 0, 80, '…') }}
                                    </div>
                                </div>
                            </div>

                            <div class="hd-kanban-meta">
                                <span class="hd-kanban-channel {{ $ch['cls'] }}">
                                    <i class="{{ $ch['icon'] }}"></i>
                                    {{ $ch['label'] }}
                                </span>
                                @if($priority !== 'normal')
                                    <span class="hd-kanban-priority {{ $priority }}">
                                        {{ $priorityLabels[$priority] ?? $priority }}
                                    </span>
                                @endif
                                <span class="hd-kanban-time">{{ $timeAgo }}</span>
                            </div>

                        </div>
                    @empty
                        <div class="hd-kanban-empty">
                            <i class="fas fa-inbox"></i>
                            Sin conversaciones
                        </div>
                    @endforelse
                </div>

            </div>
        @endforeach
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    'use strict';

    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Init SortableJS on each column's card list
    document.querySelectorAll('.hd-kanban-cards').forEach(function (el) {
        Sortable.create(el, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            draggable: '.hd-kanban-card',
            onEnd: function (evt) {
                const card = evt.item;
                const toColumn = evt.to;
                const newStatusId = toColumn.dataset.statusId;
                const convId = card.dataset.convId;
                const updateUrl = card.dataset.updateUrl;

                if (!newStatusId || !convId || !updateUrl) return;

                // Optimistic: card already moved by SortableJS
                // Update column counts
                updateColumnCount(evt.from);
                updateColumnCount(evt.to);

                $.ajax({
                    url: updateUrl,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ status_id: parseInt(newStatusId) }),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    success: function (resp) {
                    },
                    error: function (xhr) {
                        // Revert: move card back to original column
                        const fromColumn = evt.from;
                        const originalNext = evt.oldIndex < fromColumn.children.length
                            ? fromColumn.children[evt.oldIndex]
                            : null;

                        if (originalNext) {
                            fromColumn.insertBefore(card, originalNext);
                        } else {
                            fromColumn.appendChild(card);
                        }

                        updateColumnCount(evt.from);
                        updateColumnCount(evt.to);

                        const msg = xhr?.responseJSON?.message || 'No se pudo actualizar el estado';
                        if (window.toastr) {
                            toastr.error(msg);
                        }
                    },
                });
            },
        });
    });

    // Click on card → open conversation
    $(document).on('click', '.hd-kanban-card', function (e) {
        if ($(e.target).closest('.hd-kanban-card').length) {
            const url = $(this).data('show-url');
            if (url) window.location.href = url;
        }
    });

    function updateColumnCount(colEl) {
        const count = colEl.querySelectorAll('.hd-kanban-card').length;
        const col = colEl.closest('.hd-kanban-col');
        if (col) {
            const countEl = col.querySelector('.hd-kanban-col-count');
            if (countEl) countEl.textContent = count;
        }
    }
})();
</script>
@endpush
