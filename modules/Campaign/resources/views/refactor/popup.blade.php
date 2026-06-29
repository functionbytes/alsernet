{{-- Minimal popup layout — loaded via McPopup AJAX into mc-modal-body.
     No <html>, no sidebar, no topbar. Just content + scripts. --}}
@yield('content')
@stack('scripts')
@yield('scripts')
