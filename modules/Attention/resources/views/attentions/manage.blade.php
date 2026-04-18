@extends('layouts.theme')

@section('title', 'Gestionar peticiones')

@section('content')

    @include('core::components.card', ['title' => 'Gestionar peticiones'])

    @include('core::components.alerts')

    <!-- Read-Only Notice for Closed Attentions -->
    @if($attention->isClosed())
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-lock me-2 fs-4"></i>
                <div>
                    <strong>peticiones cerrado</strong><br>
                    <small>Este peticiones ha sido cerrado y no se pueden realizar más modificaciones.</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <!-- Email Actions - Sidebar (Permission-Controlled & Read-Only Check) -->
            @if(auth()->user()->can('attention.send-emails') && !$attention->isClosed())
                @include('attention::components.email.actions', [
                    'attention' => $attention
                ])
            @endif

            <!-- Attention Notes (Permission-Controlled) -->
            @if(auth()->user()->can('attention.view-notes'))
                @include('attention::components.notes.attention-notes-sidebar')
            @endif

            <!-- Action History (Permission-Controlled) -->
            @if(auth()->user()->can('attention.view-history'))
                <div id="actionHistoryContainer">
                    @include('attention::components.management.action-history')
                </div>
            @endif

            <!-- Email History (Permission-Controlled) -->
            @if(auth()->user()->can('attention.view-emails'))
                @include('attention::components.email.history')
            @endif

            <!-- Status Timeline (Permission-Controlled) -->
            @if(auth()->user()->can('attention.view-history'))
                @include('attention::components.management.status-timeline')
            @endif

        </div>

        <div class="col-lg-8">

            <!-- Attention Details (Permission-Controlled) -->
            @if(auth()->user()->can('attention.view'))
                @include('attention::components.management.attention-details')
            @endif

            <!-- Customer Information (Permission-Controlled) -->
            @if(auth()->user()->can('attention.view'))
                @include('attention::components.management.customer-information')
            @endif

            <!-- Attention Management (Permission-Controlled & Read-Only Check) -->
            @if(auth()->user()->can('attention.manage') && !$attention->isClosed())
                @include('attention::components.management.attention-management', [
                    'attention' => $attention,
                    'statuses' => $statuses ?? [],
                    'departments' => $departments ?? [],
                    'users' => $users ?? [],
                    'responseTypes' => $responseTypes ?? []
                ])
            @endif

            <!-- Upload Section (Permission-Controlled & Read-Only Check) -->
            @if(auth()->user()->can('attention.manage-files') && !$attention->isClosed())
                @include('attention::components.files.upload-section', [
                    'attention' => $attention
                ])
            @endif

        </div>

    </div>

@endsection

{{-- Global Functions Used Across Components --}}
@push('scripts')
    <script>

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

    </script>
@endpush
