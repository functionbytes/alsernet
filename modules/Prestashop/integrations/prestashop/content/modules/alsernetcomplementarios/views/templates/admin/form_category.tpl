{*
Formulario: Complementarios por CATEGORÍA
*}
<div class="panel">
    <div class="panel-heading">
        {l s='Complementarios por Categoría' mod='alsernetcomplementarios'}
    </div>

    <div id="alc-errors" class="alert alert-danger" style="display:none;"></div>

    <div class="alert alert-info">
        {l s='Selecciona una o más categorías (solo se muestran los árboles permitidos). Luego ingresa las REFERENCIAS
        de los productos que serán complementarios.' mod='alsernetcomplementarios'}
    </div>

    <form id="alc-form-category" class="form-horizontal">
        <input type="hidden" name="alc_type" value="category" />
        <input type="hidden" id="alc-ajax-base" value="{$alc_ajax_base|escape:'html':'UTF-8'}" />
        <input type="hidden" id="alc-list-url" value="{$alc_list_url|escape:'html':'UTF-8'}" />

        {if isset($alc_edit_id)}
        <input type="hidden" id="alc-edit-id" name="alc_edit_id" value="{$alc_edit_id|intval}">
        {/if}
        {if isset($alc_list_url)}
        <input type="hidden" id="alc-list-url" value="{$alc_list_url|escape:'html':'UTF-8'}">
        {/if}

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Título' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <input type="text" name="alc_title" class="form-control"
                    value="{if isset($alc_title)}{$alc_title|escape:'html':'UTF-8'}{/if}"
                    placeholder="{l s='Ej: Complementos para Calzado' mod='alsernetcomplementarios'}">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Categorías (raíces 3..11 y sus hijos)' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                {$alc_category_tree nofilter}
                <p class="help-block">
                    {l s='Marca todas las categorías donde quieras que aparezcan estos complementarios.'
                    mod='alsernetcomplementarios'}
                </p>
            </div>
        </div>

        {* NUEVO: Selección de marcas *}
        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Filtrar por Marca(s) — opcional' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <select name="alc_brands[]" id="alc-brands" class="form-control" multiple="multiple" size="8">
                    {if isset($alc_manufacturers) && $alc_manufacturers|@count > 0}
                    {foreach from=$alc_manufacturers item=man}
                    {assign var=isSelected value=false}
                    {if isset($alc_selected_brands)}
                    {foreach from=$alc_selected_brands item=bid}
                    {if $bid == $man.id_manufacturer}{assign var=isSelected value=true}{/if}
                    {/foreach}
                    {/if}
                    <option value="{$man.id_manufacturer|intval}" {if $isSelected}selected="selected" {/if}>
                        {$man.name|escape:'html':'UTF-8'} (#{$man.id_manufacturer|intval})
                    </option>
                    {/foreach}
                    {else}
                    <option value="">{l s='No hay marcas disponibles' mod='alsernetcomplementarios'}</option>
                    {/if}
                </select>
                <p class="help-block">
                    {l s='Si seleccionas marcas, solo se tomarán productos de esas marcas dentro de las categorías
                    marcadas.' mod='alsernetcomplementarios'}
                </p>
            </div>
        </div>


        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Productos complementarios (Referencia)' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <textarea name="alc_complements" class="form-control" rows="3"
                    placeholder="REF-001, REF-ABC, PACK-33">{if isset($alc_complements_text)}{$alc_complements_text|escape:'html':'UTF-8'}{/if}</textarea>
            </div>
        </div>

        {*
        Si quieres exclusiones en Categoría, descomenta:
        *}
        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Excluir productos (Referencia) — opcional' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <textarea name="alc_excluded" class="form-control" rows="2"
                    placeholder="REF-XXX, REF-YYY">{if isset($alc_excluded_text)}{$alc_excluded_text|escape:'html':'UTF-8'}{/if}</textarea>
                <span class="help-block">{l s='Estos productos no mostrarán complementarios aunque pertenezcan a las
                    categorías seleccionadas.' mod='alsernetcomplementarios'}</span>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Orden' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <input type="number" name="alc_position" class="form-control"
                    value="{if isset($alc_position)}{$alc_position|intval}{else}0{/if}">
                <p class="help-block">
                    {l s='Número para ordenar este grupo de complementarios.' mod='alsernetcomplementarios'}
                </p>
            </div>
        </div>


        <div class="form-group">
            <div class="col-lg-9 col-lg-offset-3">
                <button type="button" class="btn btn-default" id="alc-btn-preview-category">
                    <i class="icon-search"></i> {l s='Previsualizar' mod='alsernetcomplementarios'}
                </button>
                <button type="button" class="btn btn-primary" id="alc-btn-save-category">
                    <i class="icon-save"></i> {l s='Guardar' mod='alsernetcomplementarios'}
                </button>
                <a class="btn btn-default pull-right"
                    href="{$currentIndex|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}">
                    {l s='Volver al listado' mod='alsernetcomplementarios'}
                </a>
            </div>
        </div>
    </form>

    <div id="alc-conflicts" class="alert alert-warning" style="display:none;"></div>

    <div id="alc-preview-category" class="row" style="display:none;">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">{l s='Origen' mod='alsernetcomplementarios'}</div>
                <div class="table-responsive">
                    <table class="table" id="alc-table-complements"></table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">{l s='Complementarios' mod='alsernetcomplementarios'}</div>
                <div class="table-responsive">
                    <table class="table" id="alc-table-sources-cat"></table>
                </div>
            </div>
        </div>
    </div>
</div>