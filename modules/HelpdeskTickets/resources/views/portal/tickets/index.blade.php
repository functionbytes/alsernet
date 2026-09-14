@extends('helpdesktickets::portal.layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-ticket-alt me-2 text-primary"></i>My tickets
        </h4>
        <a href="{{ route('portal.tickets.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>New ticket
        </a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-1"></i>{{ session('status') }}
        </div>
    @endif

    @if ($tickets->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">You don't have any tickets yet.</p>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket #</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Category</th>
                            <th>Opened</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td class="text-muted align-middle">{{ $ticket->ticket_number }}</td>
                                <td class="align-middle fw-semibold">{{ $ticket->subject }}</td>
                                <td class="align-middle">
                                    @if ($ticket->status)
                                        <span
                                            class="badge"
                                            style="background-color: {{ $ticket->status->color ?? '#6c757d' }}"
                                        >
                                            {{ $ticket->status->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                                <td class="align-middle text-muted">
                                    {{ $ticket->category?->name ?? '—' }}
                                </td>
                                <td class="align-middle text-muted">
                                    {{ $ticket->created_at->format('d M Y') }}
                                </td>
                                <td class="align-middle text-end">
                                    <a
                                        href="{{ route('portal.tickets.show', $ticket->ticket_number) }}"
                                        class="btn btn-outline-primary btn-sm"
                                    >
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $tickets->links() }}
        </div>
    @endif
@endsection
