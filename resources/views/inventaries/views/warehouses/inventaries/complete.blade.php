
@extends('layouts.inventaries')

@section('title', 'Inventarios')

@section('content')

    <div class="container-fluid note-has-grid inventaries-complete">
        <div class="card">
            <div class="card-body text-center">
                <i class="fa-duotone fa-solid fa-clipboard-list-check"></i>
                <h5 class="fw-semibold mb-2">Ubicación validada</h5>
                <p class="mb-3 px-xl-5">Esta ubicación ya ha sido validada por el personal.</p>
                <a href="{{ route('inventarie.inventarie.arrange', $inventarie->uid ) }}" class="btn btn-primary w-100 mt-3">Volver</a>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
@endpush
