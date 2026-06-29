@extends('layouts.theme')

@section('title', 'Subscriber Lists')

@section('content')
<div class="py-4 px-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-list"></i> Subscriber Lists
            </h1>
            <p class="text-muted mb-0">Manage your subscriber lists and groups</p>
        </div>
        <a href="{{ route('mailrelay.lists.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New List
        </a>
    </div>

    <!-- Messages -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search & Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input
                        type="text"
                        class="form-control"
                        name="search"
                        placeholder="Search by name or description..."
                        value="{{ request('search') }}"
                    >
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search me-2"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lists Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Subscribers</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lists as $list)
                        <tr>
                            <td>
                                <strong>{{ $list->name }}</strong>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ Str::limit($list->description, 50) ?? 'N/A' }}
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    {{ $list->subscribers_count ?? 0 }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $list->created_at->format('M d, Y') }}
                                </small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('mailrelay.lists.show', $list) }}" class="btn btn-outline-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('mailrelay.lists.edit', $list) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('mailrelay.lists.destroy', $list) }}" method="POST"
                                          class="d-inline needs-confirm" data-confirm-msg="¿Eliminar esta lista?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <p class="text-muted mb-3">
                                    <i class="fas fa-inbox" style="font-size: 2rem;"></i>
                                </p>
                                <p class="text-muted">No lists found</p>
                                <a href="{{ route('mailrelay.lists.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i> Create your first list
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($lists->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $lists->links() }}
        </div>
    @endif
</div>
@endsection
