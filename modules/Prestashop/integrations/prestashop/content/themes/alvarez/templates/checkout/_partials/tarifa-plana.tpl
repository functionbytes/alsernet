

{if $tarifa_plana}
  <div class="tarifa-plana">
    {block name='tarifa_plana_form'}
      <form class="clearfix" id="add-tarifa-plana" data-refresh="{url entity='cart' params=['ajax' => 1, 'action' => 'refresh']}" action="{if $page.page_name == 'cart'}{url entity='cart' params=['ajax' => 1, 'action' => 'selectTarifaPlana']}{else}{url entity='order' params=['ajax' => 1, 'action' => 'selectTarifaPlana']}{/if}" method="post">
        <input type="hidden" name="token" value="{$static_token}">
        <input type="hidden" name="product_tarifa_plana" value="{$id_product_tarifa_plana}">
        <input type="hidden" name="product_attribute_tarifa_plana" value="{$id_product_attribute_tarifa_plana}">
        <input type="hidden" name="product_price" value="{$tarifa_plana_price}">

        <div class="tarifa-plana-option">
          <div class="tarifa-plana-input">
            <span class="custom-checkbox">
              <label>
                <input type="checkbox" name="add_tarifaplana" id="add_tarifaplana" value="1" {if $contains_tarifa_plana && $contains_tarifa_plana.quantity|intval > 0}checked{/if}/>
                <span>
                  <i class="material-icons rtl-no-flip checkbox-checked">&#xE876;</i>
                </span>
              </label>
            </span>
          </div>
          <label for="add_tarifaplana" class="tarifa-plana-option-2">
            <div class="row">
              <div class="col-sm-9 col-xs-8">
                <div class="row">
                  <div class="col-xs-12">
                    <span class="tarifa-plana-name">{l s='Hire FLAT RATE' d='Shop.Theme.Global'}<br>{l s='FREE SHIPPING for one year' d='Shop.Theme.Global'}</span><br>
                    <span class="tarifa-plana-conditions"><a target="_blank" href="{Context::getContext()->link->getCMSLink(35)}">{l s='View conditions' d="Shop.Theme.Checkout"}</a></span>
                  </div>
                </div>
              </div>
              <div class="col-sm-3 col-xs-4 tarifa-plana-precio">
                <span class="carrier-price">{$tarifa_plana_price_display}</span>
              </div>
            </div>
          </label>
        </div>
        <div class="clearfix"></div>
        <button style="display: none;" data-button-action="add-tarifa-plana" type="submit">Add</button>
      </form>
    {/block}
  </div>
{/if}

{if $contains_tarifa_plana && !$restriction_country}
  <div id="blockcart-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' d='Shop.Theme.Global'}">
            <span aria-hidden="true"><i class="material-icons">close</i></span>
          </button>
          <h4 class="modal-title h6 text-sm-left" id="myModalLabel">{l s='Warning' d='Shop.Theme.Checkout'}</h4>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-2 col-sm-3 col-xs-12 col-sp-12 divide-right modal-product-blocked-product-type-img">
              <img class="img-fluid" alt="" title="" src="/themes/child_alvarez/assets/img/icons/close.svg" />
            </div>
            <div class="col-md-10 col-sm-9 col-xs-12 col-sp-12 divide-right modal-product-blocked-product-type">
              <div class="title">{l s='The shipping flat rate is incompatible with the selected delivery address' d='Shop.Theme.Checkout'}</div>
              <div class="text">Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Cras mattis consectetur purus sit amet fermentum. Donec sed odio dui. Donec id elit non mi porta gravida at eget metus.</div>
            </div>
            <div class="col-md-12 col-sm-12 col-xs-12 col-sp-12 modal-product-blocked-product-type-buttons">
              <button type="button" class="btn btn-primary btn-cancel" data-dismiss="modal">{l s='Accept' d='Shop.Theme.Actions'}</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
{/if}