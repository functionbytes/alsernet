{* NO extender layout; ModuleAdminController ya envuelve el layout *}

<div class="panel">
  <h3><i class="icon-random"></i> {l s='Productos alternativos' mod='alsernetrelacionados'}</h3>

  {** Barra superior: Referencia + Idioma + Botón **}
  <div class="row">
    <div class="col-lg-8">
      <div class="form-inline">
        <div class="form-group" style="margin-right:12px;">
          <label style="margin-right:6px;">{l s='Referencia' mod='alsernetrelacionados'}</label>
          <input type="text" class="form-control" id="alser_ref" placeholder="ABC-123" />
        </div>

        <div class="form-group" style="margin-right:12px;">
          <label style="margin-right:6px;">{l s='Idioma' mod='alsernetrelacionados'}</label>
          <select id="filter_lang" class="form-control">
            {foreach from=$languages item=lg}
              <option value="{$lg.id_lang|intval}" {if $lg.id_lang==$id_lang}selected{/if}>{$lg.name|escape:'html'}</option>
            {/foreach}
          </select>
        </div>

        <button id="btn_load_filters" class="btn btn-primary">
          {l s='Cargar filtros' mod='alsernetrelacionados'}
        </button>
        <span id="load_msg" class="help-block" style="margin-left:10px;"></span>
      </div>
    </div>
  </div>

  <hr/>

  {** Doble columna: izquierda info base / derecha filtros **}
  <div id="base_and_filters" class="row" style="display:none;">
    {** Izquierda: producto base **}
    <div class="col-md-6">
      <div class="card" style="padding:15px;">
        <div class="text-center" style="margin-bottom:10px;">
          <img id="base_img" src="" alt="Imagen producto" style="max-height:160px; max-width:100%;"/>
        </div>
        <h4 id="base_name" style="margin-top:0;"></h4>
        <div>
          <strong>{l s='Precio' mod='alsernetrelacionados'}:</strong>
          <span id="base_price">-</span>
        </div>
        {** Características bajo el precio **}
        <div id="base_attrs_wrap" style="margin-top:8px; display:none;">
          <strong>{l s='Características' mod='alsernetrelacionados'}:</strong>
          <ul id="base_attrs" class="list-unstyled" style="margin:6px 0 0 0;"></ul>
        </div>
      </div>
    </div>

    {** Derecha: filtros **}
    <div class="col-md-6">
      <form id="filters_form" class="well" style="display:none;">
        <input type="hidden" id="exclude_id" />
        <input type="hidden" id="id_product_attribute" />

        <div class="row">
          <div class="col-md-12">
            <label>{l s='Nombre contiene' mod='alsernetrelacionados'}</label>
            <input type="text" class="form-control" id="name_like"/>
          </div>
        </div>

        <div class="row" style="margin-top:10px;">
          <div class="col-md-6">
            <label>{l s='Marcas' mod='alsernetrelacionados'}</label>
            <select id="brand_list" class="form-control"></select>
            <p class="help-block">{l s='Selecciona una o varias marcas' mod='alsernetrelacionados'}</p>
          </div>
          <div class="col-md-6">
            <label>{l s='Categoría' mod='alsernetrelacionados'}</label>
            <input type="text" class="form-control" id="category_name" readonly/>
            <input type="hidden" id="id_category"/>
          </div>
        </div>

        <div class="row" style="margin-top:10px;">
          <div class="col-md-6">
            <label>{l s='Precio Desde' mod='alsernetrelacionados'}</label>
            <input type="number" step="0.01" class="form-control" id="price_from"/>
          </div>
          <div class="col-md-6">
            <label>{l s='Precio Hasta' mod='alsernetrelacionados'}</label>
            <input type="number" step="0.01" class="form-control" id="price_to"/>
          </div>
        </div>

        <div class="row" style="margin-top:10px;">
          <div class="col-md-12">
            <button id="btn_search" class="btn btn-success">
              {l s='Buscar relacionados' mod='alsernetrelacionados'}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {** Resultados **}
  <div id="results_wrap" style="display:none; margin-top:15px; position:relative;">
    <div id="results_loader" style="display:none; position:absolute; inset:0; background:rgba(255,255,255,0.75); z-index:9999;">
      <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center;">
        <div class="al-spinner" style="width:32px;height:32px;border:3px solid #ccc;border-top-color:#555;border-radius:50%;margin:0 auto;animation:alspin 0.8s linear infinite;"></div>
        <div class="mt-2">Procesando…</div>
      </div>
    </div>

    <h4>{l s='Resultados' mod='alsernetrelacionados'}: <span id="total_found">0</span></h4>

    <div id="results_grid" class="container-fluid px-0"></div>

    <div class="clearfix" style="margin-top:10px;">
      <button class="btn btn-default" id="prev_page">&laquo;</button>
      <span id="page_info" style="margin:0 10px;">1</span>
      <button class="btn btn-default" id="next_page">&raquo;</button>
    </div>
  </div>

</div>

<style>
@keyframes alspin { to { transform: rotate(360deg); } }
</style>

<script>
  var ALSERNET = {
    ajax_url: '{$ajax_url|escape:'javascript'}&ajax=1',
    token: '{$token|escape:'javascript'}',
    id_lang: {$id_lang|intval},
    id_shop: {$id_shop|intval}
  };
</script>
