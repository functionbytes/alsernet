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

    // Array con los nombres de las variables
    var select  = ['categoria','familia','subfamilias','grupos'];
    var data    = ['category_select_2','family_select_2','subfamily_select_2','groups_select_2'];

    // Crear las variables con un bucle for
    for (let i = 0; i < select.length; i++) {
        var nombreVariable = select[i];

        var from = $('#'+data[i]+'');

        var selected = from.find('option');

        var selectedVal = [];
        selected.each(function(){
            selectedVal.push($(this).val());
        });

        window[nombreVariable] = selectedVal;

    }

    var id_feature_value = document.getElementById("feature_value").value;

    $.ajax({
        url: '/modules/alsernetarbolerp/insert.php',
        type: 'POST',
        data: { "id_feature_value": id_feature_value,
                "categoria": categoria,
                "familia": familia,
                "subfamilias": subfamilias,
                "grupos": grupos },
        dataType: 'json',
        success: function(respuesta) {
            document.getElementById('guardar_type_product_cfsg').disabled = true;

            crearTablaDesdeJSON(respuesta, "listoContainer", "success");
            //crearTablaDesdeJSON(respuesta["error"], "errorContainer", "danger");

            const buttons = document.querySelectorAll('#ButtonBorrrar');

            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const valorDelBoton = this.value;
                    const array1 = valorDelBoton.split(",");
                    this.disabled = true;
                    this.style.color = "white";
                    this.style.backgroundColor = "green";
                    ButtonBorrar(array1[0],array1[1]);
                });
            });
        },
        error: function(xhr, status, error) {
            showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: ATPO1.', 'danger');
        }
    });
}

function actualizar(value){
    if(value != ''){
        $.ajax({
            url: '/modules/alsernetarbolerp/datos.php',
            type: 'POST',
            data: { id_feature_value: value },
            dataType: 'json',
            success: function(respuesta) {
                document.getElementById('guardar_type_product_cfsg').disabled = false;

                document.getElementById('listoContainer').innerHTML = '';

                options_value('category_select_1',respuesta['listCategorySelected']);
                options_value('family_select_1',respuesta['listFamilySelected']);
                options_value('subfamily_select_1',respuesta['listSubFamilySelected']);
                options_value('groups_select_1',respuesta['listGroupsSelected']);

                options_value('category_select_2',respuesta['listCategoryUnselected']);
                options_value('family_select_2',respuesta['listFamilyUnselected']);
                options_value('subfamily_select_2',respuesta['listSubFamilyUnselected']);
                options_value('groups_select_2',respuesta['listGroupsUnselected']);

                document.getElementById('search_category_select_1').value = '';
                document.getElementById('search_category_select_2').value = '';
                document.getElementById('search_family_select_1').value = '';
                document.getElementById('search_family_select_2').value = '';
                document.getElementById('search_subfamily_select_1').value = '';
                document.getElementById('search_subfamily_select_2').value = '';
                document.getElementById('search_groups_select_1').value = '';
                document.getElementById('search_groups_select_2').value = '';

                $('#category_select_1').filterByText('#search_category_select_1', true);
                $('#category_select_2').filterByText('#search_category_select_2', true);

                $('#family_select_1').filterByText('#search_family_select_1', true);
                $('#family_select_2').filterByText('#search_family_select_2', true);

                $('#subfamily_select_1').filterByText('#search_subfamily_select_1', true);
                $('#subfamily_select_2').filterByText('#search_subfamily_select_2', true);

                $('#groups_select_1').filterByText('#search_groups_select_1', true);
                $('#groups_select_2').filterByText('#search_groups_select_2', true);
            },
            error: function(xhr, status, error) {
                showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: A01.', 'danger');
            }
        });
    }
}


function options_value(select_id,respuesta){
    var select = document.getElementById(select_id);
        select.disabled = false;

    for (let i = select.options.length; i >= 0; i--) {
        select.remove(i);
    }

    for (let index = 0; index < respuesta.length; index++) {

        var option = document.createElement("option");
        option.text = respuesta[index];
        option.value = respuesta[index];

        select.add(option);
    }
}

function crearTablaDesdeJSON(jsonObj, containerId, panelColor) {
    const container = document.getElementById(containerId);
    // Crear la tabla
    const tabla = document.createElement("table");
    tabla.style.borderCollapse = "collapse";
    tabla.style.width = "100%";

    // Crear encabezado de tabla (cabecera)
    const cabecera = document.createElement("tr");
    const cabeceraCols = ["Nombre", "ID PS", "Acción"];

    cabeceraCols.forEach(col => {
        const th = document.createElement("th");
        th.textContent = col;
        th.style.border = "1px solid black";
        th.style.padding = "8px";
        cabecera.appendChild(th);
    });
    tabla.appendChild(cabecera);

    // Crear filas con los registros en "listo" o "error"
    for (const clave in jsonObj) {
        if (jsonObj.hasOwnProperty(clave)) {
            const registro = jsonObj[clave];
            const fila = document.createElement("tr");

            const columnaNombre = document.createElement("td");
            columnaNombre.textContent = registro["nombre"];
            columnaNombre.style.border = "1px solid black";
            columnaNombre.style.padding = "8px";
            // if (panelColor === "danger") {
            //     columnaNombre.style.color = "red";
            // }
            fila.appendChild(columnaNombre);

            // const columnaTipoGrupo = document.createElement("td");
            // columnaTipoGrupo.textContent = registro["Tipo de grupo"];
            // columnaTipoGrupo.style.border = "1px solid black";
            // columnaTipoGrupo.style.padding = "8px";
            // if (panelColor === "success") {
            //     columnaNombre.style.color = "green";
            // }
            // fila.appendChild(columnaTipoGrupo);

            const columnaIDPS = document.createElement("td");
            columnaIDPS.textContent = registro["id_product"];
            columnaIDPS.style.border = "1px solid black";
            columnaIDPS.style.padding = "8px";
            // if (panelColor === "success") {
            //     columnaNombre.style.color = "green";
            // }
            fila.appendChild(columnaIDPS);


            const columnaActualizar = document.createElement("td");
            const btnActualizar = document.createElement("button");
            btnActualizar.textContent = "Borrar el tipo de producto";
            btnActualizar.id = "ButtonBorrrar";
            btnActualizar.value = registro["id_product"] + ',' + registro["id_feature_value"];
            btnActualizar.className = "btn btn-primary";
            btnActualizar.style.color = "white";
            btnActualizar.style.marginLeft = "10px";
            columnaActualizar.appendChild(btnActualizar);
            columnaActualizar.style.border = "1px solid black";
            columnaActualizar.style.padding = "8px";
            fila.appendChild(columnaActualizar);
            //console.log(registro["id_product"]+" - "+registro["id_feature_value"]);
            // if (panelColor === "danger") {
                ButtonActualizar(registro["id_product"],registro["id_feature_value"]);
            // }

            tabla.appendChild(fila);
        }
    }

    container.appendChild(tabla);
}

// Función para actualizar el contenido del "errorContainer"
function ButtonActualizar(id_product, id_feature_value) {
    $.ajax({
        url: '/modules/alsernetarbolerp/agregar_borrar_feature.php',
        type: 'POST',
        data: { id_product: id_product, id_feature_value: id_feature_value },
        success: function(respuesta) {
            console.log(respuesta);
        },
        error: function(xhr, status, error) {
            showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: BA01.', 'danger');
        }
    });
}

function ButtonBorrar(id_product, id_feature_value){
    $.ajax({
        url: '/modules/alsernetarbolerp/agregar_borrar_feature.php?tipo=borrar',
        type: 'POST',
        data: { id_product: id_product, id_feature_value: id_feature_value },
        success: function(respuesta) {
            console.log(respuesta);
        },
        error: function(xhr, status, error) {
            showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: BB01.', 'danger');
        }
    });
}