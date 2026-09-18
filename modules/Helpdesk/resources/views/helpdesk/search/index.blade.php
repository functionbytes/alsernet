@extends('layouts.theme')

@section('title', 'Búsqueda — Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Búsqueda global'])
@endsection

@section('content')

    @php
        /** @var \Illuminate\Pagination\LengthAwarePaginator|null $results */
        $highlight = function (string $text) use ($term): string {
            if ($text === '' || $term === '') {
                return e($text);
            }
            $escapedText = e($text);
            $pattern = '/('.preg_quote($term, '/').')/iu';
            return preg_replace($pattern, '<mark class="hd-search-mark">$1</mark>', $escapedText);
        };
    @endphp

    <div class="card">

        {{-- Header con search input --}}
        <div class="card-header p-4 border-bottom border-light">
            <h5 class="mb-1 fw-bold">Búsqueda global</h5>
            <p class="small mb-3 text-muted">Encuentra conversaciones, contactos y mensajes</p>

            <form method="GET" action="{{ route('manager.helpdesk.search') }}" class="d-flex gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="input-group flex-fill">
                    <span class="input-group-text bg-white border-end-1">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="search" name="q"
                           class="form-control -0 ps-0"
                           placeholder="Busca por nombre, email, asunto, mensaje, ID..."
                           value="{{ $term }}"
                           autofocus
                           autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary">Buscar</button>
                @if($term !== '')
                    <a href="{{ route('manager.helpdesk.search') }}" class="btn btn-outline-secondary" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- Tabs --}}
        @if($hasQuery)
            <div class="card-body border-bottom py-0 px-4">
                <ul class="nav nav-tabs border-0 mb-n1" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $type === 'conversations' ? 'active fw-semibold' : '' }} px-3 py-3"
                           href="{{ route('manager.helpdesk.search', ['q' => $term, 'type' => 'conversations']) }}">
                            <i class="far fa-comments me-1"></i>
                            Conversaciones
                            <span class="badge bg-light text-dark ms-1">{{ number_format($counts['conversations']) }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $type === 'contacts' ? 'active fw-semibold' : '' }} px-3 py-3"
                           href="{{ route('manager.helpdesk.search', ['q' => $term, 'type' => 'contacts']) }}">
                            <i class="far fa-user me-1"></i>
                            Contactos
                            <span class="badge bg-light text-dark ms-1">{{ number_format($counts['contacts']) }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $type === 'messages' ? 'active fw-semibold' : '' }} px-3 py-3"
                           href="{{ route('manager.helpdesk.search', ['q' => $term, 'type' => 'messages']) }}">
                            <i class="far fa-message me-1"></i>
                            Mensajes
                            <span class="badge bg-light text-dark ms-1">{{ number_format($counts['messages']) }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        @endif

        {{-- Estado: sin query --}}
        @if(! $hasQuery && ! $tooShort)
            <div class="card-body">
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-search fs-7"></i>
                        </div>
                        <h6 class="mb-1">Empieza a escribir para buscar</h6>
                        <p class="text-muted mb-0">Mínimo {{ $minLength }} caracteres</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Estado: query muy corta --}}
        @if($tooShort)
            <div class="card-body">
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-circle-info me-1"></i>
                    Escribe al menos {{ $minLength }} caracteres para buscar.
                </div>
            </div>
        @endif

        {{-- Resultados --}}
        @if($hasQuery && $results)
            <div class="card-body p-0">
                @if($results->isEmpty())
                    <div class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="far fa-folder-open fs-7"></i>
                            </div>
                            <h6 class="mb-1">Sin resultados</h6>
                            <p class="text-muted mb-0">No se encontraron resultados para <strong>"{{ $term }}"</strong></p>
                        </div>
                    </div>
                @else

                    {{-- Conversaciones --}}
                    @if($type === 'conversations')
                        <div class="list-group list-group-flush">
                            @foreach($results as $row)
                                <a href="{{ $row['url'] }}" class="list-group-item list-group-item-action px-4 py-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                                <span class="fw-semibold text-truncate">
                                                    {!! $highlight($row['subject'] ?? 'Sin asunto') !!}
                                                </span>
                                                @if($row['status'])
                                                    <span class="badge bg-light text-dark">{{ $row['status'] }}</span>
                                                @endif
                                                @if($row['channel'])
                                                    <span class="badge bg-light-secondary text-secondary">{{ $row['channel'] }}</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                <i class="far fa-user me-1"></i>
                                                {!! $row['customer_name'] ? $highlight($row['customer_name']) : '<em>Sin cliente</em>' !!}
                                                @if($row['customer_email'])
                                                    · {!! $highlight($row['customer_email']) !!}
                                                @endif
                                                · #{{ $row['id'] }}
                                            </div>
                                        </div>
                                        @if($row['last_message_at'])
                                            <div class="small text-muted text-nowrap">
                                                {{ \Carbon\Carbon::parse($row['last_message_at'])->diffForHumans() }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Contactos --}}
                    @if($type === 'contacts')
                        <div class="list-group list-group-flush">
                            @foreach($results as $row)
                                <a href="{{ $row['url'] }}" class="list-group-item list-group-item-action px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="round-40 rounded-circle bg-light-primary text-primary d-flex align-items-center justify-content-center fw-semibold flex-shrink-0">
                                            {{ mb_strtoupper(mb_substr($row['name'] ?? '?', 0, 1)) }}
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold">{!! $highlight($row['name'] ?? 'Sin nombre') !!}</div>
                                            <div class="small text-muted">
                                                @if($row['email'])
                                                    <i class="far fa-envelope me-1"></i>{!! $highlight($row['email']) !!}
                                                @endif
                                                @if($row['phone'])
                                                    @if($row['email']) · @endif
                                                    <i class="fas fa-phone me-1"></i>{!! $highlight($row['phone']) !!}
                                                @endif
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted flex-shrink-0"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Mensajes --}}
                    @if($type === 'messages')
                        <div class="list-group list-group-flush">
                            @foreach($results as $row)
                                <a href="{{ $row['url'] }}" class="list-group-item list-group-item-action px-4 py-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="small text-muted mb-1">
                                                <i class="far fa-comments me-1"></i>
                                                {{ $row['conversation_subject'] ?? 'Sin asunto' }}
                                                @if($row['customer_name'])
                                                    · <i class="far fa-user me-1"></i>{{ $row['customer_name'] }}
                                                @endif
                                                · #{{ $row['conversation_id'] }}
                                            </div>
                                            <div class="text-body">{!! $highlight($row['snippet']) !!}</div>
                                        </div>
                                        @if($row['created_at'])
                                            <div class="small text-muted text-nowrap">
                                                {{ \Carbon\Carbon::parse($row['created_at'])->diffForHumans() }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            @if($results->hasPages())
                <div class="card-footer">
                    {{ $results->links() }}
                </div>
            @endif
        @endif

    </div>

@endsection

@push('styles')
<style>
.hd-search-mark {
    background-color: #fff3cd;
    color: inherit;
    padding: 0 .15rem;
    border-radius: .15rem;
    font-weight: 500;
}
.round-40 {
    width: 40px;
    height: 40px;
    font-size: .9rem;
}
.min-w-0 {
    min-width: 0;
}
.nav-tabs .nav-link {
    border-bottom-width: 2px;
    color: var(--bs-secondary-color, #6c757d);
}
.nav-tabs .nav-link.active {
    color: var(--bs-primary, #90bb13);
    border-color: transparent transparent var(--bs-primary, #90bb13);
    background-color: transparent;
}
</style>
@endpush
