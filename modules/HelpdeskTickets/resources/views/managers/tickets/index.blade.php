@extends('layouts.theme')

@section('title', 'Tickets')

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/tickets.css') }}">
@endpush

@php
    /**
     * Construye el slug "lógico" del estado para el frontend a partir del modelo TicketStatus.
     * Mapeamos a los slugs canónicos que usa el JS (open, progress, pending, resolved, closed).
     */
    $statusSlug = function ($status) {
        if (!$status) return 'open';
        $raw = $status->slug ?? str()->slug($status->name ?? '');
        $known = ['open', 'progress', 'pending', 'resolved', 'closed'];
        if (in_array($raw, $known, true)) return $raw;
        // fallback: si está abierto -> open; si no -> closed
        return ($status->is_open ?? true) ? 'open' : 'closed';
    };

    $sourceSlug = function ($src) {
        $known = ['email', 'widget', 'wa', 'fb', 'ig', 'whatsapp', 'facebook', 'instagram', 'agent'];
        $aliases = ['whatsapp' => 'wa', 'facebook' => 'fb', 'instagram' => 'ig'];
        $s = strtolower((string) $src);
        return $aliases[$s] ?? (in_array($s, $known, true) ? $s : 'email');
    };

    $slaKind = function ($t) {
        if ($t->sla_resolution_breached || $t->sla_first_response_breached) return 'breach';
        $due = $t->sla_resolution_due_at;
        if (!$due) return 'ok';
        $minutes = now()->diffInMinutes($due, false);
        if ($minutes < 0) return 'breach';
        if ($minutes < 60) return 'warn';
        return 'ok';
    };

    $slaText = function ($t) {
        $due = $t->sla_resolution_due_at;
        if (!$due) return $t->resolved_at ? 'resuelto' : '—';
        $minutes = now()->diffInMinutes($due, false);
        if ($minutes < 0) return abs($minutes) . 'm vencido';
        if ($minutes < 60) return $minutes . 'm';
        $hours = floor($minutes / 60);
        return $hours . 'h ' . ($minutes % 60) . 'm';
    };

    $ticketsPayload = $tickets->getCollection()->map(function ($t) use ($statusSlug, $sourceSlug, $slaKind, $slaText) {
        $assignee = null;
        if ($t->assignee) {
            $name = trim(($t->assignee->firstname ?? '') . ' ' . ($t->assignee->lastname ?? '')) ?: ($t->assignee->name ?? 'Agente');
            $assignee = ['id' => $t->assignee->id, 'name' => $name];
        }

        return [
            'id' => $t->id,
            'ticket_number' => $t->ticket_number,
            'subject' => $t->subject ?? $t->title,
            'title' => $t->title,
            'description' => $t->description,
            'priority' => $t->priority ?? 'normal',
            'source' => $sourceSlug($t->source),
            'tags' => is_array($t->tags) ? array_values($t->tags) : [],
            'status_slug' => $statusSlug($t->status),
            'status_name' => $t->status?->name,
            'category_name' => $t->category?->name,
            'customer' => $t->customer ? [
                'id' => $t->customer->id,
                'name' => $t->customer->name,
                'email' => $t->customer->email,
            ] : null,
            'assignee' => $assignee,
            'created_at' => optional($t->created_at)->toIso8601String(),
            'created_at_human' => optional($t->created_at)->diffForHumans(),
            'updated_at' => optional($t->updated_at)->toIso8601String(),
            'assigned_at' => optional($t->assigned_at)->toIso8601String(),
            'unread_count' => method_exists($t, 'getUnreadCountForUser') ? (int) $t->getUnreadCountForUser(auth()->id()) : 0,
            'sla_kind' => $slaKind($t),
            'sla_text' => $slaText($t),
            'url' => route('manager.helpdesk.tickets.show', $t),
            'url_message_store' => route('manager.helpdesk.tickets.messages.store', $t),
        ];
    })->values();
@endphp

@section('content')
<div class="helpdesk-tickets-page">

    {{-- Datos para el JS (renderiza la tabla y el kanban) --}}
    <div id="htk-data"
         data-tickets="{{ json_encode($ticketsPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-statuses="{{ json_encode($statuses->map(fn($s) => ['slug' => $statusSlug($s), 'name' => $s->name]), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
         data-user-id="{{ auth()->id() }}"
         data-initial-filter="{{ request('quick_filter', 'open') }}"
         data-initial-view="{{ request('view', 'list') }}"
         data-bulk-url="{{ route('manager.helpdesk.tickets.bulk') }}"
         hidden>
    </div>

    <div class="htk-shell">
        @include('helpdesktickets::managers.tickets.partials._page-bar')
        @include('helpdesktickets::managers.tickets.partials._toolbar')
        @include('helpdesktickets::managers.tickets.partials._bulk-bar')
        @include('helpdesktickets::managers.tickets.partials._table')
        @include('helpdesktickets::managers.tickets.partials._kanban')

        @if($tickets->hasPages())
            <div class="px-4 py-3 bg-white border-top">
                {{ $tickets->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Off-canvas / overlay components (fuera del contenedor principal para z-index limpio) --}}
@include('helpdesktickets::managers.tickets.partials._detail-modal')
@include('helpdesktickets::managers.tickets.partials._contact-popover')
@include('helpdesktickets::managers.tickets.partials._filters-modal')

@endsection

@push('scripts')
    <script src="{{ asset('modules/helpdesktickets/js/tickets.js') }}"></script>
@endpush
