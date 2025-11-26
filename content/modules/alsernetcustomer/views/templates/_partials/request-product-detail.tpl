
  <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 mb-3">
    <div class="summery-box">
      <div class="summery-header d-block">
        <h3>{l s='Request information' mod='wkrma'}</h3>
      </div>
      <ul class="summery-contain">
        <li>
          <h4>{l s='Request Id' mod='wkrma'}: :</h4>
          <h4 class="price"> #{$rma_request_detail['id']}</h4>
        </li>

        <li>
          <h4>{l s='Return type' mod='wkrma'}:</h4>
          <h4 class="price ">{$rma_request_detail['return_type']}</h4>
        </li>
        <li>
          <h4>{l s='Return reason' mod='wkrma'}:</h4>
          <h4 class="price ">{$rma_request_detail['return_reason']}</h4>
        </li>
        <li>
          <h4>{l s='Created on' mod='wkrma'}:</h4>
          <h4 class="price">
              {dateFormat date=$rma_request_detail['date_add']}
          </h4>
        </li>

        <li>
          <h4>{l s='Return quantity' mod='wkrma'} :</h4>
          <h4 class="price ">{$rma_request_detail['product_quantity']}</h4>
        </li>
      </ul>
    </div>
  </div>

  {if isset($pickup_address)}
  <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 mb-3">
    <div class="summery-box">
      <div class="summery-header d-block">
        <h3>{l s='Pickup selection' mod='wkrma'}:</h3>
      </div>
      <ul class="summery-contain">
        <li>
          <h4>{l s='Pickup selection' mod='wkrma'}: :</h4>
          <h4 class="price">
            {if $rma_request_detail['pickup_selection'] == 2 || $rma_request_detail['id_address']}
              {l s='Courier collection' mod='wkrma'}
            {elseif $rma_request_detail['pickup_selection'] == 1}
              {l s='Drop off at office' mod='wkrma'}
            {/if}
          </h4>
        </li>

        <li>
          <h4>{l s='Pickup address' mod='wkrma'}:</h4>
          <h4 class="price ">{$pickup_address nofilter}</h4>
        </li>
        <li>
          <h4>{l s='Product name' mod='wkrma'}: :</h4>
          <h4 class="price ">galicia</h4>
        </li>
        <li>
          <h4>Address:</h4>
          <h4 class="price">
            calle
          </h4>
        </li>

        <li>
          <h4>{l s='Order reference' mod='wkrma'}:</h4>
          <h4 class="price ">{if isset($order_share) && $order_share == 1}
              <a href="{$rma_request_detail['order_link']}" target="_blank">
                {$rma_request_detail['order_reference']}
              </a>
            {else}
              {$rma_request_detail['order_reference']}
            {/if}</h4>
        </li>


      </ul>
    </div>
  </div>
  {/if}


