<div class="panel panel-default">
    <div class="panel-heading">
        <i class="icon-list-ul"></i>{l s='Árbol ERP' d='modules.MiModulo.Admin'}
    </div>
    <div class="panel-body">
        <div class="col-lg-12">
            <div class="row">
                <!-- Selector -->
                <div class="col-lg-12" style="margin-bottom: 2%;">
                    <div class="row">
                        <div class="col-xs-12">
                            <p>Listado de tipo de productos</p>
                            <select id="feature_value" class="input-large" onchange="actualizar(this.value)">
                                <option value="" selected>Seleccione una opción</option>
                                {foreach from=$query.featureValue item=value key=id_feature_value}
                                    <option value="{$id_feature_value}">[{$id_feature_value}] {$value}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Primer grupo Categoria -->
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-xs-6">
                            <p>Listado de todas las categorías</p>
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_category_select_1" autocomplete="off">
                            </div>
                            <select id="category_select_1" class="input-large" multiple disabled>
                            </select>
                            <a id="category_select_add" class="btn btn-default btn-block clearfix" >Añadir <i class="icon-arrow-right"></i></a>
                        </div>
                        <div class="col-xs-6">
                            <p>Listado de las categoría seleccionadas</p>
                            <input type="hidden" name="condition_category_select_json[]" id="condition_category_select_json" />
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_category_select_2" autocomplete="off">
                            </div>
                            <select name="category_select[]" id="category_select_2" class="input-large" multiple disabled>
                            </select>
                            <a id="category_select_remove" class="btn btn-default btn-block clearfix" ><i class="icon-arrow-left"></i> Eliminar</a>
                        </div>
                    </div>
                    <hr/>
                    <!-- Segundo grupo Familia -->
                    <div class="row">
                        <div class="col-xs-6">
                            <p>Listado de todas las familias</p>
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_family_select_1" autocomplete="off">
                            </div>
                            <select id="family_select_1" class="input-large" multiple disabled>
                            </select>
                            <a id="family_select_add" class="btn btn-default btn-block clearfix" >Añadir <i class="icon-arrow-right"></i></a>
                        </div>
                        <div class="col-xs-6">
                            <p>Listado de las familias seleccionadas</p>
                            <input type="hidden" name="condition_family_select_json[]" id="condition_family_select_json" />
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_family_select_2" autocomplete="off">
                            </div>
                            <select name="family_select[]" id="family_select_2" class="input-large" multiple disabled>
                            </select>
                            <a id="family_select_remove" class="btn btn-default btn-block clearfix" ><i class="icon-arrow-left"></i> Eliminar</a>
                        </div>
                    </div>
                    <hr/>
                    <!-- Tercer grupo SubFamilia -->
                    <div class="row">
                        <div class="col-xs-6">
                            <p>Listado de todas las subfamilias</p>
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_subfamily_select_1" autocomplete="off">
                            </div>
                            <select id="subfamily_select_1" class="input-large" multiple disabled>
                            </select>
                            <a id="subfamily_select_add" class="btn btn-default btn-block clearfix" >Añadir <i class="icon-arrow-right"></i></a>
                        </div>
                        <div class="col-xs-6">
                            <p>Listado de las subfamilias seleccionadas</p>
                            <input type="hidden" name="condition_subfamily_select_json[]" id="condition_subfamily_select_json" />
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_subfamily_select_2" autocomplete="off">
                            </div>
                            <select name="subfamily_select[]" id="subfamily_select_2" class="input-large" multiple disabled>
                            </select>
                            <a id="subfamily_select_remove" class="btn btn-default btn-block clearfix" ><i class="icon-arrow-left"></i> Eliminar</a>
                        </div>
                    </div>
                    <hr/>
                    <!-- Cuarto grupo Grupo -->
                    <div class="row">
                        <div class="col-xs-6">
                            <p>Listado de todos los grupos</p>
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_groups_select_1" autocomplete="off">
                            </div>
                            <select id="groups_select_1" class="input-large" multiple disabled>
                            </select>
                            <a id="groups_select_add" class="btn btn-default btn-block clearfix" >Añadir <i class="icon-arrow-right"></i></a>
                        </div>
                        <div class="col-xs-6">
                            <p>Listado de los grupos seleccionados</p>
                            <input type="hidden" name="condition_groups_select_json[]" id="condition_groups_select_json" />
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_groups_select_2" autocomplete="off">
                            </div>
                            <select name="groups_select[]" id="groups_select_2" class="input-large" multiple disabled>
                            </select>
                            <a id="groups_select_remove" class="btn btn-default btn-block clearfix" ><i class="icon-arrow-left"></i> Eliminar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <div class="col-xs-12">
            <div class="seguimiento-pedido-btn">
               <div class="seguimiento-pedido-form seguimiento-pedido-btn-form pull-right">
                    <button id="guardar_type_product_cfsg" class="btn btn-primary">Auto-Actualizar y Guardar</button>
               </div>
           </div>
        </div>
    </div>
    <hr />
    <div class="container">
        <div class="panel-group" id="accordion">
            <div class="panel panel-danger">
                <div class="panel-heading" style="background-color: aliceblue !important;">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#listoCollapse">
                            Listado de Auto-Asignados
                        </a>
                    </h4>
                </div>
                <div id="listoCollapse" class="panel-collapse collapse in">
                    <div class="panel-body">
                        <div id="listoContainer"></div>
                    </div>
                </div>
            </div>
            <!-- <div class="panel panel-success">
                <div class="panel-heading" style="background-color: aliceblue !important;">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#listoCollapse">
                            Los que ya tienen el "Tipo de producto" asignado:
                        </a>
                    </h4>
                </div>
                <div id="listoCollapse" class="panel-collapse collapse">
                    <div class="panel-body">
                        <div id="listoContainer"></div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</div>
{literal}
	<script type="text/javascript" style="display: none">
        // Categoria
        $('#category_select_add').click(function() { addQuantityDiscountOption(this); });
        $('#category_select_remove').click(function() { removeQuantityDiscountOption(this); });

        // Familia
        $('#family_select_add').click(function() { addQuantityDiscountOption(this); });
        $('#family_select_remove').click(function() { removeQuantityDiscountOption(this); });

        // SubFamilia
        $('#subfamily_select_add').click(function() { addQuantityDiscountOption(this); });
        $('#subfamily_select_remove').click(function() { removeQuantityDiscountOption(this); });

        // Grupos
        $('#groups_select_add').click(function() { addQuantityDiscountOption(this); });
        $('#groups_select_remove').click(function() { removeQuantityDiscountOption(this); });

        // Boton de guardar
        $('#guardar_type_product_cfsg').click(function() { addTypeProductOption(); });
	</script>
{/literal}
