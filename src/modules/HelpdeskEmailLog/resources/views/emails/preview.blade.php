@extends('layouts.theme')

@section('title', __('helpdeskemaillog::emaillog.preview.title') . ' — ' . $log->subject)

@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskemaillog/css/emaillog.css') }}">
@endpush

@section('content')

    <div class="row g-3">

        {{-- Contenido principal --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold">{{ __('helpdeskemaillog::emaillog.preview.heading') }}</h6>
                    <div class="btn-group btn-group-sm email-log-toggle-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="btnDesktopView">
                            <i class="fas fa-desktop me-1"></i>{{ __('helpdeskemaillog::emaillog.preview.desktop') }}
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="btnMobileView">
                            <i class="fas fa-mobile-screen me-1"></i>{{ __('helpdeskemaillog::emaillog.preview.mobile') }}
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="email-log-preview-wrapper">
                        @if($log->body_html)
                            {{-- Rendered inside a sandboxed iframe: scripts cannot execute and the
                                 content lives in an opaque origin, so the original HTML is shown faithfully
                                 without any XSS risk to the admin panel. --}}
                            <iframe id="previewFrame" class="email-log-preview-frame" title="{{ __('helpdeskemaillog::emaillog.preview.heading') }}"
                                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                                    referrerpolicy="no-referrer"
                                    srcdoc="{{ $log->body_html }}"></iframe>
                        @elseif($log->body_text)
                            <div class="email-log-preview-frame bg-white p-4">
                                <pre class="mb-0 email-log-preview-text">{{ $log->body_text }}</pre>
                            </div>
                        @else
                            <div class="p-4 w-100">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>{{ __('helpdeskemaillog::emaillog.preview.no_content') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>{{ __('helpdeskemaillog::emaillog.preview.footer_note') }}
                    </small>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">

            {{-- Detalle --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">{{ __('helpdeskemaillog::emaillog.preview.detail') }}</h6>
                    <small class="text-muted">{{ __('helpdeskemaillog::emaillog.preview.detail_hint') }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.subject') }}</h6>
                            <p class="mb-0">{{ $log->subject ?: '—' }}</p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.from') }}</h6>
                            <p class="mb-0">
                                @if($log->from_name){{ $log->from_name }}<br>@endif
                                <span class="text-muted small">{{ $log->from_address }}</span>
                            </p>
                        </div>

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.to') }}</h6>
                            <p class="mb-0">
                                @foreach($log->to_addresses ?? [] as $addr)
                                    <span class="d-block small">{{ $addr }}</span>
                                @endforeach
                            </p>
                        </div>

                        @if($log->cc_addresses)
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.cc') }}</h6>
                                <p class="mb-0">
                                    @foreach($log->cc_addresses as $addr)
                                        <span class="d-block small">{{ $addr }}</span>
                                    @endforeach
                                </p>
                            </div>
                        @endif

                        @if($log->reply_to)
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.reply_to') }}</h6>
                                <p class="mb-0">
                                    @foreach($log->reply_to as $addr)
                                        <span class="d-block small">{{ $addr }}</span>
                                    @endforeach
                                </p>
                            </div>
                        @endif

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.status') }}</h6>
                            <p class="mb-0">
                                <span class="badge bg-{{ $log->status_color }}-subtle text-{{ $log->status_color }}">{{ $log->status_label }}</span>
                            </p>
                        </div>

                        @if($log->module)
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.module') }}</h6>
                                <p class="mb-0">
                                    <span class="badge bg-info-subtle text-info">{{ $log->module }}</span>
                                    @if($log->mailable_class)
                                        <br><code class="small text-muted">{{ class_basename($log->mailable_class) }}</code>
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if($log->entity_type)
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.entity') }}</h6>
                                <p class="mb-0 small">
                                    @if($log->entity_url)
                                        <a href="{{ $log->entity_url }}" target="_blank" rel="noopener">
                                            {{ $log->entity_type }} #{{ $log->entity_id }}
                                            <i class="fas fa-arrow-up-right-from-square fa-xs ms-1"></i>
                                        </a>
                                    @else
                                        {{ $log->entity_type }} #{{ $log->entity_id }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if($log->attachments)
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.attachments') }}</h6>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($log->attachments as $att)
                                        <li>
                                            <i class="fas fa-paperclip me-1 text-muted"></i>{{ $att['name'] ?? 'archivo' }}
                                            @if(!empty($att['size']))<span class="text-muted">({{ number_format(($att['size'] ?? 0) / 1024, 1) }} KB)</span>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($log->causer)
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.causer') }}</h6>
                                <p class="mb-0 small">{{ $log->causer->name ?? ($log->causer->email ?? ('#'.$log->causer_id)) }}</p>
                            </div>
                        @endif

                        <div class="col-12">
                            <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.sent_at') }}</h6>
                            <p class="mb-0 small">
                                {{ $log->display_date->format('d/m/Y H:i:s') }}
                                <span class="text-muted">({{ $log->display_date->diffForHumans() }})</span>
                            </p>
                        </div>

                        @if($log->message_id)
                            <div class="col-12">
                                <h6 class="text-muted fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.message_id') }}</h6>
                                <p class="mb-0"><code class="small text-muted text-break">{{ $log->message_id }}</code></p>
                            </div>
                        @endif

                        @if($log->error_message)
                            <div class="col-12">
                                <h6 class="text-danger fw-semibold small mb-1">{{ __('helpdeskemaillog::emaillog.preview.field.error') }}</h6>
                                <div class="alert alert-danger mb-0 small">{{ $log->error_message }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="card mb-3">
                <div class="card-header p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">{{ __('helpdeskemaillog::emaillog.preview.quick_actions') }}</h6>
                    <small class="text-muted">{{ __('helpdeskemaillog::emaillog.preview.quick_actions_hint') }}</small>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('helpdeskemaillog.manage')
                            <button type="button" class="btn btn-primary js-resend" data-url="{{ route('helpdeskemaillog.resend', $log->uid) }}">
                                <i class="fas fa-paper-plane me-1"></i>{{ __('helpdeskemaillog::emaillog.actions.resend') }}
                            </button>
                        @endcan
                        <button type="button" class="btn btn-outline-secondary" id="btnPrint">
                            <i class="fas fa-print me-1"></i>{{ __('helpdeskemaillog::emaillog.actions.print') }}
                        </button>
                        <a href="{{ route('helpdeskemaillog.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i>{{ __('helpdeskemaillog::emaillog.actions.back_to_list') }}
                        </a>
                        @can('helpdeskemaillog.manage')
                            <button type="button" class="btn btn-outline-danger btn-sm js-delete" data-url="{{ route('helpdeskemaillog.destroy', $log->uid) }}">
                                <i class="fas fa-trash me-1"></i>{{ __('helpdeskemaillog::emaillog.actions.delete') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal de confirmación reutilizable --}}
    <div class="modal fade" id="emaillog-confirm-modal" tabindex="-1" aria-hidden="true">
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

    $('#btnDesktopView').on('click', function () {
        $('#previewFrame').removeClass('is-mobile');
        $('.email-log-toggle-group .btn').removeClass('active');
        $(this).addClass('active');
    });
    $('#btnMobileView').on('click', function () {
        $('#previewFrame').addClass('is-mobile');
        $('.email-log-toggle-group .btn').removeClass('active');
        $(this).addClass('active');
    });
    $('#btnPrint').on('click', () => window.print());

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
});
</script>
@endpush
