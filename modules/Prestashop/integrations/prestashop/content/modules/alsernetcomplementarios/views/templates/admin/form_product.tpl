{*
Formulario: Complementarios por PRODUCTO
*}
<div class="panel">
    <div class="panel-heading">
        {l s='Complementarios por Producto' mod='alsernetcomplementarios'}
    </div>

    <div class="alert alert-info">
        {l s='Ingresa una lista separada por comas de REFERENCIAS. Ej: ABC-REF, Z999, PACK-33'
        mod='alsernetcomplementarios'}
    </div>

    <div id="alc-errors" class="alert alert-danger" style="display:none;"></div>

    <form id="alc-form-product" class="form-horizontal">
        <input type="hidden" name="alc_type" value="product" />
        <input type="hidden" id="alc-ajax-base" value="{$alc_ajax_base|escape:'html':'UTF-8'}" />
        <input type="hidden" id="alc-list-url" value="{$alc_list_url|escape:'html':'UTF-8'}" />

        {if isset($alc_edit_id)}
        <input type="hidden" id="alc-edit-id" name="alc_edit_id" value="{$alc_edit_id|intval}">
        {/if}

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Título' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <input type="text" name="alc_title" class="form-control"
                    value="{if isset($alc_title)}{$alc_title|escape:'html':'UTF-8'}{/if}"
                    placeholder="{l s='Ej: Pack running verano' mod='alsernetcomplementarios'}">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Productos origen (Referencia)' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <textarea name="alc_sources" class="form-control" rows="2"
                    placeholder="12, REF-123, 88">{if isset($alc_sources_text)}{$alc_sources_text|escape:'html':'UTF-8'}{/if}</textarea>
                <p class="help-block">
                    {l s='Estos productos mostrarán los complementarios.' mod='alsernetcomplementarios'}
                </p>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Productos complementarios (Referencia)' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <textarea name="alc_complements" class="form-control" rows="3"
                    placeholder="21, REF-999, 34">{if isset($alc_complements_text)}{$alc_complements_text|escape:'html':'UTF-8'}{/if}</textarea>
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
                <button type="button" class="btn btn-default" id="alc-btn-preview-product">
                    <i class="icon-search"></i> {l s='Previsualizar' mod='alsernetcomplementarios'}
                </button>
                <button type="button" class="btn btn-primary" id="alc-btn-save-product">
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

    <div id="alc-preview-product" class="row" style="display:none;">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">{l s='Origen' mod='alsernetcomplementarios'}</div>
                <div class="table-responsive">
                    <table class="table" id="alc-table-sources"></table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">{l s='Complementarios' mod='alsernetcomplementarios'}</div>
                <div class="table-responsive">
                    <table class="table" id="alc-table-complements"></table>
                </div>
            </div>
        </div>
    </div>
</div>