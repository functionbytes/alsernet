@extends('layouts.admin')

@section('title', 'Merge Contacts')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Merge Contacts</h1>
            <p class="text-muted">Combine multiple contact records into one</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10">
            <!-- Warning -->
            <div class="alert alert-warning mb-3">
                <h5 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> Warning</h5>
                <p class="mb-0">
                    Merging contacts is <strong>irreversible</strong>. All conversations and contact inbox connections
                    from the selected contacts will be moved to the primary contact, and the other contacts will be deleted.
                </p>
            </div>

            <!-- Merge Form -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.contacts.merge') }}" method="POST">
                        @csrf

                        <h5 class="mb-3">Select Primary Contact</h5>
                        <p class="text-muted">This contact will keep all information. Other contacts will be deleted.</p>

                        @foreach($contacts as $contact)
                            <div class="card mb-3 {{ $loop->first ? 'border-primary' : '' }}">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="primary_contact_id"
                                               id="primary_{{ $contact->id }}" value="{{ $contact->id }}"
                                               {{ $loop->first ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="primary_{{ $contact->id }}">
                                            <strong>{{ $contact->name }}</strong>
                                            @if($loop->first)
                                                <span class="badge bg-primary ms-2">Recommended</span>
                                            @endif
                                        </label>
                                    </div>
                                    <div class="ms-4 mt-2">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <small class="text-muted">Email:</small>
                                                <div>{{ $contact->email ?? '-' }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted">Phone:</small>
                                                <div>{{ $contact->phone_number ?? '-' }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted">Conversations:</small>
                                                <div><span class="badge bg-secondary">{{ $contact->conversations->count() }}</span></div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">Created: {{ $contact->created_at->format('M d, Y h:i A') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="merge_contact_ids[]" value="{{ $contact->id }}">
                        @endforeach

                        <hr>

                        <h5 class="mb-3">Summary</h5>
                        <ul>
                            <li>Total contacts to merge: <strong>{{ $contacts->count() }}</strong></li>
                            <li>Total conversations that will be combined: <strong>{{ $contacts->sum(fn($c) => $c->conversations->count()) }}</strong></li>
                            <li>Contacts that will be deleted: <strong>{{ $contacts->count() - 1 }}</strong></li>
                        </ul>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-shuffle"></i> Merge Contacts
                            </button>
                            <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">
                                <i class="fa fa-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
