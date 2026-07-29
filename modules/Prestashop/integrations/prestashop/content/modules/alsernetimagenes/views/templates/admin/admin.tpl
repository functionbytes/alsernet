<style>
    .image-container {
        display: inline-block;
        position: relative;
        margin: 10px;
    }

    .image-checkbox {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        pointer-events: none;
    }

    .image-checkbox input[type="checkbox"] {
        display: none;
    }

    .image-checkbox input[type="checkbox"]:checked + label {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        border: 2px solid blue;
    }
    .img-text-container {
        display: flex;
        align-items: center;
    }

    .img-text-container img {
        margin-right: 10px;
    }

    /* Estilos personalizados para el interruptor */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }

    .toggle-switch input[type="checkbox"] {
        display: none;
    }

    .toggle-switch-label {
        position: absolute;
        top: 0;
        left: 0;
        width: 50px;
        height: 24px;
        background-color: #ccc;
        border-radius: 12px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .toggle-switch-label:before {
        content: "";
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background-color: #fff;
        border-radius: 50%;
        transition: left 0.3s ease;
    }

    .toggle-switch input[type="checkbox"]:checked + .toggle-switch-label {
        background-color: #5cb85c; /* Cambia el color de fondo cuando está activado */
    }

    .toggle-switch input[type="checkbox"]:checked + .toggle-switch-label:before {
        left: 28px; /* Mueve el botón hacia la derecha cuando está activado */
    }
    .custom-right-align {
        text-align: right;
    }

    .clickable-div {
        cursor: pointer;
    }

    .clickable-div:hover {
        background-color: lightgray;
    }

    .clickable-div:active {
        background-color: gray;
    }

</style>
<div class="panel panel-default">
    <div class="panel-heading">
        <i class="icon-list-ul"></i>{l s='Alsernet - Gestor de imagenes' d='modules.MiModulo.Admin'}
    </div>
    <div class="panel-body">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-xs-2">
                    <p>Automatico</p>
                    <label class="toggle-switch">
                        <input type="checkbox" id="flexSwitchCheckChecked" checked>
                        <span class="toggle-switch-label"></span>
                    </label>
                </div>
                <div class="col-xs-7">
                    <p>ID - ERP</p>
                    <input type="text" class="search_select" id="buscar_id_erp"><br>
                </div>
                <div class="col-xs-3 pull-right custom-right-align" style="top: 28px;">
                    <button id="buscar_imagen" class="btn btn-success">Buscar</button>
                    <button class="btn btn-primary" onclick="location.reload();">Reiniciar</button>
                    <button class="btn btn-danger" id="aprobado" onclick="aprobado();" disabled>Aprobado</button>
                </div>

                <div class="col-lg-12" style="margin-bottom: 2%;">
                    <h1 class="text-center" id="name"></h1>
                    <div id="imagenes"></div>
                </div>

                <div class="col-lg-12" style="margin-bottom: 2%;" hidden>
                    <p>Seleccionar referencia</p>
                    <select id="imagen_value" class="input-large" onchange="actualizar(this.value)" disabled>
                        <option value="" selected>Seleccione una opción</option>
                    </select>
                </div>

                <div class="col-lg-12" >
                    <div id="image-gallery"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <div class="col-xs-12">
            <div class="seguimiento-pedido-btn">
                <div class="seguimiento-pedido-form seguimiento-pedido-btn-form pull-right">
                    <a id="guardar_imagen_seguir" class="btn btn-primary">Guardar y Seguir</a>
                </div>
             </div>
            <div class="seguimiento-pedido-btn">
               <div class="seguimiento-pedido-form seguimiento-pedido-btn-form pull-right" style="margin-right: 1%;">
                   <a id="guardar_imagen" class="btn btn-primary">Guardar y Quedarse (0)</a>
               </div>
            </div>
            <div class="seguimiento-pedido-btn">
                <div class="seguimiento-pedido-form seguimiento-pedido-btn-form pull-right" style="margin-right: 1%;">
                    <a id="pre_guardar_imagen" class="btn btn-success">Pre Guardado</a>
                </div>
            </div>
        </div>
    </div>
</div>
{literal}
	<script type="text/javascript" style="display: none">
        // Boton de buscar
        $('#buscar_imagen').click(function() { addTypeProductOption(); });

        $('#pre_guardar_imagen').click(function() { pre_guardado(); });

        $('#guardar_imagen').click(function() { guardarImagenesSeleccionadas(); });

        $('#guardar_imagen_seguir').click(function() { guardarImagenesSeleccionadas(true); });

        document.getElementById('buscar_id_erp').addEventListener('keypress', function (e){
            if (!soloNumeros(event)){
                e.preventDefault();
            }
        })
	</script>
{/literal}
