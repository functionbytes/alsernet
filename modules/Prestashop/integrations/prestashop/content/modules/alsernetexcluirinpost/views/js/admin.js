function removeQuantityDiscountOption(item) {
    var to = $('#'+$(item).attr('id').replace('remove', '1'));
    var from = $('#'+$(item).attr('id').replace('remove', '2'));

    var selected = from.find('option:selected');
    var selectedVal = [];
    selected.each(function(){
        selectedVal.push($(this).val());
    });

    var options = from.data('options');
    var tempOption = [];

    var targetOptions = to.data('options');

    $.each(options, function(i) {
        var option = options[i];
        if($.inArray(option.value, selectedVal) === -1) {
            tempOption.push(option);
        } else {
            targetOptions.push(option);
        }

    });

    from.find('option:selected').remove().appendTo(to);

    to.data('options', targetOptions);
    from.data('options', tempOption);
}

function addQuantityDiscountOption(item) {
    var to = $('#'+$(item).attr('id').replace('add', '2'));
    var from = $('#'+$(item).attr('id').replace('add', '1'));

    var selected = from.find('option:selected');
    var selectedVal = [];
    selected.each(function(){
        selectedVal.push($(this).val());
    });

    var options = from.data('options');
    var tempOption = [];

    var targetOptions = to.data('options');

    $.each(options, function(i) {
        var option = options[i];
        if($.inArray(option.value, selectedVal) === -1) {
            tempOption.push(option);
        } else {
            targetOptions.push(option);
        }

    });

    from.find('option:selected').remove().appendTo(to);

    to.data('options', targetOptions);
    from.data('options', tempOption);
}

jQuery.fn.filterByText = function(textbox, selectSingleMatch) {
    return this.each(function() {
        var select = $(this);
        var options = [];
        select.find('option').each(function() {
            options.push({value: $(this).val(), text: $(this).text()});
        });
        select.data('options', options);
        textbox = textbox.replace( /(:|\.|\[|\]|,|=|@)/g, "\\$1" );
        $(textbox).bind('keyup', function() {
            var options = select.empty().scrollTop(0).data('options');
            var search = $.trim($(this).val());
            var regex = new RegExp(search,'gi');

            var new_options_html = '';
            $.each(options, function(i, option) {
                if(option.text.match(regex) !== null) {
                    new_options_html += '<option value="' + option.value + '">' + option.text + '</option>';
                }
            });

            select.append(new_options_html);

            if (selectSingleMatch === true &&
                select.children().length === 1) {
                select.children().get(0).selected = true;
            } else if (select.children().length > 0) {
                select.children().get(0).selected = false;
            }
        })
    })
};

function showAlert(message, type) {
    // Crear el elemento de alerta
    var alertElement = document.createElement('div');
    alertElement.classList.add('alert', 'alert-' + type);
    alertElement.innerHTML = message;

    // Agregar el alerta al contenedor adecuado en PrestaShop
    var alertContainer = document.getElementById('content');
    alertContainer.insertBefore(alertElement, alertContainer.firstChild);

    // Mover el scroll al top de la página
    window.scrollTo(0, 0);

    // Desaparecer el alerta después de 3 segundos
    setTimeout(function() {
      alertElement.remove();
    }, 6000);
}

function addTypeProductOption(){

    var from = $('#product_type_select_2');

    var selected = from.find('option');

    var selectedVal = [];
    selected.each(function(){
        selectedVal.push($(this).val());
    });

    $.ajax({
        url: '/modules/alsernetexcluirinpost/datos.php',
        type: 'POST',
    data: { data: selectedVal },
        success: function(respuesta) {
            showAlert('Los cambios se han guardado correctamente.', 'success');
        },
        error: function(xhr, status, error) {
            showAlert('Error, Comunicar al Departamento de Desarrollo Web el número de error: ATPO-01.', 'danger');
        }
    });
}
