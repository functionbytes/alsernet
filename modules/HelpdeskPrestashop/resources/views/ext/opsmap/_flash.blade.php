{{-- Avisos propios en vez de core::components.alerts: aquel pinta los
     errores con bg-danger (rojo), prohibido en estas pantallas. --}}
@if (session('success'))
    <div class="psc-note psc-note--good psc-opsmap-flash" role="status">
        <i class="fas fa-check"></i><span class="psc-note-txt">{{ session('success') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="psc-note psc-note--warn psc-opsmap-flash" role="alert">
        <i class="fas fa-triangle-exclamation"></i><span class="psc-note-txt">{{ session('error') }}</span>
    </div>
@endif
@if (isset($errors) && $errors->any())
    <div class="psc-note psc-note--warn psc-opsmap-flash" role="alert">
        <i class="fas fa-triangle-exclamation"></i>
        <span class="psc-note-txt">{{ $errors->first() }}</span>
    </div>
@endif
