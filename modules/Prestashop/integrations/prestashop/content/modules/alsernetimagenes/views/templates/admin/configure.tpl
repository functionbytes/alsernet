<div class="panel panel-default">
    <div class="panel-heading">
        <i class="icon-list-ul"></i>{l s='aaa' d='modules.MiModulo.Admin'}
    </div>
    <div class="panel-body">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-12" style="display: flex; justify-content: center;">
                    <div class="row center-row">
                        <div class="col-xs-6">
                            <select name="mi_select" class="select2 fixed-width-xl">
                                <option value="">Seleccione una opción</option>
                                {foreach from=$data item=name key=id}
                                    <option value="{$id}">[{$id}] {$name}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-xs-6">
                            <select name="mi_select" class="select2 fixed-width-xl">
                                <option value="">Seleccione una opción</option>
                                {foreach from=$data item=name key=id}
                                    <option value="{$id}">[{$id}] {$name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .center-row {
      display: flex;
      justify-content: center;
    }
  </style>


  <!-- <pre>
    {$data.campo1|var_dump}
  </pre> -->
