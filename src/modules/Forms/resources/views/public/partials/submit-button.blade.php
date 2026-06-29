@php
    $btnClass = 'btn btn-' . ($form->button_variant ?? 'primary');
    $btnSize = match($form->button_size ?? 'md') { 'sm' => 'btn-sm', 'lg' => 'btn-lg', default => '' };
    $btnWidth = ($form->button_position ?? 'left') === 'full' ? 'w-100' : '';
    $customStyle = $buttonColor ? "background-color:{$buttonColor};border-color:{$buttonColor}" : '';
@endphp
<button type="submit" class="{{ $btnClass }} {{ $btnSize }} {{ $btnWidth }} forms-submit-btn" style="{{ $customStyle }}">
    @if($form->button_icon)<i class="{{ $form->button_icon }} me-1"></i>@endif
    <span class="forms-submit-text">{{ $buttonText }}</span>
    <span class="forms-submit-spinner d-none">
        <span class="spinner-border spinner-border-sm me-1"></span>
        {{ ['es' => 'Enviando...', 'pt' => 'A enviar...', 'en' => 'Sending...', 'fr' => 'Envoi...'][app()->getLocale()] ?? 'Enviando...' }}
    </span>
</button>
