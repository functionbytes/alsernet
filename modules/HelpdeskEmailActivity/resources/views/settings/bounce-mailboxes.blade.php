@extends('layouts.theme')

@section('title', 'Actividad de correo — Buzones de rebote')

{{-- Sin page_header y con content_full_width, igual que el listado: el
     título de la franja del tema repetía el que ya lleva el breadcrumb de
     .evx-toolbar de dentro, y dejaba estas pantallas con un ancho distinto
     al del listado del que cuelgan. --}}
@section('content_full_width', true)

@include('helpdeskemailactivity::settings.partials.css')

@section('content')
    @include('core::components.alerts')

    <div class="emaillog-settings">
        <div class="evx-shell">
            @include('helpdeskemailactivity::settings.partials.subnav', ['current' => 'bounce-mailboxes'])

            <div class="evx-section-block d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h2 class="evx-section-title">{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.heading') }}</h2>
                    <p class="evx-section-desc mb-0">
                        Buzones IMAP que <span class="evx-mono">email-logs:process-bounces</span> revisa cada 10 minutos
                        en busca de rebotes (DSN) y quejas de spam. Sin ningún buzón habilitado, no se detecta ningún rebote.
                    </p>
                </div>
                <button type="button" class="evx-btn evx-btn-primary evx-btn-inline" data-bs-toggle="modal" data-bs-target="#bounce-mailbox-add-modal">
                    <i class="fas fa-plus" aria-hidden="true"></i> {{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.add') }}
                </button>
            </div>

            <div class="evx-list">
                @forelse($mailboxes as $mailbox)
                    <div class="evx-list-row">
                        <div class="evx-list-main">
                            <div class="evx-list-title">{{ $mailbox['label'] ?? '—' }}</div>
                            {{-- Una sola interpolación: separar "usuario" y "host" en dos
                                 {{ }} distintos con un "@" literal entre ambos hace que Blade
                                 confunda esa "@{{" con el escape @{{ }} (para imprimir "{{ }}"
                                 literal) y deje el host SIN renderizar — bug ya presente antes
                                 de este rediseño, nunca detectado por falta de datos reales. --}}
                            <div class="evx-list-sub evx-mono">
                                {{ ($mailbox['username'] ?? '').'@'.($mailbox['host'] ?? '').':'.($mailbox['port'] ?? 993).' · '.($mailbox['folder'] ?? 'INBOX') }}
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            @forelse(($mailbox['module_scope'] ?? []) as $mod)
                                <span class="evx-tag mono">{{ $mod }}</span>
                            @empty
                                <span class="evx-muted small">{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.all_modules') }}</span>
                            @endforelse
                        </div>

                        <span class="evx-badge {{ ($mailbox['enabled'] ?? false) ? 'ok' : 'neutral' }}">
                            {{ ($mailbox['enabled'] ?? false) ? 'Activo' : 'Inactivo' }}
                        </span>

                        @if(($mailbox['consecutive_failures'] ?? 0) >= 3)
                            <span class="evx-badge warn" title="{{ $mailbox['last_error'] ?? '' }}">
                                {{ $mailbox['consecutive_failures'] }} fallos
                            </span>
                        @endif

                        <span class="evx-list-date">
                            @if(!empty($mailbox['last_checked_at']))
                                {{ \Illuminate\Support\Carbon::parse($mailbox['last_checked_at'])->diffForHumans() }}
                            @else
                                Nunca revisado
                            @endif
                        </span>

                        <div class="dropdown">
                            <button type="button" class="evx-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Acciones">
                                <i class="fas fa-ellipsis-vertical" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" class="dropdown-item js-edit-mailbox"
                                            data-mailbox="{{ json_encode($mailbox) }}"
                                            data-update-url="{{ route('settings.helpdeskemailactivity.bounce-mailboxes.update', $mailbox['id']) }}">
                                        {{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.edit') }}
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('settings.helpdeskemailactivity.bounce-mailboxes.destroy', $mailbox['id']) }}"
                                          onsubmit="return confirm('¿Eliminar este buzón de rebote?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item">{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.delete') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                @empty
                    <div class="evx-empty-row">
                        <i class="fas fa-inbox" aria-hidden="true"></i>
                        <p>{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.empty') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Modal: añadir --}}
    <div class="modal fade evx-dialog" id="bounce-mailbox-add-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('settings.helpdeskemailactivity.bounce-mailboxes.store') }}" class="modal-content">
                @csrf
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-inbox',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.bounce_mailbox'),
                    'title' => __('helpdeskemailactivity::emaillog.bounce_mailboxes.add_title'),
                    'titleId' => 'a-adir-buz-n-de-rebote-title',
                ])
                <div class="modal-body">
                    @include('helpdeskemailactivity::settings.partials.bounce-mailbox-fields', ['prefix' => 'add'])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.add') }}</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: editar (un solo modal, rellenado por JS con los datos de la fila) --}}
    <div class="modal fade evx-dialog" id="bounce-mailbox-edit-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="bounce-mailbox-edit-form" class="modal-content">
                @csrf
                @method('PUT')
                @include('helpdeskemailactivity::emails.partials.modal-head', [
                    'icon' => 'fa-inbox',
                    'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.bounce_mailbox'),
                    'title' => __('helpdeskemailactivity::emaillog.bounce_mailboxes.edit_title'),
                    'titleId' => 'editar-buz-n-de-rebote-title',
                ])
                <div class="modal-body">
                    @include('helpdeskemailactivity::settings.partials.bounce-mailbox-fields', ['prefix' => 'edit'])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.save') }}</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('helpdeskemailactivity::emaillog.bounce_mailboxes.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('modules/helpdeskemailactivity/js/bounce-mailboxes.js') }}?v={{ filemtime(public_path('modules/helpdeskemailactivity/js/bounce-mailboxes.js')) }}"></script>
@endpush
