{extends file='customer/order-detail.tpl'}

{block name='breadcrumb'}
  {include file="customer/_partials/breadcrumb.tpl" title={l s='Guest Tracking' d='Shop.Customer.Orders'}}
{/block}

{block name='page_content_container'}

  <section class="order-detail">
    <div class="container">

      <div class="order-detail">
        <div class="row g-sm-4 g-3">

          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-file-spreadsheet"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Order id' d='Shop.Customer.Orders'}</h5>
                <h2 class="theme-color">{$order.details.id}</h2>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-user-tie"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Order customer' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.addresses.delivery.firstname|regex_replace:'/ .*/':''|capitalize} {$order.addresses.delivery.lastname|substr:0:1|upper}</h4>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-bullhorn"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Order state' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.details.status}</h4>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Order address ' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.addresses.delivery.address1}, {$order.addresses.delivery.postcode}, {$order.addresses.delivery.city}, {$order.addresses.delivery.country}</h4>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-credit-card-blank"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Payment method' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.details.payment}</h4>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-clock"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Order date' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.details.order_date}</h4>
              </div>
            </div>
          </div>

          {if isset($order.shipping.latest) }
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-van-shuttle"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Shipping carrier' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.shipping.latest.description}</h4>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-calendar-days"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Shipping date' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.shipping.latest.fenvio}</h4>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
            <div class="order-details-contain">
              <div class="order-tracking-icon">
                <i class="fa-solid fa-file-spreadsheet"></i>
              </div>
              <div class="order-details-name">
                <h5 class="text-content">{l s='Shipping cod' d='Shop.Customer.Orders'}</h5>
                <h4>{$order.shipping.latest.codtracking}</h4>
              </div>
            </div>
          </div>

          {/if}


          {if isset($order.history) && $order.history|@count > 0}
            <hr>
            <div class="row">
              <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <h5>{l s='History title' d='Shop.Customer.Orders'}</h5>
                <p>{l s='History description' d='Shop.Customer.Orders'}</p>
                <div class="table-responsive">
                  <table class="table order-tab-table">
                    <thead>
                    <tr>
                      <th>{l s='Description' d='Shop.Customer.Orders'}</th>
                      <th>{l s='Date' d='Shop.Customer.Orders'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$order.history item=state}
                      <tr>
                        <td>{$state.ostate_name}</td>
                        <td>{$state.history_date|date_format:"%d/%m/%Y"}</td>
                      </tr>
                    {/foreach}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          {/if}

          {if isset($order.shipping.all) && $order.shipping.all|@count > 0}
            <hr>
            <div class="row">
              <div class="col-sp-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <h5>{l s='Tracking title' d='Shop.Customer.Orders'}</h5>
                <p>{l s='Tracking description' d='Shop.Customer.Orders'}</p>
                <div class="table-responsive">
                  <table class="table order-tab-table">
                    <thead>
                    <tr>
                      <th>{l s='Date' d='Shop.Customer.Orders'}</th>
                      <th>{l s='Transportation agency' d='Shop.Theme.Checkout'}</th>
                      <th>{l s='Number of follow-up' d='Shop.Theme.Checkout'}</th>
                      <th>{l s='Tracking link' d='Shop.Theme.Checkout'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach from=$order.shipping.all item=line}
                      <tr>
                        <td>{$line.fenvio|default:'-'}</td>
                        <td>{$line.description|default:'-'}</td>
                        <td>{$line.codtracking|default:'-'}</td>
                        <td>
                          {if isset($line.url) && $line.url != '#'}
                            <a href="{$line.url}" target="_blank" rel="noopener noreferrer">
                              {l s='click here tracking' d='Shop.Customer.Orders'}
                            </a>
                          {else}
                            <span>{l s='No tracking link' d='Shop.Theme.Checkout'}</span>
                          {/if}
                        </td>
                      </tr>
                    {/foreach}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          {/if}



        </div>
      </div>
    </div>
  </section>


{/block}
