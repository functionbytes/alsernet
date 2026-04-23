@php
    $ctaPhone       = theme_option('phone', '+351913893833');
    $ctaPhoneDigits = preg_replace('/[^0-9+]/', '', $ctaPhone);
    $ctaPhoneIntl   = preg_replace('/[^0-9]/', '', $ctaPhone);
    $ctaWhatsappUrl = 'https://api.whatsapp.com/send?phone=' . $ctaPhoneIntl;
    $ctaCallUrl     = 'tel:' . $ctaPhoneDigits;
@endphp

@if(!empty($ctaPhone))
<a href="{{ $ctaWhatsappUrl }}" target="_blank" rel="noopener"
   class="bt-buy-now theme-btn"
   aria-label="{{ __('cta_whatsapp_aria') }}">
    <i class="fab fa-whatsapp" aria-hidden="true"></i>
    <span>{{ __('cta_whatsapp') }}</span>
</a>
<a href="{{ $ctaCallUrl }}"
   class="bt-support-now theme-btn"
   aria-label="{{ __('cta_call_aria') }}">
    <i class="fa fa-phone" aria-hidden="true"></i>
    <span>{{ __('cta_call') }}</span>
</a>


@endif
