{{-- Avisos propios en vez de core::components.alerts (aquel pinta los errores
     en rojo, prohibido en estas pantallas). --}}
@if (session('success'))
    <div class="era-note era-note--good" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="era-note era-note--warn" role="alert">{{ session('error') }}</div>
@endif
@if (isset($errors) && $errors->any())
    <div class="era-note era-note--warn" role="alert">
        No se ha guardado: revisa {{ $errors->count() === 1 ? 'el campo marcado' : 'los '.$errors->count().' campos marcados' }}.
        {{ $errors->first() }}
    </div>
@endif
