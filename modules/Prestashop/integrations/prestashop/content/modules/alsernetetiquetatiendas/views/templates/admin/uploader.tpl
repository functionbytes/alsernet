<div class="panel">
  <h3><i class="icon-file-text"></i> {l s='Generación de PDF' mod='alsernetetiquetatiendas'}</h3>

  <div class="alert alert-info" style="margin-bottom:15px">
    {l s='La previsualización y el ajuste de posiciones se realizan ahora en la pantalla de Configuración del módulo.' mod='alsernetetiquetatiendas'}
  </div>

  {if !$base_image_url}
    <div class="alert alert-warning">
      {l s='Primero sube una imagen base en la configuración del módulo.' mod='alsernetetiquetatiendas'}
    </div>
  {else}
    <form id="excel-form" enctype="multipart/form-data" method="post" onsubmit="return false;">
      <div class="form-group">
        <label>{l s='Archivo Excel (XLSX/XLS) con columnas: REFERENCIA, DESCRIPCION, PVP RECOM PROV, PVP' mod='alsernetetiquetatiendas'}</label>
        <input type="file" name="excel_file" accept=".xlsx,.xls" class="form-control" required />
      </div>
      <button id="generate" class="btn btn-primary">
        <i class="icon-file-text"></i> {l s='Generar PDF' mod='alsernetetiquetatiendas'}
      </button>
      <span id="gen-status" style="margin-left:10px"></span>
    </form>
  {/if}
</div>

<script>
  window.ALSER_URLS = {ldelim}
    gen: "{$generate_url|escape:'javascript'}"
  {rdelim};
</script>
