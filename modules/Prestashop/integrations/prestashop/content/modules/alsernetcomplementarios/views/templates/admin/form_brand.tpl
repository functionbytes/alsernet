<div class="panel">
    <div class="panel-heading">
        {l s='Complementarios por Marca' mod='alsernetcomplementarios'}
    </div>

    {*
    URL del listado para redirigir al guardar
    *}

    <form id="alc-form-brand" class="form-horizontal">
        <input type="hidden" name="alc_type" value="brand" />
        <input type="hidden" id="alc-ajax-base" value="{$alc_ajax_base|escape:'html':'UTF-8'}" />
        <input type="hidden" id="alc-list-url" value="{$alc_list_url|escape:'html':'UTF-8'}" />

        {if isset($alc_edit_id)}
        <input type="hidden" id="alc-edit-id" name="alc_edit_id" value="{$alc_edit_id|intval}">
        {/if}

        <div id="alc-errors" class="alert alert-danger" style="display:none;"></div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Título' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <input type="text" name="alc_title" class="form-control"
                    value="{if isset($alc_title)}{$alc_title|escape:'html':'UTF-8'}{/if}"
                    placeholder="{l s='Ej: Complementos marca X' mod='alsernetcomplementarios'}">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Marcas' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <select name="alc_brands[]" class="form-control" multiple size="10">
                    {foreach from=$alc_manufacturers item=m}
                    {assign var=isSel value=false}
                    {if isset($alc_selected_brands) && in_array($m.id_manufacturer, $alc_selected_brands)}{assign
                    var=isSel value=true}{/if}
                    <option value="{$m.id_manufacturer}" {if $isSel} selected{/if}>
                        {$m.name} (ID: {$m.id_manufacturer})
                    </option>
                    {/foreach}
                </select>
                <p class="help-block">
                    {l s='Puedes seleccionar una o varias marcas.' mod='alsernetcomplementarios'}
                </p>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Productos complementarios (Referencia)' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <textarea name="alc_complements" class="form-control" rows="3" placeholder="REF-999, REF-123">
{if isset($alc_complements_text)}{$alc_complements_text|escape:'html':'UTF-8'}{/if}
</textarea>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Excluir productos (Referencia) — opcional' mod='alsernetcomplementarios'}
            </label>
            <div class="col-lg-9">
                <textarea name="alc_excluded" class="form-control" rows="2" placeholder="REF-XXX, REF-YYY">
{if isset($alc_excluded_text)}{$alc_excluded_text|escape:'html':'UTF-8'}{/if}
</textarea>
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
                <button type="button" class="btn btn-default" id="alc-btn-preview-brand">
                    <i class="icon-search"></i> {l s='Previsualizar' mod='alsernetcomplementarios'}
                </button>
                <button type="button" class="btn btn-primary" id="alc-btn-save-brand">
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

    {*
    Previsualización a dos columnas: ORIGEN (productos de la/s marca/s)
    y COMPLEMENTARIOS (refs ingresadas)
    *}
    <div id="alc-preview-brand" class="row" style="display:none;">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">{l s='Origen' mod='alsernetcomplementarios'}</div>
                <div class="table-responsive">
                    <table class="table" id="alc-table-sources-brand"></table>
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