<tr>
  <td>
    <select class="form-control" name="brand">
      <option value="">-- Selecciona --</option>
      {foreach from=$manufacturers item=man}
        <option value="{$man.id_manufacturer}" {if isset($row.id_manufacturer) && $row.id_manufacturer == $man.id_manufacturer}selected{/if}>
          {$man.name}
        </option>
      {/foreach}
    </select>
  </td>
  <td>
    <input type="number" class="form-control" name="category"
           value="{if isset($row.id_category)}{$row.id_category}{/if}" min="1">
  </td>
  <td class="text-center">
    <a href="#" class="btn btn-danger btn-sm deleteRow"><i class="icon-trash"></i></a>
  </td>
</tr>
