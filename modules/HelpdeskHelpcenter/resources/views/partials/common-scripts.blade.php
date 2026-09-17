{{--
    Boton ".delete-btn" (rellena core::components.delete) + toastr de
    exito/error de la sesion flash — repetido en varias vistas del Centro de
    Ayuda. Incluir una vez por vista:

      @include('helpdeskhelpcenter::partials.common-scripts')

    Trae su propio @push('scripts'), asi que basta con incluirlo en
    cualquier punto del cuerpo.
--}}
@push('scripts')
<script>window.HelpcenterFlash = @json(['success' => session('success'), 'error' => session('error')]);</script>
<script src="{{ asset('modules/helpdeskhelpcenter/js/helpcenter-common.js') }}?v={{ filemtime(public_path('modules/helpdeskhelpcenter/js/helpcenter-common.js')) }}"></script>
@endpush
