 @extends('layouts.theme')

@section('title', 'Gestionar Documento')

@section('content')

    @include('core::components.card', ['title' => 'Gestionar Documento'])

    @include('core::components.alerts')

    <!-- Read-Only Notice for Fully Approved Documents -->
    @if($document->isFullyApproved())
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-lock me-2 fs-4"></i>
                <div>
                    <strong>Documento aprobado</strong><br>
                    <small>Todas las etapas han sido completadas y el documento ha sido aprobado. Solo puede ver la información, no realizar cambios.</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <!-- Email Actions - Sidebar (Permission-Controlled & Read-Only Check) -->
            @if(auth()->user()->canDocument('email-actions') && !$document->isFullyApproved())
                @include('documents::documents.documents.components.email.actions', [
                    'document' => $document,
                    'documentConfig' => $documentConfig,
                    'requiredDocuments' => $requiredDocuments
                ])
            @endif

            <!-- Workflow Multi-Etapa (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-validation-workflow'))
                @include('documents::documents.documents.components.validation.workflow-sidebar')
            @endif

            <!-- Document Notes (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-document-notes'))
                @include('documents::documents.documents.components.notes.document-notes-sidebar')
            @endif

            <!-- Action History (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-action-history'))
                <div id="actionHistoryContainer">
                    @include('documents::documents.documents.components.management.action-history')
                </div>
            @endif

            <!-- Email History (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-email-history'))
                @include('documents::documents.documents.components.email.history')
            @endif

            <!-- Status Timeline (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-status-timeline'))
                @include('documents::documents.documents.components.management.status-timeline')
            @endif

        </div>

        <div class="col-lg-8">

            <!-- Products List (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-products-list'))
                @include('documents::documents.documents.components.management.products-list')
            @endif

            <!-- Order Details (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-document-details'))
                @include('documents::documents.documents.components.management.order-details')
            @endif

            <!-- Customer Information (Permission-Controlled) -->
            @if(auth()->user()->canDocument('view-customer-information'))
                @include('documents::documents.documents.components.management.customer-information')
            @endif

            <!-- Document Configuration (Permission-Controlled & Read-Only Check) -->
            @if(auth()->user()->canDocument('view-document-management') && !$document->isFullyApproved())
                @include('documents::documents.documents.components.management.document-management', [
                    'document' => $document,
                    'statuses' => $statuses,
                    'documentSources' => $documentSources,
                    'documentLoads' => $documentLoads,
                    'documentSyncs' => $documentSyncs,
                    'uploadTypes' => $uploadTypes
                ])
            @endif

            <!-- Upload Section (Permission-Controlled & Read-Only Check) -->
            @if(auth()->user()->canDocument('view-document-upload') && !$document->isFullyApproved())
                    @include('documents::documents.documents.components.files.upload-section', [
                        'document' => $document,
                        'requiredDocuments' => $requiredDocuments,
                        'uploadedDocs' => $uploadedDocs,
                        'missingDocs' => $missingDocs,
                        'allUploaded' => $allUploaded,
                    ])
            @endif

            <!-- Additional Attachments Section (Permission-Controlled & Read-Only Check) -->
            @if(auth()->user()->canDocument('view-additional-attachments') && !$document->isFullyApproved())
                @include('documents::documents.documents.components.files.additional-attachments')
            @endif

        </div>

    </div>

        @if(auth()->user()->canDocument('view-document-upload') && !$document->isFullyApproved())
            @include('documents::documents.documents.components.management.modals.confirm-missing-docs')
        @endif

        @if(auth()->user()->canDocument('view-document-upload') && !$document->isFullyApproved())
            @include('documents::documents.documents.components.files.modals.confirm-delete')
        @endif

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
