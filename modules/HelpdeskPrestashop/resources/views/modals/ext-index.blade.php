{{-- Modales/hojas de las extensiones del módulo: cada
     resources/views/modals/ext/<nombre>.blade.php se incluye aquí (carga su
     propio CSS con <link> y su JS con @push('scripts')). --}}
@foreach (glob(module_path('HelpdeskPrestashop', 'resources/views/modals/ext/*.blade.php')) ?: [] as $psExtView)
    @include('helpdeskprestashop::modals.ext.'.basename($psExtView, '.blade.php'))
@endforeach
