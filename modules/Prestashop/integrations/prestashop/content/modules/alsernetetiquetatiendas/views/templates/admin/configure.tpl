{* Usa Bootstrap del BO *}
<div class="panel">
  <h3><i class="icon-cogs"></i> {l s='Configuración de Etiquetas' mod='alsernetetiquetatiendas'}</h3>

  <form method="post" enctype="multipart/form-data">

    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <label>{l s='Imagen base VERTICAL (A4 – 4 etiquetas)' mod='alsernetetiquetatiendas'}</label>
          <input type="file" name="ALSERNET_BASE_IMAGE" accept="image/*" class="form-control" />
          {if $settings.base_image}
            <p class="help-block">
              {l s='Actual:' mod='alsernetetiquetatiendas'} <code>{$settings.base_image|escape:'htmlall'}</code>
            </p>
            <img src="{$module_dir}uploads/{$settings.base_image|escape:'url'}"
                 style="max-width:25%;height:auto;border:1px solid #ddd" />
          {/if}
        </div>
      </div>

      <div class="col-sm-6">
        <div class="form-group">
          <label>{l s='Imagen base HORIZONTAL (A4 apaisado – 8 etiquetas)' mod='alsernetetiquetatiendas'}</label>
          <input type="file" name="ALSERNET_BASE_IMAGE_H" accept="image/*" class="form-control" />
          {if $settings.base_image_h}
            <p class="help-block">
              {l s='Actual:' mod='alsernetetiquetatiendas'} <code>{$settings.base_image_h|escape:'htmlall'}</code>
            </p>
            <img src="{$module_dir}uploads/{$settings.base_image_h|escape:'url'}"
                 style="max-width:25%;height:auto;border:1px solid #ddd" />
          {/if}
        </div>
      </div>
    </div>

    <hr/>

    <h4 class="m-t">{l s='Fuentes por campo – VERTICAL' mod='alsernetetiquetatiendas'}</h4>
    <div class="row">
      <div class="col-sm-4">
        <label>REFERENCIA – Fuente</label>
        <select name="font_referencia_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_referencia_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_referencia_size"
               value="{$settings.font_referencia_size|intval}" min="6" max="72" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Color</label>
        <input type="color" name="color_referencia"
               value="{$settings.color_referencia|escape:'htmlall'}" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Ancho (px)</label>
        <input type="number" name="box_referencia_w"
               value="{$settings.box_referencia_w|default:100|intval}" min="10" max="2000" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Alto (px)</label>
        <input type="number" name="box_referencia_h"
               value="{$settings.box_referencia_h|default:20|intval}" min="10" max="2000" class="form-control" />
      </div>
    </div>

    <div class="row m-t-10">
      <div class="col-sm-4">
        <label>DESCRIPCIÓN – Fuente</label>
        <select name="font_descripcion_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_descripcion_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_descripcion_size"
               value="{$settings.font_descripcion_size|intval}" min="6" max="72" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Color</label>
        <input type="color" name="color_descripcion"
               value="{$settings.color_descripcion|escape:'htmlall'}" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Ancho (px)</label>
        <input type="number" name="box_descripcion_w"
               value="{$settings.box_descripcion_w|default:330|intval}" min="10" max="2000" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Alto (px)</label>
        <input type="number" name="box_descripcion_h"
               value="{$settings.box_descripcion_h|default:30|intval}" min="10" max="2000" class="form-control" />
      </div>
    </div>

    <div class="row m-t-10">
      <div class="col-sm-4">
        <label>PVP RECOM PROV – Fuente</label>
        <select name="font_pvprp_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_pvprp_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_pvprp_size"
               value="{$settings.font_pvprp_size|intval}" min="6" max="72" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Color</label>
        <input type="color" name="color_pvprp"
               value="{$settings.color_pvprp|escape:'htmlall'}" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Ancho (px)</label>
        <input type="number" name="box_pvprp_w"
               value="{$settings.box_pvprp_w|default:120|intval}" min="10" max="2000" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Alto (px)</label>
        <input type="number" name="box_pvprp_h"
               value="{$settings.box_pvprp_h|default:33|intval}" min="10" max="2000" class="form-control" />
      </div>
    </div>

    <div class="row m-t-10">
      <div class="col-sm-4">
        <label>PVP – Fuente</label>
        <select name="font_pvp_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_pvp_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_pvp_size"
               value="{$settings.font_pvp_size|intval}" min="6" max="72" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Color</label>
        <input type="color" name="color_pvp"
               value="{$settings.color_pvp|escape:'htmlall'}" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Ancho (px)</label>
        <input type="number" name="box_pvp_w"
               value="{$settings.box_pvp_w|default:200|intval}" min="10" max="2000" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Alto (px)</label>
        <input type="number" name="box_pvp_h"
               value="{$settings.box_pvp_h|default:90|intval}" min="10" max="2000" class="form-control" />
      </div>
    </div>

    <hr/>
    <h4 class="m-t">{l s='Texto fijo (label)' mod='alsernetetiquetatiendas'}</h4>
    <div class="row">
      <div class="col-sm-4">
        <label>{l s='Texto (se repite en cada etiqueta)' mod='alsernetetiquetatiendas'}</label>
        <input type="text" name="label_text"
               value="{$settings.label_text|escape:'htmlall'}"
               class="form-control" placeholder="Precio recomendado:" />
      </div>
      <div class="col-sm-2">
        <label>Fuente</label>
        <select name="font_label_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_label_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_label_size"
               value="{$settings.font_label_size|intval}" min="6" max="72" class="form-control" />
      </div>
      <div class="col-sm-2">
        <label>Color</label>
        <input type="color" name="color_label"
               value="{$settings.color_label|escape:'htmlall'}" class="form-control" />
      </div>
      <div class="col-sm-1">
        <label>Ancho (px)</label>
        <input type="number" name="box_label_w"
               value="{$settings.box_label_w|default:220|intval}" min="10" max="2000" class="form-control" />
      </div>
      <div class="col-sm-1">
        <label>Alto (px)</label>
        <input type="number" name="box_label_h"
               value="{$settings.box_label_h|default:30|intval}" min="10" max="2000" class="form-control" />
      </div>
    </div>

    <hr/>
    <h4 class="m-t">{l s='Fuentes por campo – HORIZONTAL' mod='alsernetetiquetatiendas'}</h4>
    <p class="help-block">{l s='(Estas fuentes se aplican SOLO al PDF horizontal y su previsualización)' mod='alsernetetiquetatiendas'}</p>

    <div class="row">
      <div class="col-sm-4">
        <label>REFERENCIA – Fuente</label>
        <select name="font_referencia_h_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_referencia_h_family|default:$settings.font_referencia_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_referencia_h_size"
               value="{$settings.font_referencia_h_size|default:$settings.font_referencia_size|intval}" min="6" max="72" class="form-control" />
      </div>
      <div class="col-sm-6">
        <div class="alert alert-info" style="margin-top:24px;">
          {l s='Colores y tamaños de caja se reutilizan de la configuración vertical (puedes ajustarlos allí).' mod='alsernetetiquetatiendas'}
        </div>
      </div>
    </div>

    <div class="row m-t-10">
      <div class="col-sm-4">
        <label>DESCRIPCIÓN – Fuente</label>
        <select name="font_descripcion_h_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_descripcion_h_family|default:$settings.font_descripcion_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_descripcion_h_size"
               value="{$settings.font_descripcion_h_size|default:$settings.font_descripcion_size|intval}" min="6" max="72" class="form-control" />
      </div>
    </div>

    <div class="row m-t-10">
      <div class="col-sm-4">
        <label>PVP RECOM PROV – Fuente</label>
        <select name="font_pvprp_h_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_pvprp_h_family|default:$settings.font_pvprp_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_pvprp_h_size"
               value="{$settings.font_pvprp_h_size|default:$settings.font_pvprp_size|intval}" min="6" max="72" class="form-control" />
      </div>
    </div>

    <div class="row m-t-10">
      <div class="col-sm-4">
        <label>PVP – Fuente</label>
        <select name="font_pvp_h_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_pvp_h_family|default:$settings.font_pvp_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_pvp_h_size"
               value="{$settings.font_pvp_h_size|default:$settings.font_pvp_size|intval}" min="6" max="72" class="form-control" />
      </div>
    </div>

    <div class="row m-t-10">
      <div class="col-sm-4">
        <label>LABEL – Fuente</label>
        <select name="font_label_h_family" class="form-control">
          {foreach $fonts as $f}
            <option value="{$f}" {if $settings.font_label_h_family|default:$settings.font_label_family == $f}selected{/if}>{$f|capitalize}</option>
          {/foreach}
        </select>
      </div>
      <div class="col-sm-2">
        <label>Tamaño (pt)</label>
        <input type="number" name="font_label_h_size"
               value="{$settings.font_label_h_size|default:$settings.font_label_size|intval}" min="6" max="72" class="form-control" />
      </div>
    </div>

    <div class="panel-footer" style="margin-top:15px">
      <button type="submit" name="submitAlsernetConfig" class="btn btn-primary">
        <i class="icon-save"></i> {l s='Guardar' mod='alsernetetiquetatiendas'}
      </button>
    </div>
  </form>
</div>

{* ==== CSS PARA ACHICAR LOS “BOTONES” DE PREVIEW ==== *}
<style>
  #canvas .drag,
  #canvasH .drag {
    font-size: 10px;
    padding: 2px 4px;
    line-height: 1.1;
  }

  /* Opcional: PVP un pelín más protagonista pero pequeño igual */
  #canvas .drag[data-key="pvp"],
  #canvasH .drag[data-key="pvp"] {
    font-size: 11px;
    font-weight: bold;
  }
</style>

{* ===================== PREVIEW: VERTICAL (4) ===================== *}
<div class="panel">
  <h3><i class="icon-picture"></i> {l s='Previsualización vertical (4 por hoja)' mod='alsernetetiquetatiendas'}</h3>

  {if !$settings.base_image}
    <div class="alert alert-warning">
      {l s='Primero sube una imagen base vertical y guarda.' mod='alsernetetiquetatiendas'}
    </div>
  {else}
    <div id="preview-wrapper-v">
      <div id="canvas-outer" style="position:relative; overflow:hidden; border:1px solid #ddd; display:inline-block;">
        <div id="canvas" class="a4-canvas"
             style="position:relative; width:700px; height:990px;
                    background:url('{$module_dir}uploads/{$settings.base_image|escape:'url'}') no-repeat;
                    background-size:100% 100%;">
          {section name=i start=1 loop=5} {* 4 slots efectivos (1..4) *}
            <div class="drag" data-key="label" data-slot="{$smarty.section.i.index}">
              {$settings.label_text|escape:'htmlall'} {$smarty.section.i.index}
            </div>
            <div class="drag" data-key="referencia" data-slot="{$smarty.section.i.index}">
              312553_{$smarty.section.i.index}
            </div>
            <div class="drag" data-key="descripcion" data-slot="{$smarty.section.i.index}" style="text-align:center;white-space:normal;line-height:1.2;">
              Trípode Primos Trigger Stick de 3ª Generación_{$smarty.section.i.index}
            </div>
            <div class="drag" data-key="pvprp" data-slot="{$smarty.section.i.index}">
              169,99€_{$smarty.section.i.index}
            </div>
            <div class="drag" data-key="pvp" data-slot="{$smarty.section.i.index}">
              149,99€_{$smarty.section.i.index}
            </div>
          {/section}
        </div>
      </div>

      <div class="m-t-10">
        <button id="save-positions-v" class="btn btn-default">
          <i class="icon-save"></i> {l s='Guardar posiciones (vertical)' mod='alsernetetiquetatiendas'}
        </button>
      </div>
    </div>
  {/if}
</div>


{* ===================== PREVIEW: HORIZONTAL (8) ===================== *}
<div class="panel">
  <h3><i class="icon-picture"></i> {l s='Previsualización horizontal (8 por hoja)' mod='alsernetetiquetatiendas'}</h3>

  {if !$settings.base_image_h}
    <div class="alert alert-warning">
      {l s='Sube una imagen base horizontal y guarda.' mod='alsernetetiquetatiendas'}
    </div>
  {else}
    <div id="preview-wrapper-h">
      <div id="canvasH-outer" style="position:relative; overflow:hidden; border:1px solid #ddd; display:inline-block;">
        <div id="canvasH" class="a4-canvas"
             style="position:relative; width:1133px; height:720px; {* Oficio apaisado *}
                    background:url('{$module_dir}uploads/{$settings.base_image_h|escape:'url'}') no-repeat;
                    background-size:100% 100%;">
          {section name=j start=1 loop=9} {* 8 slots efectivos (1..8) *}
            <div class="drag" data-key="label" data-slot="{$smarty.section.j.index}" data-orient="h">
              {$settings.label_text|escape:'htmlall'} {$smarty.section.j.index}
            </div>
            <div class="drag" data-key="referencia" data-slot="{$smarty.section.j.index}" data-orient="h">
              312553_{$smarty.section.j.index}
            </div>
            <div class="drag" data-key="descripcion" data-slot="{$smarty.section.j.index}" data-orient="h" style="text-align:center;white-space:normal;line-height:1.2;">
              Trípode Primos Trigger Stick de 3ª Generación_{$smarty.section.j.index}
            </div>
            <div class="drag" data-key="pvprp" data-slot="{$smarty.section.j.index}" data-orient="h">
              169,99€_{$smarty.section.j.index}
            </div>
            <div class="drag" data-key="pvp" data-slot="{$smarty.section.j.index}" data-orient="h">
              149,99€_{$smarty.section.j.index}
            </div>
          {/section}
        </div>
      </div>

      <div class="m-t-10">
        <button id="save-positions-h" class="btn btn-default">
          <i class="icon-save"></i> {l s='Guardar posiciones (horizontal)' mod='alsernetetiquetatiendas'}
        </button>
      </div>
    </div>
  {/if}
</div>

<hr/>
<form id="excel-form" enctype="multipart/form-data" method="post" onsubmit="return false;">
  <div class="form-group">
    <label>{l s='Archivo Excel (XLSX/XLS) con columnas: REFERENCIA, DESCRIPCION, PVP RECOM PROV, PVP' mod='alsernetetiquetatiendas'}</label>
    <input type="file" name="excel_file" accept=".xlsx,.xls" class="form-control" required />
  </div>
  <button id="generate" class="btn btn-primary">
    <i class="icon-file-text"></i> {l s='Generar PDF (Vertical + Horizontal)' mod='alsernetetiquetatiendas'}
  </button>
  <span id="gen-status" style="margin-left:10px"></span>
</form>

{* ================== JS CONFIG (valores para ambos previews) ================== *}
<script>
  // --- Vertical ---
  window.ALSER_URLS = {ldelim}
    save: "{$savepos_url|escape:'javascript'}",
    gen:  "{$generate_url|escape:'javascript'}",
    pos: {ldelim}
      referencia: {ldelim}
        1:{ldelim}x:{$settings.pos_referencia_x1|intval},y:{$settings.pos_referencia_y1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_referencia_x2|intval},y:{$settings.pos_referencia_y2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_referencia_x3|intval},y:{$settings.pos_referencia_y3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_referencia_x4|intval},y:{$settings.pos_referencia_y4|intval}{rdelim}
      {rdelim},
      descripcion: {ldelim}
        1:{ldelim}x:{$settings.pos_descripcion_x1|intval},y:{$settings.pos_descripcion_y1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_descripcion_x2|intval},y:{$settings.pos_descripcion_y2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_descripcion_x3|intval},y:{$settings.pos_descripcion_y3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_descripcion_x4|intval},y:{$settings.pos_descripcion_y4|intval}{rdelim}
      {rdelim},
      pvprp: {ldelim}
        1:{ldelim}x:{$settings.pos_pvprp_x1|intval},y:{$settings.pos_pvprp_y1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_pvprp_x2|intval},y:{$settings.pos_pvprp_y2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_pvprp_x3|intval},y:{$settings.pos_pvprp_y3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_pvprp_x4|intval},y:{$settings.pos_pvprp_y4|intval}{rdelim}
      {rdelim},
      pvp: {ldelim}
        1:{ldelim}x:{$settings.pos_pvp_x1|intval},y:{$settings.pos_pvp_y1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_pvp_x2|intval},y:{$settings.pos_pvp_y2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_pvp_x3|intval},y:{$settings.pos_pvp_y3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_pvp_x4|intval},y:{$settings.pos_pvp_y4|intval}{rdelim}
      {rdelim},
      label: {ldelim}
        1:{ldelim}x:{$settings.pos_label_x1|intval},y:{$settings.pos_label_y1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_label_x2|intval},y:{$settings.pos_label_y2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_label_x3|intval},y:{$settings.pos_label_y3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_label_x4|intval},y:{$settings.pos_label_y4|intval}{rdelim}
      {rdelim}
    {rdelim},
    colors: {ldelim}
      referencia: "{$settings.color_referencia|escape:'htmlall'}",
      descripcion:"{$settings.color_descripcion|escape:'htmlall'}",
      pvprp:"{$settings.color_pvprp|escape:'htmlall'}",
      pvp:"{$settings.color_pvp|escape:'htmlall'}",
      label:"{$settings.color_label|escape:'htmlall'}"
    {rdelim},
    fonts: {ldelim}
      referencia:{ldelim}family:"{$settings.font_referencia_family|escape:'javascript'}",size:{10|intval}{rdelim},
      descripcion:{ldelim}family:"{$settings.font_descripcion_family|escape:'javascript'}",size:{15|intval}{rdelim},
      pvprp:{ldelim}family:"{$settings.font_pvprp_family|escape:'javascript'}",size:{20|intval}{rdelim},
      pvp:{ldelim}family:"{$settings.font_pvp_family|escape:'javascript'}",size:{30|intval}{rdelim},
      label:{ldelim}family:"{$settings.font_label_family|escape:'javascript'}",size:{7|intval}{rdelim}
    {rdelim},
    scale_factor: 0.8
  {rdelim};

  // --- Horizontal ---
  window.ALSER_URLS_H = {ldelim}
    save: "{$savepos_url|escape:'javascript'}",
    pos: {ldelim}
      referencia: {ldelim}
        1:{ldelim}x:{$settings.pos_referencia_hx1|intval},y:{$settings.pos_referencia_hy1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_referencia_hx2|intval},y:{$settings.pos_referencia_hy2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_referencia_hx3|intval},y:{$settings.pos_referencia_hy3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_referencia_hx4|intval},y:{$settings.pos_referencia_hy4|intval}{rdelim},
        5:{ldelim}x:{$settings.pos_referencia_hx5|intval},y:{$settings.pos_referencia_hy5|intval}{rdelim},
        6:{ldelim}x:{$settings.pos_referencia_hx6|intval},y:{$settings.pos_referencia_hy6|intval}{rdelim},
        7:{ldelim}x:{$settings.pos_referencia_hx7|intval},y:{$settings.pos_referencia_hy7|intval}{rdelim},
        8:{ldelim}x:{$settings.pos_referencia_hx8|intval},y:{$settings.pos_referencia_hy8|intval}{rdelim}
      {rdelim},
      descripcion: {ldelim}
        1:{ldelim}x:{$settings.pos_descripcion_hx1|intval},y:{$settings.pos_descripcion_hy1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_descripcion_hx2|intval},y:{$settings.pos_descripcion_hy2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_descripcion_hx3|intval},y:{$settings.pos_descripcion_hy3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_descripcion_hx4|intval},y:{$settings.pos_descripcion_hy4|intval}{rdelim},
        5:{ldelim}x:{$settings.pos_descripcion_hx5|intval},y:{$settings.pos_descripcion_hy5|intval}{rdelim},
        6:{ldelim}x:{$settings.pos_descripcion_hx6|intval},y:{$settings.pos_descripcion_hy6|intval}{rdelim},
        7:{ldelim}x:{$settings.pos_descripcion_hx7|intval},y:{$settings.pos_descripcion_hy7|intval}{rdelim},
        8:{ldelim}x:{$settings.pos_descripcion_hx8|intval},y:{$settings.pos_descripcion_hy8|intval}{rdelim}
      {rdelim},
      pvprp: {ldelim}
        1:{ldelim}x:{$settings.pos_pvprp_hx1|intval},y:{$settings.pos_pvprp_hy1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_pvprp_hx2|intval},y:{$settings.pos_pvprp_hy2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_pvprp_hx3|intval},y:{$settings.pos_pvprp_hy3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_pvprp_hx4|intval},y:{$settings.pos_pvprp_hy4|intval}{rdelim},
        5:{ldelim}x:{$settings.pos_pvprp_hx5|intval},y:{$settings.pos_pvprp_hy5|intval}{rdelim},
        6:{ldelim}x:{$settings.pos_pvprp_hx6|intval},y:{$settings.pos_pvprp_hy6|intval}{rdelim},
        7:{ldelim}x:{$settings.pos_pvprp_hx7|intval},y:{$settings.pos_pvprp_hy7|intval}{rdelim},
        8:{ldelim}x:{$settings.pos_pvprp_hx8|intval},y:{$settings.pos_pvprp_hy8|intval}{rdelim}
      {rdelim},
      pvp: {ldelim}
        1:{ldelim}x:{$settings.pos_pvp_hx1|intval},y:{$settings.pos_pvp_hy1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_pvp_hx2|intval},y:{$settings.pos_pvp_hy2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_pvp_hx3|intval},y:{$settings.pos_pvp_hy3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_pvp_hx4|intval},y:{$settings.pos_pvp_hy4|intval}{rdelim},
        5:{ldelim}x:{$settings.pos_pvp_hx5|intval},y:{$settings.pos_pvp_hy5|intval}{rdelim},
        6:{ldelim}x:{$settings.pos_pvp_hx6|intval},y:{$settings.pos_pvp_hy6|intval}{rdelim},
        7:{ldelim}x:{$settings.pos_pvp_hx7|intval},y:{$settings.pos_pvp_hy7|intval}{rdelim},
        8:{ldelim}x:{$settings.pos_pvp_hx8|intval},y:{$settings.pos_pvp_hy8|intval}{rdelim}
      {rdelim},
      label: {ldelim}
        1:{ldelim}x:{$settings.pos_label_hx1|intval},y:{$settings.pos_label_hy1|intval}{rdelim},
        2:{ldelim}x:{$settings.pos_label_hx2|intval},y:{$settings.pos_label_hy2|intval}{rdelim},
        3:{ldelim}x:{$settings.pos_label_hx3|intval},y:{$settings.pos_label_hy3|intval}{rdelim},
        4:{ldelim}x:{$settings.pos_label_hx4|intval},y:{$settings.pos_label_hy4|intval}{rdelim},
        5:{ldelim}x:{$settings.pos_label_hx5|intval},y:{$settings.pos_label_hy5|intval}{rdelim},
        6:{ldelim}x:{$settings.pos_label_hx6|intval},y:{$settings.pos_label_hy6|intval}{rdelim},
        7:{ldelim}x:{$settings.pos_label_hx7|intval},y:{$settings.pos_label_hy7|intval}{rdelim},
        8:{ldelim}x:{$settings.pos_label_hx8|intval},y:{$settings.pos_label_hy8|intval}{rdelim}
      {rdelim}
    {rdelim},
    colors: window.ALSER_URLS ? window.ALSER_URLS.colors : {ldelim}{rdelim},
    fonts: {ldelim}
      referencia:{ldelim}family:"{$settings.font_referencia_h_family|default:$settings.font_referencia_family|escape:'javascript'}",size:{10|intval}{rdelim},
      descripcion:{ldelim}family:"{$settings.font_descripcion_h_family|default:$settings.font_descripcion_family|escape:'javascript'}",size:{10|intval}{rdelim},
      pvprp:{ldelim}family:"{$settings.font_pvprp_h_family|default:$settings.font_pvprp_family|escape:'javascript'}",size:{15|intval}{rdelim},
      pvp:{ldelim}family:"{$settings.font_pvp_h_family|default:$settings.font_pvp_family|escape:'javascript'}",size:{25|intval}{rdelim},
      label:{ldelim}family:"{$settings.font_label_h_family|default:$settings.font_label_family|escape:'javascript'}",size:{7|intval}{rdelim}
    {rdelim},
    scale_factor: 0.9
  {rdelim};
</script>
