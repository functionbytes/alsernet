@extends('layouts.admin')

@section('title', 'Create Contact')

@section('content')

    @include('helpdeskchat::includes.card', [
        'title' => 'Create Contact',
        'icon' => 'fa-user-plus',
        'breadcrumbs' => [
            ['label' => 'Contacts', 'url' => route('admin.contacts.index')],
            ['label' => 'Create']
        ]
    ])

    <div class="widget-content">
        @include('helpdeskchat::components.alerts')

        <div class="row">
            <div class="col-md-8">
                <div class="card border">
                    <div class="card-body">
                        <form action="{{ route('admin.contacts.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="phone_number" class="form-label fw-bold">Phone Number</label>
                                <input type="text" class="form-control @error('phone_number') is-invalid @enderror"
                                       id="phone_number" name="phone_number" value="{{ old('phone_number') }}">
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="border-top pt-3">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">
                                        <i class="fa fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save me-2"></i>Create Contact
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 fw-bold">
                            <i class="fa fa-info-circle me-2"></i>About Contacts
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-0">
                            Contacts represent your customers or leads. At minimum, a contact needs a name.
                            Email and phone number are optional but recommended for communication.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
