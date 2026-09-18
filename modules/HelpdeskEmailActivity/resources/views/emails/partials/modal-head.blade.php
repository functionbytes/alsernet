{{-- Cabecera de modal del mockup: icono en caja, eyebrow en versalita,
     título y botón de cierre. Se incluye desde cada modal en vez de
     repetir el markup:

       @include('helpdeskemailactivity::emails.partials.modal-head', [
           'icon' => 'fa-share',
           'eyebrow' => __('helpdeskemailactivity::emaillog.modal.eyebrow.resend'),
           'title' => __('helpdeskemailactivity::emaillog.resend.to_title'),
           'titleId' => 'emaillog-resendto-title',
       ])

     'iconId' es opcional y solo lo usa el modal de confirmación genérico,
     que cambia el icono por JS según la acción que se esté confirmando. --}}
<div class="modal-header evx-dialog-head">
    <span class="evx-dialog-icon" @isset($iconId) id="{{ $iconId }}" @endisset aria-hidden="true">
        <i class="fa-solid {{ $icon }}"></i>
    </span>
    <span class="evx-dialog-heading">
        <span class="evx-dialog-eyebrow">{{ $eyebrow }}</span>
        <span class="evx-dialog-title" id="{{ $titleId }}">{{ $title }}</span>
    </span>
    <button type="button" class="evx-dialog-close" data-bs-dismiss="modal"
            aria-label="{{ __('helpdeskemailactivity::emaillog.modal.close') }}">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
</div>
