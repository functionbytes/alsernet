@extends('helpdesktickets::portal.layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('portal.tickets') }}" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-1"></i>Back to my tickets
                </a>
                <h4 class="mb-0">Account settings</h4>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('portal.account.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $customer->name) }}"
                                required
                                maxlength="255"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input
                                type="email"
                                id="email"
                                class="form-control bg-light text-muted"
                                value="{{ $customer->email }}"
                                readonly
                                disabled
                            >
                            <div class="form-text">Email cannot be changed — it is used to log in via magic link.</div>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label fw-semibold">Phone</label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $customer->phone) }}"
                                maxlength="30"
                            >
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
