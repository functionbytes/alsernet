/**
 * DateRangePicker Initialization
 * Solo se inicializa daterangepicker, sin pickadate
 */

$(function() {
    // Cargar fechas previas si existen
    var dateFromInput = $('#date_from');
    var dateToInput = $('#date_to');
    var daterangeInput = $('#daterange');

    var startDate = null;
    var endDate = null;

    if (dateFromInput.val()) {
        startDate = moment(dateFromInput.val(), 'YYYY-MM-DD');
    }
    if (dateToInput.val()) {
        endDate = moment(dateToInput.val(), 'YYYY-MM-DD');
    }

    // Inicializar todos los elementos con clase daterange
    $('.daterange').daterangepicker({
        startDate: startDate || moment().subtract(30, 'days'),
        endDate: endDate || moment(),
        locale: {
            format: 'YYYY-MM-DD',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            daysOfWeek: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sab'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            firstDay: 1
        },
        showDropdowns: true,
        autoUpdateInput: true,
        alwaysShowCalendars: true,
        opens: 'left',
        ranges: {
            'Hoy': [moment(), moment()],
            'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes': [moment().startOf('month'), moment().endOf('month')],
            'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end, label) {
        // Actualizar los inputs hidden cuando se aplica el rango
        $('#date_from').val(start.format('YYYY-MM-DD'));
        $('#date_to').val(end.format('YYYY-MM-DD'));
    });

    // Mostrar rango inicial si existen las fechas
    if (startDate && endDate) {
        daterangeInput.val(startDate.format('YYYY-MM-DD') + ' - ' + endDate.format('YYYY-MM-DD'));
    }
});
