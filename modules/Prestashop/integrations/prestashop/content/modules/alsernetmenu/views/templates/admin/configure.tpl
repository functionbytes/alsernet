<form  class="defaultForm form-horizontal" action="{$form_action}" method="post" enctype="multipart/form-data" novalidate="">
    <input type="hidden" name="id_countrys" class="none" value="{$id}">
    <div class="panel" id="fieldset_0">
        <div class="panel-heading">
            <i class="icon-cogs"></i> Ajustes
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-4">
                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="Country name">
                        Country name
                    </span>
                </label>
                <div class="col-lg-8">
                    <input type="text" name="name" id="name" value="{$name}" class="" disabled="disabled">
                </div>
            </div>
            <div class="form-group hide">
                <input type="hidden" name="id_country" id="id_country" value="6">
            </div>
            <div class="form-group">
                <label class="control-label col-lg-4">Fullwidth Homepage</label>
                <div class="col-lg-8">
                    <div class="checkbox">
                        {foreach from=$payments item=payment}
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="payments[]" value="{$payment.id}" {if $payment.checked==1}checked="checked"{/if}>
                                    {$payment.name}
                                </label>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <button type="submit" id="submitPayments" name="submitPayments" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> Guardar
                </button>
                <button class="btn btn-default" type="button" onclick="javascript:window.history.back();">
                    Atras
                </button>
            </div>
        </div>
    </div>
</form>