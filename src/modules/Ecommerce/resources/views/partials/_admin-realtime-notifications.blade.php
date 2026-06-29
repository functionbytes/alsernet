@if(config('broadcasting.connections.reverb.app_id'))
@push('scripts')
<script>
(function () {
    if (typeof window.Echo === 'undefined') {
        return;
    }

    window.Echo.channel('admin-orders').listen('.order.received', function (data) {
        if (typeof toastr !== 'undefined') {
            toastr.success(
                'Nueva orden <strong>#' + data.code + '</strong> de ' + data.customer_name + ' — $' + parseFloat(data.total).toFixed(2),
                'Orden recibida',
                { timeOut: 8000, escapeHtml: false }
            );
        }
    });
}());
</script>
@endpush
@endif
