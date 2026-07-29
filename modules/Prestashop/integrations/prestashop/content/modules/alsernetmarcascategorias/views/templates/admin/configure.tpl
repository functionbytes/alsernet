{block name="content"}
  {if $mode == 'list'}
    <!-- ========== BLOQUE 1: Marcas como Categorías (PRIMERO) - categorías con id_parent=2821 ========== -->
    <div class="panel">
      <h3>Marcas como Categorías</h3>
      <form method="post" id="brandCatForm">
        <input type="hidden" name="saveBrandCatTable" value="1">
        <input type="hidden" id="brand_cat_json" name="brand_cat_json" value="" />
        <div class="table-sticky-container">
        <table class="table" id="brandCatTable">
          <thead>
            <tr>
              <th>Marca</th>
              <th>Categoría</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$brandCatPairs item=row}
              <tr>
                <td>
                  <select class="form-control" name="brand">
                    <option value="">-- Seleccionar --</option>
                    {foreach from=$manufacturers item=man}
                      <option value="{$man.id_manufacturer}" {if $man.id_manufacturer == $row.brand}selected{/if}>{$man.name|escape:'html'}</option>
                    {/foreach}
                  </select>
                </td>
                <td>
                  <select class="form-control category-select" name="category">
                    <option value="">-- Seleccionar --</option>
                    {foreach from=$childCategories item=cat}
                      <option value="{$cat.id_category}" {if $cat.id_category == $row.category}selected{/if}>{$cat.name|escape:'html'}</option>
                    {/foreach}
                  </select>
                </td>
                <td><a href="#" class="btn btn-danger deleteRow">Eliminar</a></td>
              </tr>
            {/foreach}
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3">
                <button type="button" class="btn btn-default" id="addRow">Añadir fila</button>
                <button type="submit" class="btn btn-primary pull-right">Guardar</button>
              </td>
            </tr>
          </tfoot>
                </table>
      </div>
      </form>

      <!-- Templates para JS (opciones) -->
      <script type="text/template" id="brandOptionsTemplate">
        <option value="">-- Seleccionar --</option>
        {foreach from=$manufacturers item=man}
          <option value="{$man.id_manufacturer}">{$man.name|escape:'html'}</option>
        {/foreach}
      </script>
      <script type="text/template" id="categoryOptionsTemplate">
        <option value="">-- Seleccionar --</option>
        {foreach from=$childCategories item=cat}
          <option value="{$cat.id_category}">{$cat.name|escape:'html'}</option>
        {/foreach}
      </script>

      <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
          var form = document.getElementById('brandCatForm');
          var table = document.getElementById('brandCatTable').getElementsByTagName('tbody')[0];
          var addBtn = document.getElementById('addRow');
          var optionsHtml = document.getElementById('brandOptionsTemplate').innerHTML;
          var categoryOptions = document.getElementById('categoryOptionsTemplate').innerHTML;

          function initSelect2() {
            if (typeof $ !== 'undefined' && $.fn.select2) {
              $('.category-select').select2({
                width: 'resolve',
                placeholder: '-- Seleccionar categoría --'
              });
            }
          }

          initSelect2();

          addBtn.addEventListener('click', function () {
            var row = document.createElement('tr');
            row.innerHTML =
              '<td>' +
                '<select class="form-control" name="brand">' + optionsHtml + '</select>' +
              '</td>' +
              '<td>' +
                '<select class="form-control category-select" name="category">' + categoryOptions + '</select>' +
              '</td>' +
              '<td><a href="#" class="btn btn-danger deleteRow">Eliminar</a></td>';
            table.appendChild(row);
            initSelect2();
          });

          table.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('deleteRow')) {
              e.preventDefault();
              var tr = e.target.closest('tr');
              if (tr) { tr.parentNode.removeChild(tr); }
            }
          });

          form.addEventListener('submit', function () {
            var data = [];
            var rows = table.getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
              var brand = rows[i].querySelector('select[name="brand"]');
              var category = rows[i].querySelector('select[name="category"]');
              if (brand && category && brand.value && category.value) {
                data.push({ brand: brand.value, category: category.value });
              }
            }
            document.getElementById('brand_cat_json').value = JSON.stringify(data);
          });
        });
      </script>
    </div>

    <!-- ========== BLOQUE 2: Tabla de marcas y categorías 3..11 con checkboxes (AJAX) ========== -->
    <div class="panel">
      <h3>Categorías Nivel 2</h3>
      <style>
        .table-sticky-container { max-height: 70vh; overflow: auto; }
        .table-sticky-container thead th { position: sticky; top: 0; z-index: 2; background: #fff; }
        .brand-row.active td { background-color: #ffffff !important; }
        .brand-row.inactive td { background-color: #ff9696 !important; }
      </style>
      <div class="table-sticky-container">
      <table class="table" id="brandsCatsMatrix">
        <thead>
          <tr>
            <th>ID Marca</th>
            <th>Marca</th>
            <th>Productos</th>
            <th>Foto</th>
            {foreach from=$categoriesHeader item=ch}
              <th>{$ch.name}</th>
            {/foreach}
          </tr>
        </thead>
        <tbody>
          {foreach from=$brandRows item=row}
            <tr class="brand-row {if $row.is_active}active{else}inactive{/if}">
              <td>{$row.id_manufacturer}</td>
              <td>{$row.name}</td>
              <td>
                <span class="badge badge-success">{$row.active_count}</span>
                /
                <span class="badge badge-danger">{$row.inactive_count}</span>
              </td>
              <td>
                {if $row.img}
                  <a href="{$link->getManufacturerLink($row.id_manufacturer)|escape:'html'}" target="_blank" rel="noopener"><img src="{$row.img}" alt="{$row.name}" /></a>
                {/if}
              </td>
              {foreach from=$categoriesHeader item=ch}
                <td>
                  <input type="checkbox"
                         class="toggle-as-cat"
                         data-brand="{$row.id_manufacturer}"
                         data-cat="{$ch.id_category}"
                         {if isset($row.assoc[$ch.id_category])}checked{/if} />
                </td>
              {/foreach}
            </tr>
          {/foreach}
        </tbody>
            </table>
      </div>
      <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function(){
          var table = document.getElementById('brandsCatsMatrix');
          table.addEventListener('change', function(e){
            if (e.target && e.target.classList.contains('toggle-as-cat')) {
              var brandId = e.target.getAttribute('data-brand');
              var catId   = e.target.getAttribute('data-cat');
              var checked = e.target.checked ? 1 : 0;
              var url = '?controller=AdminAlsernetMarcasCategorias&ajax=1&action=toggleBrandAsCategory&token={$token}';
              var formData = new FormData();
              formData.append('brand_id', brandId);
              formData.append('category_id', catId);
              formData.append('checked', checked);
              fetch(url, { method: 'POST', body: formData })
                .then(function(r){ return r.json(); })
                .then(function(json){ if (!json.success) { alert('Error al guardar'); e.target.checked = !checked; }})
                .catch(function(){ alert('Error de red'); e.target.checked = !checked; });
            }
          });
        });
      </script>
    </div>
  {elseif $mode == 'edit'}
    <div class="panel">
      <h3>Marcas para categoria "{$currentCategory->name}"</h3>
      <form method="post" action="" class="form-horizontal">
        <input type="hidden" name="category_id" value="{$currentCategory->id}" />
        <div class="form-group">
          {foreach from=$manufacturers item=man}
            <div class="checkbox">
              <label>
                <input type="checkbox" name="brands[]" value="{$man.id_manufacturer}"{if in_array($man.id_manufacturer, $currentBrands)} checked{/if} /> {$man.name}
              </label>
            </div>
          {/foreach}
        </div>
        <div class="panel-footer">
          <button type="submit" name="submitCategoryBrands" class="btn btn-primary">Guardar cambios</button>
          <a href="?controller=AdminAlsernetMarcasCategorias&token={$token}" class="btn btn-default">Volver</a>
        </div>
      </form>
    </div>
  {/if}
{/block}
