var pre_guardado_imagen     = 0;
var datos_pre_guardado      = [];
var interruptor             = '';
var pintado                 = '';


$(document).ready(function() {
    // Obtén una referencia al elemento del interruptor
    interruptor = document.getElementById('flexSwitchCheckChecked');

    // Validar el estado inicial del interruptor al cargar la página
    validarEstadoInterruptor();

    // Agregar un evento de cambio al interruptor
    $(interruptor).change(function() {
        validarEstadoInterruptor();
    });
});


/**
 * Escucha el evento 'click' en el documento y realiza acciones dependiendo del elemento clicado.
 * Si el elemento clicado es una imagen dentro de un contenedor con la clase 'image-container',
 * llama a la función toggleImageSelection para alternar la selección de la imagen.
 * @param {Event} event - El evento de clic.
 */
document.addEventListener('click', function(event) {
    var target = event.target;

    // Obtener el contenedor más cercano con la clase 'image-container' desde el elemento clicado
    var imageContainer = target.closest('.image-container');

    // Verificar si se encontró un contenedor de imagen
    if (imageContainer) {
        // Si se encontró, llamar a la función toggleImageSelection para alternar la selección de la imagen.
        toggleImageSelection(imageContainer);
    }
});



/**
 * Agrega opciones al campo de selección de imágenes y muestra las imágenes relacionadas
 * con la opción seleccionada. Si se especifica `selectimagen` como `true`, selecciona
 * automáticamente la primera imagen de la lista y actualiza la imagen mostrada.
 * @param {boolean} selectimagen - Indica si se debe seleccionar automáticamente la
 * primera imagen y actualizar la imagen mostrada.
 */
function addTypeProductOption(selectimagen = false) {
    // Obtener el valor del campo de entrada con el ID "buscar_id_erp"
    var id_erp = document.getElementById('buscar_id_erp').value;

    // Verificar si el campo ID - ERP no está vacío
    if (id_erp != '') {
        // Deshabilitar el campo ID - ERP para evitar cambios durante la solicitud AJAX
        document.getElementById("buscar_id_erp").disabled = true;

        // Realizar una solicitud AJAX para obtener datos relacionados con el ID del modelo
        $.ajax({
            url: '/modules/alsernetimagenes/datos.php?datos=v1',
            type: 'POST',
            data: { id_modelo: id_erp },
            dataType: 'json',
            success: function(respuesta) {
                // La solicitud AJAX fue exitosa, por lo que procedemos a actualizar
                // el campo de selección de imágenes y mostrar las imágenes.

                // Habilitar el campo de selección de imágenes y el campo "aprobado"
                var select = document.getElementById("imagen_value");
                select.disabled = false;
                document.getElementById("aprobado").disabled = false;

                // Limpiar el contenedor de imágenes
                var imagenesContainer = document.getElementById("imagenes");
                imagenesContainer.innerHTML = '';

                // Eliminar todas las opciones del campo de selección de imágenes
                for (let i = select.options.length; i >= 0; i--) {
                    select.remove(i);
                }

                // Agregar una opción predeterminada al campo de selección
                var option = document.createElement("option");
                option.text = 'Seleccione una opción';
                option.value = '';
                select.add(option);

                // Recorrer la respuesta de la solicitud AJAX y agregar opciones al campo de selección
                for (let index = 0; index < respuesta.length; index++) {
                    var option = document.createElement("option");
                    option.text = '[' + respuesta[index]['reference'] + '] ' + respuesta[index]['nombre'] + ' (' + respuesta[index]['active'] + ')';
                    option.value = respuesta[index]['id_product_attribute'];
                    select.add(option);

                    // Crear un contenedor para cada imagen y agregarlo al contenedor de imágenes
                    var colDiv = document.createElement("div");
                    colDiv.className = "col-md-3 clickable-div";
                    colDiv.id = respuesta[index]['id_product_attribute'];

                    var imgTextContainer = document.createElement("div");
                    imgTextContainer.className = "img-text-container";

                    var img = document.createElement("img");
                    img.src = respuesta[index]['imagen'];
                    img.width = "50";
                    img.height = "50";
                    img.className = "img-responsive";
                    img.alt = "Imagen";

                    var p = document.createElement("p");
                    p.textContent = '[' + respuesta[index]['reference'] + '] ' + respuesta[index]['nombre'];

                    imgTextContainer.appendChild(img);
                    imgTextContainer.appendChild(p);
                    colDiv.appendChild(imgTextContainer);
                    imagenesContainer.appendChild(colDiv);

                    // Agregar un evento clic a cada contenedor para realizar una acción
                    colDiv.addEventListener("click", function() {
                        click_div(respuesta[index]['id_product_attribute']);
                    });
                }

                // Actualizar el contenido del elemento con ID "name" con información de la primera opción
                document.getElementById("name").innerHTML = respuesta[0]["name"] + '<br><img src="' + respuesta[0]["portada"] + '" style="width: 200px; height: 200px;">';

                // Si se especifica `selectimagen` como `true`, seleccionar la primera opción y actualizar la imagen mostrada.
                if (selectimagen) {
                    document.getElementById('imagen_value').selectedIndex = 1;
                    actualizar(document.getElementById('imagen_value').value);
                }
            },
            error: function(xhr, status, error) {
                // Si hay un error en la solicitud AJAX, mostrar un mensaje de error en la consola y una alerta en la página.
                showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: AD01.', 'danger');
            }
        });
    } else {
        // Si el campo ID - ERP está vacío, mostrar una alerta en la página.
        showAlert('Error, complete el campo ID - ERP.', 'danger');
    }
}


/**
 * Actualiza la galería de imágenes y selecciona imágenes específicas según el valor proporcionado.
 * @param {string} value - Valor para identificar las imágenes que se deben seleccionar.
 */
function actualizar(value) {
    // Verificar si el valor no está vacío
    if (value != '') {
        // Realizar una solicitud AJAX para obtener datos relacionados con el modelo y el valor dado
        $.ajax({
            url: '/modules/alsernetimagenes/datos.php',
            type: 'POST',
            data: {
                id_modelo: document.getElementById('buscar_id_erp').value,
                refencia: value
            },
            dataType: 'json',
            success: function(respuesta) {
                // La solicitud AJAX fue exitosa, se procede a actualizar la galería de imágenes.

                // Obtener el elemento que contendrá la galería de imágenes
                var imageGallery = document.getElementById('image-gallery');

                // Opcional: Eliminar imágenes existentes del contenedor
                imageGallery.innerHTML = '';

                // Recorrer la respuesta y agregar las imágenes al contenedor de la galería
                respuesta['imagen'].forEach(function(image) {
                    var imageContainer = document.createElement('div');
                    imageContainer.className = 'image-container';

                    var img = document.createElement('img');
                    img.src = image.url;
                    img.style = 'width: 200px;height: 200px;';

                    var checkboxContainer = document.createElement('div');
                    checkboxContainer.className = 'image-checkbox';

                    var checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = 'image' + image.id_image;
                    checkbox.value = image.id_image;

                    var label = document.createElement('label');
                    label.htmlFor = 'image' + image.id_image;
                    //label.innerHTML = 'image' + image.id_image;

                    checkboxContainer.appendChild(checkbox);
                    checkboxContainer.appendChild(label);

                    imageContainer.appendChild(img);
                    imageContainer.appendChild(checkboxContainer);

                    imageGallery.appendChild(imageContainer);
                });

                // Seleccionar imágenes específicas según la respuesta recibida
                respuesta['select'].forEach(function(select) {
                    seleccionarImagenPorId('image' + select.id_image);
                });
            },
            error: function(xhr, status, error) {
                // Si hay un error en la solicitud AJAX, mostrar un mensaje de error en la consola y una alerta en la página.
                showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: A01.', 'danger');
            }
        });
    }
}


/**
 * Guarda las imágenes seleccionadas y realiza acciones adicionales según el valor de `seguir`.
 * @param {boolean} seguir - Indica si se deben realizar acciones adicionales después de guardar las imágenes.
 */
function guardarImagenesSeleccionadas(seguir = false) {
    var datas = [];

    // Comprobar si el pre_guardado_imagen es igual a 0
    if (pre_guardado_imagen == 0) {
        // Si es igual a 0, asignar un objeto con IDs y referencia a datas[0]
        datas[0] = {
            "ids": buscarDatos(),
            "referencia": document.getElementById('imagen_value').value
        };
    } else {
        // Si pre_guardado_imagen no es igual a 0, asignar datos_pre_guardado a datas
        datas = datos_pre_guardado;
    }

    // Realizar una solicitud AJAX para eliminar imágenes según los datos proporcionados
    $.ajax({
        url: '/modules/alsernetimagenes/delete.php',
        type: 'POST',
        data: { data: datas },
        success: function(respuesta) {
            // Si la solicitud AJAX es exitosa, se realizan acciones adicionales según `seguir`

            if (seguir) {
                // Si `seguir` es verdadero, recargar la página
                location.reload();
            }

            // Mostrar una alerta de éxito indicando que los cambios se han guardado correctamente
            showAlert('Los cambios se han guardado correctamente.', 'success');

            // Actualizar el campo de selección de imágenes
            addTypeProductOption();

            // Restablecer las variables pre_guardado_imagen y datos_pre_guardado a sus valores iniciales
            pre_guardado_imagen = 0;
            datos_pre_guardado = [];

            // Actualizar el contenido del elemento con ID "guardar_imagen"
            document.getElementById('guardar_imagen').innerHTML = "Guardar y Quedarse (" + pre_guardado_imagen + ")";

            // Limpiar el contenedor de la galería de imágenes
            document.getElementById('image-gallery').innerHTML = '';
        },
        error: function(xhr, status, error) {
            // Si hay un error en la solicitud AJAX, mostrar un mensaje de error en la consola y una alerta en la página.
            showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: G01.', 'danger');
        }
    });
}


/**
 * Valida el estado del interruptor y realiza acciones dependiendo de su estado.
 */
function validarEstadoInterruptor() {
    // Obtener el estado actual del interruptor
    if (interruptor.checked) {
        // Si el interruptor está marcado (checked), realizar una solicitud AJAX
        // para obtener datos automáticamente.

        $.ajax({
            url: '/modules/alsernetimagenes/automatico.php',
            type: 'GET',
            dataType: 'json',
            success: function(respuesta) {
                // La solicitud AJAX fue exitosa, realizar acciones dependiendo de la respuesta.

                if (respuesta != '') {
                    // Si hay una respuesta válida, establecer el valor del campo ID - ERP con la respuesta.
                    document.getElementById('buscar_id_erp').value = respuesta;

                    // Llamar a la función addTypeProductOption con el parámetro true para
                    // seleccionar automáticamente la primera imagen y actualizar la imagen mostrada.
                    addTypeProductOption(true);

                    // Deshabilitar el campo de búsqueda de imágenes.
                    document.getElementById('buscar_imagen').disabled = true;
                } else {
                    // Si no hay respuesta válida, desmarcar el interruptor.
                    interruptor.checked = false;
                }
            },
            error: function(xhr, status, error) {
                // Si hay un error en la solicitud AJAX, mostrar un mensaje de error en la consola
                // y una alerta en la página.
                showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: VEI01.', 'danger');
            }
        });
    } else {
        // Si el interruptor no está marcado, realizar acciones para restablecer los campos y opciones.

        // Restablecer el valor del campo ID - ERP a vacío y habilitar el campo.
        document.getElementById('buscar_id_erp').value = '';
        document.getElementById('buscar_id_erp').disabled = false;

        // Limpiar el contenido del elemento con ID "name".
        document.getElementById('name').innerHTML = '';

        // Limpiar el contenedor de imágenes.
        document.getElementById('imagenes').innerHTML = '';

        // Deshabilitar el campo "aprobado".
        document.getElementById("aprobado").disabled = true;

        // Limpiar el contenedor de la galería de imágenes.
        document.getElementById('image-gallery').innerHTML = '';

        // Habilitar el campo de búsqueda de imágenes y deshabilitar el campo de selección de imágenes.
        document.getElementById('buscar_imagen').disabled = false;
        var select = document.getElementById("imagen_value");
        select.disabled = true;

        // Eliminar todas las opciones del campo de selección de imágenes.
        for (let i = select.options.length; i >= 0; i--) {
            select.remove(i);
        }

        // Agregar una opción predeterminada al campo de selección.
        var option = document.createElement("option");
        option.text = 'Seleccione una opción';
        option.value = '';
        select.add(option);
    }
}


/**
 * Realiza una solicitud AJAX para marcar el producto como aprobado y realiza acciones adicionales.
 */
function aprobado() {
    $.ajax({
        url: '/modules/alsernetimagenes/aprobado.php',
        type: 'POST',
        data: { id_modelo: document.getElementById('buscar_id_erp').value },
        dataType: 'json',
        success: function(respuesta) {
            // La solicitud AJAX fue exitosa, mostrar una alerta de éxito indicando que el producto fue aprobado.
            showAlert('El producto fue aprobado.', 'success');

            // Llamar a la función validarEstadoInterruptor para realizar acciones adicionales según el estado del interruptor.
            validarEstadoInterruptor();
        },
        error: function(xhr, status, error) {
            // Si hay un error en la solicitud AJAX, mostrar un mensaje de error en la consola y una alerta en la página.
            showAlert('Error, comunicar al Departamento de Desarrollo Web el numero de error: AP01.', 'danger');
        }
    });
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


/**
 * Permite el ingreso de solo números al campo de entrada al verificar el código de caracter (charCode) del evento.
 * @param {Event} e - El evento de entrada de teclado.
 * @returns {boolean} - Devuelve true si el caracter ingresado es un número (0-9), de lo contrario, devuelve false.
 */
function soloNumeros(e) {
    // Obtener el código de caracter (charCode) del evento de entrada de teclado
    var key = e.charCode;

    // Verificar si el código de caracter corresponde a un número (0-9)
    // Devuelve true si es un número, de lo contrario, devuelve false.
    return key >= 48 && key <= 57;
}


/**
 * Muestra una alerta en la página con el mensaje y tipo de alerta especificados.
 * La alerta se eliminará automáticamente después de 6 segundos (6000 milisegundos).
 * @param {string} message - El mensaje que se mostrará en la alerta.
 * @param {string} type - El tipo de alerta (p. ej., 'success', 'info', 'warning', 'danger') que afectará el estilo de la alerta.
 */
function showAlert(message, type) {
    // Crear un elemento div para la alerta
    var alertElement = document.createElement('div');

    // Agregar clases de estilo a la alerta, según el tipo de alerta proporcionado
    alertElement.classList.add('alert', 'alert-' + type);

    // Establecer el contenido del elemento div con el mensaje de la alerta
    alertElement.innerHTML = message;

    // Obtener el contenedor donde se mostrarán las alertas (por ejemplo, un div con el ID 'content')
    var alertContainer = document.getElementById('content');

    // Insertar la alerta como el primer elemento hijo del contenedor
    alertContainer.insertBefore(alertElement, alertContainer.firstChild);

    // Desplazarse hacia la parte superior de la página para que la alerta sea visible al usuario
    window.scrollTo(0, 0);

    // Programar la eliminación automática de la alerta después de 6 segundos (6000 milisegundos)
    setTimeout(function() {
        alertElement.remove();
    }, 6000);
}


/**
 * Alterna la selección de una imagen representada por un contenedor dado.
 * La selección se basa en el estado actual del checkbox dentro del contenedor.
 * Si el checkbox está marcado (checked), lo desmarca y elimina la clase 'selected' del contenedor.
 * Si el checkbox no está marcado, lo marca y agrega la clase 'selected' al contenedor.
 * @param {Element} container - El contenedor que representa la imagen y contiene un checkbox.
 */
function toggleImageSelection(container) {
    // Obtener el checkbox dentro del contenedor
    var checkbox = container.querySelector('input[type="checkbox"]');

    // Alternar el estado del checkbox (marcar o desmarcar)
    checkbox.checked = !checkbox.checked;

    // Alternar la clase 'selected' en el contenedor para mostrar o ocultar el estado seleccionado
    container.classList.toggle('selected');
}


/**
 * Selecciona una imagen específica por su ID y agrega la clase 'selected' para resaltar visualmente la selección.
 * @param {string} id - El ID del elemento checkbox de la imagen que se va a seleccionar.
 */
function seleccionarImagenPorId(id) {
    // Obtener el elemento checkbox de la imagen con el ID dado
    var imagen = document.getElementById(id);

    // Verificar si se encontró un elemento con el ID dado
    if (imagen) {
        // Si se encontró el elemento, verificar si el checkbox no está marcado (checked)
        if (!imagen.checked) {
            // Si el checkbox no está marcado, marcarlo y agregar la clase 'selected' al elemento para resaltar visualmente la selección.
            imagen.checked = true;
            imagen.classList.add('selected');
        }
    }
}


/**
 * Realiza un pre-guardado de datos, almacenando IDs y referencia asociados a una imagen seleccionada.
 * Agrega la información pre-guardada al arreglo 'datos_pre_guardado' y realiza acciones visuales en la página.
 * Muestra una alerta de éxito indicando que el pre-guardado fue realizado correctamente.
 */
function pre_guardado() {
    // Obtener el valor seleccionado en el campo de selección de imágenes
    var refe = document.getElementById('imagen_value').value;

    // Almacenar los IDs y referencia asociados a la imagen seleccionada en el arreglo 'datos_pre_guardado'
    datos_pre_guardado[pre_guardado_imagen] = {
        ids: buscarDatos(),
        referencia: refe
    };

    // Limpiar el valor de la variable 'pintado'
    pintado = '';

    // Obtener el elemento con el ID igual al valor seleccionado en el campo de selección de imágenes
    var rrefe = document.getElementById('' + refe + '');

    // Cambiar el color de fondo del elemento y eliminar la clase "clickable-div"
    rrefe.style.backgroundColor = 'beige';
    rrefe.classList.remove("clickable-div");

    // Incrementar el contador pre_guardado_imagen y actualizar el contenido del elemento con ID "guardar_imagen"
    pre_guardado_imagen++;
    document.getElementById('guardar_imagen').innerHTML = "Guardar y Quedarse (" + pre_guardado_imagen + ")";

    // Mostrar una alerta de éxito indicando que el pre-guardado fue realizado correctamente
    showAlert('Pre guardado correcto.', 'success');
}


/**
 * Busca y devuelve un arreglo con los valores (IDs) de las imágenes seleccionadas mediante checkboxes.
 * @returns {Array} - Arreglo que contiene los valores (IDs) de las imágenes seleccionadas.
 */
function buscarDatos() {
    // Crear un arreglo vacío para almacenar los valores (IDs) de las imágenes seleccionadas.
    var imagenesSeleccionadas = [];

    // Obtener todos los checkboxes seleccionados con la clase 'image-checkbox'
    var checkboxes = document.querySelectorAll('.image-checkbox input[type="checkbox"]:checked');

    // Recorrer los checkboxes seleccionados y agregar sus valores (IDs) al arreglo imagenesSeleccionadas.
    checkboxes.forEach(function(checkbox) {
        imagenesSeleccionadas.push(checkbox.value);
    });

    // Devolver el arreglo que contiene los valores (IDs) de las imágenes seleccionadas.
    return imagenesSeleccionadas;
}


/**
 * Resalta visualmente un elemento de imagen seleccionado y actualiza el campo de selección de imágenes.
 * @param {string} value - El valor del elemento de imagen que se ha seleccionado.
 */
function click_div(value) {
    // Obtener el elemento de imagen con el ID igual al valor dado
    var click = document.getElementById('' + value + '');

    // Verificar si el elemento de imagen no tiene un color de fondo establecido
    if (!click.style.backgroundColor) {
        // Obtener el elemento de selección de imágenes
        var selectElement = document.getElementById('imagen_value');

        // Verificar si 'pintado' está vacío
        if (pintado == '') {
            // Si está vacío, asignar el valor actual a 'pintado'
            pintado = value;
        } else {
            // Si no está vacío, restablecer el color de fondo del elemento de imagen previamente seleccionado
            document.getElementById('' + pintado + '').style.backgroundColor = '';
            // Asignar el valor actual a 'pintado'
            pintado = value;
        }

        // Cambiar el color de fondo del elemento de imagen seleccionado
        click.style.backgroundColor = 'lightgray';

        // Recorrer las opciones del elemento de selección de imágenes
        for (var i = 0; i < selectElement.options.length; i++) {
            // Verificar si la opción tiene el mismo valor que el elemento de imagen seleccionado
            if (selectElement.options[i].value === value) {
                // Si es igual, seleccionar esa opción en el elemento de selección de imágenes y detener el bucle.
                selectElement.options[i].selected = true;
                break;
            }
        }

        // Llamar a la función actualizar con el valor del elemento de imagen seleccionado
        actualizar(value);
    }
}
