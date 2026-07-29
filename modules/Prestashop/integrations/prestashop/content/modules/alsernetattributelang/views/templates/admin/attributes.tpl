{* Mensajes *}
{if $errors && count($errors)}
  <div class="alert alert-danger">
    {foreach from=$errors item=e}{$e|escape:'html'}<br>{/foreach}
  </div>
{/if}
{if $success && count($success)}
  <div class="alert alert-success">
    {foreach from=$success item=s}{$s|escape:'html'}<br>{/foreach}
  </div>
{/if}

<div class="panel">
  <h3>{l s='Traducciones de atributos por producto' mod='alsernetattributelang'}</h3>

  <form method="post" action="{$currentIndex|escape:'html'}&token={$token|escape:'html'}" class="form-inline">
    <div class="form-group">
      <label for="id_product" class="control-label">{l s='ID Producto' mod='alsernetattributelang'}</label>
      <input type="number" min="1" class="form-control" name="id_product" id="id_product" value="{$id_product|intval}">
    </div>
    <button type="submit" name="submitLoad" class="btn btn-primary">
      {l s='Cargar atributos' mod='alsernetattributelang'}
    </button>
  </form>
</div>

{if $groups || $attributes}
<form method="post" action="{$currentIndex|escape:'html'}&token={$token|escape:'html'}">
  <input type="hidden" name="id_product" value="{$id_product|intval}" />

  <div class="panel">
    <h3>{l s='Grupos de atributos (ej. Talla, Color)' mod='alsernetattributelang'}</h3>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="width:30%;">{l s='Attributos en ES' mod='alsernetattributelang'}</th>
            {foreach from=$languages item=lng}
              <th>
                {$lng.name|escape:'html'}
              </th>
            {/foreach}
          </tr>
        </thead>
        <tbody>
          {foreach from=$groups key=id_group item=g}
            <tr>
              {* Columna referencia (id_lang=1) *}
              <td>
                <strong>{$g.names[$ref_lang_id]|escape:'html'}</strong>
                <br>
                <small class="text-muted">{l s='Public name:' mod='alsernetattributelang'} {$g.public_names[$ref_lang_id]|escape:'html'}</small>
                <div class="text-muted">ID Group: {$id_group|intval}</div>
              </td>

              {* Inputs por idioma *}
              {foreach from=$languages item=lng}
                <td>
                  <div class="form-group" style="display: none;">
                    <label>{l s='Nombre' mod='alsernetattributelang'}</label>
                    <input class="form-control"
                      name="group[{$id_group|intval}][{$lng.id_lang|intval}]"
                      value="{$g.names[$lng.id_lang]|escape:'html'}">
                  </div>
                  <div class="form-group" style="margin-top:6px;">
                    <label>{l s='Nombre público' mod='alsernetattributelang'}</label>
                    <input class="form-control"
                      name="group_public[{$id_group|intval}][{$lng.id_lang|intval}]"
                      value="{$g.public_names[$lng.id_lang]|escape:'html'}">
                  </div>
                </td>
              {/foreach}
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <h3>{l s='Valores de atributos (ej. S, M, L / Rojo, Azul)' mod='alsernetattributelang'}</h3>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th style="width:30%;">{l s='Valores en ES' mod='alsernetattributelang'}</th>
            {foreach from=$languages item=lng}
              <th>
                {$lng.name|escape:'html'}
              </th>
            {/foreach}
          </tr>
        </thead>
        <tbody>
          {foreach from=$attributes key=id_attribute item=a}
            <tr>
              <td>
                <strong>
                  {$a.names[$ref_lang_id]|escape:'html'}
                </strong>
                <div class="text-muted">
                  ID Attribute: {$id_attribute|intval}
                  — {l s='Grupo:' mod='alsernetattributelang'} {$groups[$a.id_group].names[$ref_lang_id]|escape:'html'}
                </div>
              </td>
              {foreach from=$languages item=lng}
                <td>
                  <input class="form-control"
                    name="attr[{$id_attribute|intval}][{$lng.id_lang|intval}]"
                    value="{$a.names[$lng.id_lang]|escape:'html'}">
                </td>
              {/foreach}
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <button type="submit" name="submitSaveTranslations" class="btn btn-success">
      {l s='Guardar traducciones' mod='alsernetattributelang'}
    </button>
  </div>
</form>
{/if}
