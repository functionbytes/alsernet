@php
    $waNumber = preg_replace('/[^0-9]/', '', \Modules\Core\Models\Setting::get('ecommerce.whatsapp_number', ''));
    $waMessage = \Modules\Core\Models\Setting::get('ecommerce.whatsapp_message', 'Hola, tengo una consulta sobre');
    $fullMessage = trim($waMessage . ' ' . url()->current());
@endphp

@if($waNumber)
    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($fullMessage) }}"
       target="_blank"
       rel="noopener noreferrer"
       class="whatsapp-floating-btn"
       aria-label="Contactar por WhatsApp"
       title="Contactar por WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
@endif
