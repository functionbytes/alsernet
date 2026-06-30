# JavaScript Patterns

## 1. BulkActions Plugin (compartido)

**Ubicacion**: `/public/core/js/bulk.js`

**API publica:**
```javascript
const bulk = window.BulkActions.init({
    checkbox  : '.bulk-checkbox',   // class de checkboxes individuales
    selectAll : '#select-all',      // checkbox cabecera
    toolbar   : '#bulk-toolbar',    // barra flotante con [data-bulk-count]
});

bulk.getIds()    // array de IDs seleccionados (parseInt)
bulk.getCount()  // numero de items seleccionados
bulk.reset()     // deselecciona todo y oculta toolbar
```

## 2. Bulk Action Handler (ajax submit)

```javascript
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#bulk-action-select').select2({
        dropdownParent: $('#bulk-modal'),
        width: '100%'
    });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una accion.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }

        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' registro(s)?')) return;

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route("resource.bulk-action") }}',
            method: 'POST',
            data: JSON.stringify({
                action: action,
                ids: ids,
                _token: $('meta[name="csrf-token"]').attr('content')
            }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.message || res.count + ' registro(s) procesados.');
                setTimeout(() => location.reload(), 800);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });
});
```

## 3. Delete Confirmation

```javascript
$(document).on('click', '.delete-btn', function (e) {
    e.preventDefault();
    $('#delete-modal .modal-title').text($(this).data('title'));
    $('#delete-form').attr('action', $(this).data('url'));
    $('#delete-modal').modal('show');
});
```

## 4. AJAX Delete (alternativa sin modal)

```javascript
$(document).on('click', '.delete-ajax-btn', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const url = $btn.data('url');
    const title = $btn.data('title');

    if (!confirm('¿Eliminar ' + title + '?')) return;

    $.ajax({
        url: url,
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
            toastr.success(res.message || 'Eliminado correctamente.');
            setTimeout(() => location.reload(), 800);
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message ?? 'Error al eliminar.');
        },
    });
});
```

## 5. Select2 Initialization

```javascript
// Basic
$('.select2').select2({ width: '100%' });

// Con dropdownParent (para modales)
$('#modal-select').select2({
    dropdownParent: $('#filterModal'),
    width: '100%'
});

// Sin busqueda (listas cortas)
$('.select2-no-search').select2({
    minimumResultsForSearch: Infinity,
    width: '100%'
});

// Con AJAX
$('#remote-select').select2({
    ajax: {
        url: '{{ route('api.resource.search') }}',
        dataType: 'json',
        delay: 250,
        data: params => ({ q: params.term }),
        processResults: data => ({
            results: data.map(item => ({ id: item.id, text: item.name }))
        }),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    },
    minimumInputLength: 2,
    width: '100%'
});
```

**NUNCA usar:** `theme: 'bootstrap-5'` (CSS no cargado).

## 6. DateRangePicker

```javascript
$('.daterange').daterangepicker({
    autoUpdateInput: false,
    locale: {
        format: 'DD/MM/YYYY',
        cancelLabel: 'Limpiar',
        applyLabel: 'Aplicar',
        daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                     'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    }
});

$('.daterange').on('apply.daterangepicker', function (ev, picker) {
    $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
    $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
    $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
});

$('.daterange').on('cancel.daterangepicker', function () {
    $(this).val('');
    $('#date_from').val('');
    $('#date_to').val('');
});
```

## 7. Form AJAX Submit with Validation Errors

```javascript
$('#resource-form').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $submit = $form.find('button[type="submit"]');

    // Clear previous errors
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.field-validation-error').remove();

    $submit.prop('disabled', true).text('Guardando...');

    $.ajax({
        url: $form.attr('action'),
        method: $form.attr('method'),
        data: $form.serialize(),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
            toastr.success(res.message || 'Guardado correctamente.');
            setTimeout(() => location.href = res.redirect || '{{ route('resource.index') }}', 800);
        },
        error: function (xhr) {
            $submit.prop('disabled', false).text('Guardar cambios');

            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                $.each(errors, function (field, messages) {
                    const $input = $form.find(`[name="${field}"]`);
                    $input.addClass('is-invalid');
                    $input.after(`<span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> ${messages[0]}</span>`);
                });
                toastr.error('Revisa los errores en el formulario.');
            } else {
                toastr.error(xhr.responseJSON?.message ?? 'Error al guardar.');
            }
        },
    });
});
```

## 8. Toastr Notifications

```javascript
// Success
toastr.success('Mensaje de exito', 'Titulo opcional');

// Error
toastr.error('Mensaje de error');

// Warning
toastr.warning('Mensaje de advertencia');

// Info
toastr.info('Mensaje informativo');

// From session (Blade)
@if(session('success'))
    toastr.success('{{ session('success') }}');
@endif
```

## 9. Icon Preview (form)

```javascript
$('#icon').on('input', function () {
    $('#iconPreview').attr('class', $(this).val());
});
```

## 10. Slug Auto-Generation

```javascript
$('#name').on('input', function () {
    if ($('#slug').data('manual')) return;

    $('#slug').val(
        $(this).val()
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
    );
});

$('#slug').on('input', function () {
    $(this).data('manual', $(this).val().length > 0);
});
```

## 11. ApexCharts — Area Chart (Dashboard)

```javascript
const chart = new ApexCharts(document.querySelector('#chart'), {
    series: [{ name: 'Total', data: valuesArray }],
    chart: {
        type: 'area',
        height: 295,
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit'
    },
    colors: ['#90bb13'],
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.15, opacityTo: 0.02, stops: [0, 100] }
    },
    xaxis: {
        categories: labelsArray,
        labels: { style: { fontSize: '11px', colors: '#adb5bd' } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: v => Math.round(v) }
    },
    grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
    tooltip: { theme: 'light', shared: true, intersect: false },
    legend: { show: false },
    markers: { size: 0 }
});
chart.render();
```

## 12. ApexCharts — Donut Chart

```javascript
const chart = new ApexCharts(document.querySelector('#donut-chart'), {
    series: dataValues,
    labels: dataLabels,
    chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
    colors: ['#90bb13', '#13C672', '#FEC90F', '#FA896B', '#333333'],
    legend: { show: false },
    dataLabels: { enabled: false },
    tooltip: { y: { formatter: v => new Intl.NumberFormat('es-ES').format(v) } },
    plotOptions: { pie: { donut: { size: '75%' } } }
});
chart.render();
```

## 13. ApexCharts — Sparkline

```javascript
new ApexCharts(document.querySelector('#spark'), {
    series: [{ data: sparkData }],
    chart: {
        type: 'area',
        height: 70,
        width: 70,
        sparkline: { enabled: true },
        animations: { enabled: false },
        fontFamily: 'inherit'
    },
    colors: ['#90bb13'],
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: { opacityFrom: 0.35, opacityTo: 0.02 }
    },
    tooltip: {
        fixed: { enabled: false },
        x: { show: false },
        y: { title: { formatter: () => '' } }
    }
});
```

## 14. ApexCharts — Bar Chart (Stacked)

```javascript
new ApexCharts(document.querySelector('#bar-chart'), {
    series: [
        { name: 'Exitosos', data: successData },
        { name: 'Fallidos', data: failedData }
    ],
    chart: {
        type: 'bar',
        height: 260,
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
        stacked: true
    },
    colors: ['#13C672', '#90bb13'],
    plotOptions: { bar: { borderRadius: 2, columnWidth: '70%' } },
    xaxis: {
        categories: labels,
        labels: { style: { fontSize: '11px', colors: '#adb5bd' } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: { style: { fontSize: '11px', colors: '#adb5bd' }, formatter: v => Math.round(v) }
    },
    grid: { borderColor: '#f0f0f0', strokeDashArray: 4 },
    tooltip: { theme: 'light', shared: true, intersect: false },
    legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'inherit' }
});
```

## 15. ApexCharts — RadialBar

```javascript
new ApexCharts(document.querySelector('#radial'), {
    series: [score],
    chart: { type: 'radialBar', height: 140, fontFamily: 'inherit' },
    plotOptions: {
        radialBar: {
            hollow: { size: '55%' },
            dataLabels: {
                show: true,
                name: { show: false },
                value: {
                    fontSize: '22px',
                    fontWeight: 600,
                    color: color,
                    formatter: v => v + '%'
                }
            }
        }
    },
    colors: [color],
    stroke: { lineCap: 'round' }
});
```

## 16. DevExpress DataGrid (para listados complejos)

```javascript
$('#dataGrid').dxDataGrid({
    dataSource: {
        store: new DevExpress.data.CustomStore({
            key: 'id',
            load: function (loadOptions) {
                return $.getJSON('{{ route('api.resource.index') }}', loadOptions);
            }
        })
    },
    columns: [
        { dataField: 'name', caption: 'Nombre' },
        { dataField: 'email', caption: 'Email' },
        {
            dataField: 'status',
            caption: 'Estado',
            cellTemplate: (container, options) => {
                const color = options.value === 'active' ? 'success' : 'secondary';
                container.html(`<span class="badge bg-${color}-subtle text-${color}">${options.value}</span>`);
            }
        },
        {
            type: 'buttons',
            buttons: [
                { hint: 'Ver', icon: 'info', onClick: e => location.href = `/resource/${e.row.data.id}` },
                { hint: 'Editar', icon: 'edit', onClick: e => location.href = `/resource/${e.row.data.id}/edit` }
            ]
        }
    ],
    paging: { pageSize: 25 },
    filterRow: { visible: true },
    searchPanel: { visible: true }
});
```

## 17. CSRF Token Helper (usar SIEMPRE)

```javascript
// Setup global para jQuery AJAX (recomendado en layout)
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// O por request individual
$.ajax({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
```

## 18. Global Confirm Modal

```javascript
window.__confirm('¿Estás seguro?', function () {
    // accion confirmada
});
```

## 19. Loader Global

El layout incluye un loader global que se activa automaticamente:
- En cada AJAX request (via `ajaxStart/ajaxStop`)
- En cada submit de formulario

No es necesario agregar loaders manuales para operaciones estandar.

## Reglas JavaScript

1. **Event delegation**: usa `$(document).on('click', '.selector', ...)` para contenido dinamico
2. **CSRF header**: SIEMPRE en requests POST/PUT/DELETE (o confiar en `$.ajaxSetup()` global)
3. **422 handling**: parsear `xhr.responseJSON.errors` y mostrar por campo
4. **Disable submit**: prevenir doble submit con `prop('disabled', true)`
5. **Reload delay**: `setTimeout(() => location.reload(), 800)` para que se vea el toast
6. **NO jQuery CDN**: usar el que ya esta cargado (NO volver a incluir)
7. **NO bootstrap-5 theme** en Select2
8. **Toastr globally**: disponible como `toastr.success/error/warning/info`
9. **ApexCharts**: cargar via CDN en `@push('scripts')`, destruir instancia anterior antes de crear nueva
10. **DevExpress**: cargar solo si el modulo lo requiere (NO es global)
