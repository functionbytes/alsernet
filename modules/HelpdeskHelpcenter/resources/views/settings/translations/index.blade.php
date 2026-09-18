@extends('layouts.theme')

@section('title', 'Traducciones del artículo')

@section('page_header')
    @include('core::components.card', ['title' => 'Centro de ayuda — Traducciones'])
@endsection

@section('content')

    @include('core::components.alerts')

    @php
        $supportedLocales = config('helpdeskhelpcenter.supported_locales', ['es', 'en']);
    @endphp

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Traducciones</h5>
                    <p class="small mb-0 text-muted">Artículo: {{ $article->title }}</p>
                </div>
                <a href="{{ route('manager.helpcenter.articles.edit', $article->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-chevron-left me-1"></i> Volver al artículo
                </a>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Idioma</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supportedLocales as $locale)
                            @php $translation = $translations->get($locale); @endphp
                            <tr>
                                <td>
                                    <span class="badge bg-light-secondary text-secondary">
                                        {{ strtoupper($locale) }}
                                    </span>
                                </td>
                                <td>
                                    @if($translation)
                                        <span class="fw-semibold">{{ $translation->title }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Sin traducción</span>
                                    @endif
                                </td>
                                <td>
                                    @if($translation?->is_published)
                                        <span class="badge bg-success-subtle text-success">Publicada</span>
                                    @elseif($translation)
                                        <span class="badge bg-warning-subtle text-warning">Borrador</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item"
                                                   href="{{ route('manager.helpcenter.articles.translations.edit', ['article' => $article, 'locale' => $locale]) }}">
                                                    {{ $translation ? 'Editar' : 'Crear traducción' }}
                                                </a>
                                            </li>
                                            @if($translation)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item delete-btn"
                                                       data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                       data-url="{{ route('manager.helpcenter.articles.translations.destroy', ['article' => $article, 'locale' => $locale]) }}"
                                                       data-title="Eliminar traducción: {{ strtoupper($locale) }}">
                                                        Eliminar
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.delete-btn').on('click', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
