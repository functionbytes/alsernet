<div class="panel panel-default">
    <div class="panel-heading">
        <i class="icon-list-ul"></i>{l s='Excluir productos de InPost' d='modules.MiModulo.Admin'}
    </div>
    <div class="panel-body">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="col-xs-6">
                            <p>Listado de Tipo de Productos</p>
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_product_type_select_1" autocomplete="off">
                            </div>
                            <select id="product_type_select_1" class="input-large" multiple>
                                {foreach from=$query.productTypeSelected item=value key=id_feature_value}
                                    <option value="{$id_feature_value}">[{$id_feature_value}] {$value}</option>
                                {/foreach}
                            </select>
                            <a id="product_type_select_add" class="btn btn-default btn-block clearfix" >Excluir <i class="icon-arrow-right"></i></a>
                        </div>
                        <div class="col-xs-6">
                            <p>Listado de Tipo de Productos que se Excluyen de InPost</p>
                            <input type="hidden" name="condition_product_type_select_json[]" id="condition_product_type_select_json" />
                            <div class="input-group">
                                <span class="input-group-addon">Buscar</span>
                                <input type="text" class="search_select" id="search_product_type_select_2" autocomplete="off">
                            </div>
                            <select name="product_type_select[]" id="product_type_select_2" class="input-large" multiple>
                                {foreach from=$query.productTypeUnselected item=value key=id_feature_value}
                                    <option value="{$id_feature_value}">[{$id_feature_value}] {$value}</option>
                                {/foreach}
                            </select>
                            <a id="product_type_select_remove" class="btn btn-default btn-block clearfix" ><i class="icon-arrow-left"></i> Eliminar</a>
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
                   <a id="guardar_type_product" class="btn btn-primary">Guardar</a>
               </div>
           </div>
        </div>
    </div>
</div>
{literal}
	<script type="text/javascript" style="display: none">
        // Buscador
        $('#product_type_select_1').filterByText('#search_product_type_select_1', true);
        $('#product_type_select_2').filterByText('#search_product_type_select_2', true);
        // Fin del Buscador

        // Accion de mover de Izquierda a Derecha en Select
	    $('#product_type_select_add').click(function() { addQuantityDiscountOption(this); });

        // Accion de mover de Derecha a Izquierda en Select
        $('#product_type_select_remove').click(function() { removeQuantityDiscountOption(this); });

        // Boton de guardar
        $('#guardar_type_product').click(function() { addTypeProductOption(); });
	</script>
{/literal}
