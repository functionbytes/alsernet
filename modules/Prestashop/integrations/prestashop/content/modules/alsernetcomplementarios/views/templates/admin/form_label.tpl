{* Etiqueta (label) *}
<div class="panel">
    <div class="panel-heading">
        {l s='Complementarios por Etiqueta' mod='alsernetcomplementarios'}
    </div>

    <form id="alc-form-label" class="form-horizontal">
        <input type="hidden" name="alc_type" value="label" />
        {if isset($alc_edit_id)}<input type="hidden" id="alc-edit-id" value="{$alc_edit_id|intval}">{/if}
        <input type="hidden" id="alc-ajax-base" value="{$alc_ajax_base|escape:'html':'UTF-8'}" />
        <input type="hidden" id="alc-list-url" value="{$alc_list_url|escape:'html':'UTF-8'}" />

        <div id="alc-errors" class="alert alert-danger" style="display:none;"></div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Título' mod='alsernetcomplementarios'}</label>
            <div class="col-lg-9">
                <input type="text" name="alc_title" class="form-control" value="{$alc_title|escape:'html':'UTF-8'}"
                    placeholder="{l s='Ej: Pack verano BBs' mod='alsernetcomplementarios'}">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Etiquetas origen' mod='alsernetcomplementarios'}</label>
            <div class="col-lg-9">
                <textarea name="alc_labels" class="form-control" rows="2"
                    placeholder="BOLAS BB, OTRO TEXTO...">{$alc_labels_text|escape:'html':'UTF-8'}</textarea>
                <p class="help-block">{l s='Ingresa una o varias etiquetas separadas por coma/espacio. Se buscarán en
                    combinaciones y productos “únicos”.' mod='alsernetcomplementarios'}</p>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Productos complementarios (Referencia)'
                mod='alsernetcomplementarios'}</label>
            <div class="col-lg-9">
                <textarea name="alc_complements" class="form-control" rows="3"
                    placeholder="CAN203, 320200">{$alc_complements_text|escape:'html':'UTF-8'}</textarea>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Excluir productos (Referencia) — opcional'
                mod='alsernetcomplementarios'}</label>
            <div class="col-lg-9">
                <textarea name="alc_excluded" class="form-control" rows="2"
                    placeholder="REF-XXX, REF-YYY">{$alc_excluded_text|escape:'html':'UTF-8'}</textarea>
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
                <button type="button" class="btn btn-default" id="alc-btn-preview-label">
                    <i class="icon-search"></i> {l s='Previsualizar' mod='alsernetcomplementarios'}
                </button>
                <button type="button" class="btn btn-primary" id="alc-btn-save-label">
                    <i class="icon-save"></i> {l s='Guardar' mod='alsernetcomplementarios'}
                </button>
                <a class="btn btn-default pull-right" href="{$alc_list_url|escape:'html':'UTF-8'}">
                    {l s='Volver al listado' mod='alsernetcomplementarios'}
                </a>
            </div>
        </div>
    </form>

    <div id="alc-conflicts" class="alert alert-warning" style="display:none;"></div>

    <div id="alc-preview-label" class="row" style="display:none;">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">{l s='Origen (etiquetas → productos)' mod='alsernetcomplementarios'}</div>
                <div class="table-responsive">
                    <table class="table" id="alc-table-sources-cat"></table>
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