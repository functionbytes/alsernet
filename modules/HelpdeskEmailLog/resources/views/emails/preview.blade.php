@extends('layouts.theme')

@section('title', __('helpdeskemaillog::emaillog.preview.title') . ' — ' . $log->subject)

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog.css') }}">
@endpush

@php
    $statusIcons = ['sent' => 'fa-check', 'failed' => 'fa-xmark', 'queued' => 'fa-clock'];
    $statusIcon = $statusIcons[$log->status?->value] ?? 'fa-circle';
    $canManage = auth()->user()?->can('helpdeskemaillog.manage') ?? false;
@endphp

@section('content')

    <div class="emaillog-viewer">
        <div class="evx-card">

            {{-- Cabecera --}}
            <div class="evx-head">
                <div class="evx-icon"><i class="fa-regular fa-envelope-open" aria-hidden="true"></i></div>
                <div class="evx-title-wrap">
                    <div class="evx-label">
                        {{ __('helpdeskemaillog::emaillog.title') }} · {{ Str::upper($log->status_label) }}
                    </div>
                    <div class="evx-title">
                        <span class="evx-subject">{{ $log->subject ?: '—' }}</span>
                        <span class="evx-chip">#{{ Str::upper(Str::substr($log->uid, 0, 8)) }}</span>
                    </div>
                </div>
                <a href="{{ route('helpdeskemaillog.index') }}" class="evx-close"
                   title="{{ __('helpdeskemaillog::emaillog.actions.back_to_list') }}">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>

            {{-- Cuerpo: vista previa + detalle --}}
            <div class="evx-body">

                {{-- Vista previa --}}
                <div class="evx-preview">
                    <div class="evx-preview-head">
                        <span>{{ __('helpdeskemaillog::emaillog.preview.heading') }}</span>
                        <div class="evx-device-toggle" role="group" aria-label="{{ __('helpdeskemaillog::emaillog.preview.heading') }}">
                            <button type="button" class="on" id="btnDesktopView" aria-pressed="true"
                                    aria-label="{{ __('helpdeskemaillog::emaillog.preview.desktop') }}"
                                    title="{{ __('helpdeskemaillog::emaillog.preview.desktop') }}">
                                <i class="fa-regular fa-window-maximize" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="btnMobileView" aria-pressed="false"
                                    aria-label="{{ __('helpdeskemaillog::emaillog.preview.mobile') }}"
                                    title="{{ __('helpdeskemaillog::emaillog.preview.mobile') }}">
                                <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="evx-preview-body">
                        @if($log->body_html)
                            {{-- Renderizado dentro de un iframe con sandbox: los scripts no se ejecutan y
                                 el contenido vive en un origen opaco, mostrando el HTML original con fidelidad
                                 pero sin riesgo de XSS para el panel de administración. --}}
                            <iframe id="previewFrame" class="evx-frame"
                                    title="{{ __('helpdeskemaillog::emaillog.preview.heading') }}"
                                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                                    referrerpolicy="no-referrer"
                                    srcdoc="{{ $log->body_html }}"></iframe>
                        @elseif($log->body_text)
                            <pre class="evx-preview-text">{{ $log->body_text }}</pre>
                        @else
                            <div class="evx-empty">
                                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                                {{ ($log->metadata['redacted'] ?? false)
                                    ? __('helpdeskemaillog::emaillog.preview.purged_note')
                                    : __('helpdeskemaillog::emaillog.preview.no_content') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Detalle --}}
                <div class="evx-detail">

                    {{-- Detalle del email --}}
                    <div class="evx-section">
                        <div class="evx-hdr">
                            <span class="t">{{ __('helpdeskemaillog::emaillog.preview.detail') }}</span>
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.detail_hint') }}</span>
                        </div>

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.subject') }}</span>
                            <span class="v">{{ $log->subject ?: '—' }}</span>
                        </div>

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.from') }}</span>
                            <span class="v">
                                @if($log->from_name){{ $log->from_name }} @endif
                                <span class="mono">{{ $log->from_address }}</span>
                            </span>
                        </div>

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.to') }}</span>
                            <span class="v mono">
                                @forelse($log->to_addresses ?? [] as $addr)
                                    {{ $addr }}@if(!$loop->last)<br>@endif
                                @empty
                                    —
                                @endforelse
                            </span>
                        </div>

                        @if($log->cc_addresses)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.cc') }}</span>
                                <span class="v mono">
                                    @foreach($log->cc_addresses as $addr)
                                        {{ $addr }}@if(!$loop->last)<br>@endif
                                    @endforeach
                                </span>
                            </div>
                        @endif

                        @if($log->bcc_addresses)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.bcc') }}</span>
                                <span class="v mono">
                                    @foreach($log->bcc_addresses as $addr)
                                        {{ $addr }}@if(!$loop->last)<br>@endif
                                    @endforeach
                                </span>
                            </div>
                        @endif

                        @if($log->reply_to)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.reply_to') }}</span>
                                <span class="v mono">
                                    @foreach($log->reply_to as $addr)
                                        {{ $addr }}@if(!$loop->last)<br>@endif
                                    @endforeach
                                </span>
                            </div>
                        @endif

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.status') }}</span>
                            <span class="v">
                                <span class="evx-status {{ $log->status?->value }}">
                                    <i class="fa-solid {{ $statusIcon }}" aria-hidden="true"></i>{{ $log->status_label }}
                                </span>
                            </span>
                        </div>

                        @if($log->module)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.module') }}</span>
                                <span class="v">
                                    <span class="evx-tag">{{ $log->module }}</span>
                                    @if($log->mailable_class)
                                        <span class="evx-tag mono">{{ class_basename($log->mailable_class) }}</span>
                                    @endif
                                </span>
                            </div>
                        @endif

                        @if($log->attachments)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.attachments') }}</span>
                                <span class="v">
                                    <ul class="evx-attachments">
                                        @foreach($log->attachments as $att)
                                            <li>
                                                <i class="fa-solid fa-paperclip"></i>{{ $att['name'] ?? 'archivo' }}
                                                @if(!empty($att['size']))
                                                    <span class="muted">({{ number_format(($att['size'] ?? 0) / 1024, 1) }} KB)</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </span>
                            </div>
                        @endif

                        @if($log->causer)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.causer') }}</span>
                                <span class="v">{{ $log->causer->name ?? ($log->causer->email ?? ('#'.$log->causer_id)) }}</span>
                            </div>
                        @endif

                        <div class="evx-field">
                            <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.sent_at') }}</span>
                            <span class="v mono">
                                {{ $log->display_date->format('d/m/Y H:i:s') }}
                                <span class="muted">· {{ $log->display_date->diffForHumans() }}</span>
                            </span>
                        </div>

                        @if($log->message_id)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.message_id') }}</span>
                                <span class="v mono evx-copy-row">
                                    <span class="evx-copy-text">{{ $log->message_id }}</span>
                                    <button type="button" class="evx-copy-btn" data-copy="{{ $log->message_id }}"
                                            aria-label="{{ __('helpdeskemaillog::emaillog.actions.copy_id') }}"
                                            title="{{ __('helpdeskemaillog::emaillog.actions.copy_id') }}">
                                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                    </button>
                                </span>
                            </div>
                        @endif

                        @if($log->error_message)
                            <div class="evx-field">
                                <span class="k">{{ __('helpdeskemaillog::emaillog.preview.field.error') }}</span>
                                <div class="evx-alert">{{ $log->error_message }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Acciones rápidas --}}
                    <div class="evx-section">
                        <div class="evx-hdr">
                            <span class="t">{{ __('helpdeskemaillog::emaillog.preview.quick_actions') }}</span>
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.quick_actions_hint') }}</span>
                        </div>
                        <div class="evx-actions">
                            @if($canManage)
                                <button type="button" class="evx-btn evx-btn-primary js-resend"
                                        data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                                    {{ __('helpdeskemaillog::emaillog.actions.resend') }}
                                </button>
                                <button type="button" class="evx-btn evx-btn-outline" id="btnResendTo">
                                    {{ __('helpdeskemaillog::emaillog.actions.resend_to') }}
                                </button>
                            @endif
                            @if($log->body_html || $log->body_text)
                                <a href="{{ route('helpdeskemaillog.download', $log->uid) }}" class="evx-btn evx-btn-outline">
                                    {{ __('helpdeskemaillog::emaillog.actions.download') }}
                                </a>
                            @endif
                            <button type="button" class="evx-btn evx-btn-outline" id="btnPrint">
                                {{ __('helpdeskemaillog::emaillog.actions.print') }}
                            </button>
                            <a href="{{ route('helpdeskemaillog.index') }}" class="evx-btn evx-btn-outline">
                                {{ __('helpdeskemaillog::emaillog.actions.back_to_list') }}
                            </a>
                            @if($canManage)
                                <button type="button" class="evx-btn evx-btn-danger js-delete"
                                        data-url="{{ route('helpdeskemaillog.destroy', $log->uid) }}">
                                    {{ __('helpdeskemaillog::emaillog.actions.delete') }}
                                </button>
                            @endif
                            @if($canManage && ($log->body_html || $log->body_text))
                                <button type="button" class="evx-btn evx-btn-danger js-purge"
                                        data-url="{{ route('helpdeskemaillog.purge-body', $log->uid) }}">
                                    {{ __('helpdeskemaillog::emaillog.actions.purge') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Entidad relacionada --}}
                    @if($log->entity_type)
                        <div class="evx-section">
                            <div class="evx-hdr">
                                <span class="t">{{ __('helpdeskemaillog::emaillog.preview.related_entity') }}</span>
                                <span class="s">{{ __('helpdeskemaillog::emaillog.preview.related_entity_hint') }}</span>
                            </div>
                            <div class="evx-field">
                                <span class="k">{{ $log->entity_label }}</span>
                                <span class="v">
                                    @if($log->entity_url)
                                        <a href="{{ $log->entity_url }}" target="_blank" rel="noopener">
                                            {{ $log->entity_label }} #{{ $log->entity_id }}
                                            <i class="fa-solid fa-arrow-up-right-from-square fa-xs" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        {{ $log->entity_label }} #{{ $log->entity_id }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Emails relacionados --}}
                    <div class="evx-section">
                        <div class="evx-hdr">
                            <span class="t">{{ __('helpdeskemaillog::emaillog.preview.related_emails') }}</span>
                            <span class="s">{{ __('helpdeskemaillog::emaillog.preview.related_emails_hint') }}</span>
                        </div>
                        @forelse($related as $rel)
                            @php $rv = $rel->status?->value; @endphp
                            <a href="{{ route('helpdeskemaillog.show', $rel->uid) }}" class="evx-related">
                                <span class="evx-related-subject">{{ Str::limit($rel->subject, 42) ?: '—' }}</span>
                                <span class="evx-related-meta">
                                    <span class="evx-status {{ $rv }}">
                                        <i class="fa-solid {{ ['sent' => 'fa-check', 'failed' => 'fa-xmark', 'queued' => 'fa-clock'][$rv] ?? 'fa-circle' }}" aria-hidden="true"></i>{{ $rel->status_label }}
                                    </span>
                                    <span class="evx-related-date">{{ $rel->display_date->format('d/m/Y H:i') }}</span>
                                </span>
                            </a>
                        @empty
                            <div class="evx-field"><span class="v"><span class="muted">{{ __('helpdeskemaillog::emaillog.preview.no_related') }}</span></span></div>
                        @endforelse
                    </div>

                </div>
            </div>

            {{-- Nota inferior --}}
            <div class="evx-bottom-note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                {{ __('helpdeskemaillog::emaillog.preview.footer_note') }}
            </div>

        </div>
    </div>

    {{-- Modal de confirmación reutilizable --}}
    <div class="modal fade" id="emaillog-confirm-modal" tabindex="-1"
         aria-labelledby="emaillog-confirm-title" aria-describedby="emaillog-confirm-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emaillog-confirm-title">{{ __('helpdeskemaillog::emaillog.confirm.title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="emaillog-confirm-message">—</p>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="emaillog-confirm-accept">
                        {{ __('helpdeskemaillog::emaillog.confirm.accept') }}
                    </button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                        {{ __('helpdeskemaillog::emaillog.confirm.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @can('helpdeskemaillog.manage')
        {{-- Modal: reenviar a otra dirección --}}
        <div class="modal fade" id="emaillog-resendto-modal" tabindex="-1"
             aria-labelledby="emaillog-resendto-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="emaillog-resendto-title">{{ __('helpdeskemaillog::emaillog.resend.to_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <label for="resendto-email" class="form-label fw-semibold small">
                            {{ __('helpdeskemaillog::emaillog.resend.to_label') }}
                        </label>
                        <input type="email" class="form-control" id="resendto-email"
                               placeholder="{{ __('helpdeskemaillog::emaillog.resend.to_placeholder') }}"
                               data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                        <div class="form-text">{{ __('helpdeskemaillog::emaillog.resend.to_hint') }}</div>
                    </div>
                    <div class="modal-footer flex-column">
                        <button type="button" class="btn btn-primary w-100 mb-2" id="resendto-send">
                            {{ __('helpdeskemaillog::emaillog.resend.to_send') }}
                        </button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                            {{ __('helpdeskemaillog::emaillog.confirm.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

@endsection

@push('scripts')
<script>
$(function () {
    @if(session('success')) toastr.success(@json(session('success'))); @endif
    @if(session('error')) toastr.error(@json(session('error'))); @endif

    const csrf = $('meta[name="csrf-token"]').attr('content');
    const $confirmModal = $('#emaillog-confirm-modal');
    const confirmModal = new bootstrap.Modal($confirmModal[0]);
    let pendingAccept = null;

    function askConfirm({ title, message, onAccept }) {
        $('#emaillog-confirm-title').text(title);
        $('#emaillog-confirm-message').text(message);
        pendingAccept = onAccept;
        confirmModal.show();
    }

    $('#emaillog-confirm-accept').on('click', function () {
        const fn = pendingAccept;
        pendingAccept = null;
        confirmModal.hide();
        if (typeof fn === 'function') fn();
    });

    // Alternar vista escritorio / móvil
    $('#btnDesktopView').on('click', function () {
        $('#previewFrame').removeClass('is-mobile');
        $('.evx-device-toggle button').removeClass('on').attr('aria-pressed', 'false');
        $(this).addClass('on').attr('aria-pressed', 'true');
    });
    $('#btnMobileView').on('click', function () {
        $('#previewFrame').addClass('is-mobile');
        $('.evx-device-toggle button').removeClass('on').attr('aria-pressed', 'false');
        $(this).addClass('on').attr('aria-pressed', 'true');
    });
    $('#btnPrint').on('click', () => window.print());

    // Copiar Message-ID
    $(document).on('click', '.evx-copy-btn', function () {
        const text = $(this).data('copy');
        const done = () => toastr.success(@json(__('helpdeskemaillog::emaillog.copy.message_id_copied')));
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            const $t = $('<textarea>').val(text).css({ position: 'fixed', opacity: 0 }).appendTo('body');
            $t[0].select();
            document.execCommand('copy');
            $t.remove();
            done();
        }
    });

    @can('helpdeskemaillog.manage')
    // Reenviar a otra dirección
    const $resendToModal = $('#emaillog-resendto-modal');
    const resendToModal = new bootstrap.Modal($resendToModal[0]);
    $('#btnResendTo').on('click', () => { $('#resendto-email').val(''); resendToModal.show(); });

    $('#resendto-send').on('click', function () {
        const $input = $('#resendto-email');
        const to = ($input.val() || '').trim();
        const url = $input.data('url');
        if (!to) { $input.addClass('is-invalid'); return; }
        $input.removeClass('is-invalid');
        $.ajax({ url, method: 'POST', data: { to }, headers: { 'X-CSRF-TOKEN': csrf } })
            .done(() => location.reload())
            .fail(xhr => {
                const msg = xhr.responseJSON?.errors?.to?.[0] || xhr.responseJSON?.message || 'Error';
                toastr.error(msg);
            });
    });
    @endcan

    // Reenviar
    $('.js-resend').on('click', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.resend.confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.resend.confirm')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Eliminar
    $('.js-delete').on('click', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.confirm.delete_title')),
            message: @json(__('helpdeskemaillog::emaillog.confirm.delete_one')),
            onAccept: () => {
                $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => window.location = @json(route('helpdeskemaillog.index')))
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });

    // Purgar contenido (GDPR)
    $('.js-purge').on('click', function () {
        const url = $(this).data('url');
        askConfirm({
            title: @json(__('helpdeskemaillog::emaillog.purge.confirm_title')),
            message: @json(__('helpdeskemaillog::emaillog.purge.confirm')),
            onAccept: () => {
                $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } })
                    .done(() => location.reload())
                    .fail(xhr => toastr.error(xhr.responseJSON?.message || 'Error'));
            },
        });
    });
});
</script>
@endpush
