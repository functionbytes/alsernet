{extends file='customer/page.tpl'}

{block name='page_content'}

  <button class="btn btn-dashboard-show mb-4 d-lg-none">
    {l s='Show menu' d='Shop.Customer.Orders'}
  </button>

  <div class="dashboard-right-sidebar">

    <div class="dashboard-tracking">

      <div class="dashboard-title d-flex justify-content-between align-items-center">
        <div class="title">
          <h2>{l s='Order tracking' d='Shop.Customer.Orders'}: #{$order.details.id}</h2>
        </div>
      </div>

      <div class="order-detail">

        <div class="row">
        <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xxl-3">
          <div class="order-details-contain">
            <div class="order-tracking-icon">
              <i class="fa-solid fa-calendar-days"></i>
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
              <i class="fa-solid fa-credit-card-blank"></i>
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

        {if isset($order.history) && $order.history|@count > 0}

          <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xxl-12">
            <div class="section-t-space section-b-space">
              <div class="mb-4">
                <h5 class="mb-0">{l s='History title' d='Shop.Customer.Orders'}</h5>
                <p class="mb-0">{l s='History description' d='Shop.Customer.Orders'}</p>
              </div>
              <div class="order-table-section">
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
          </div>
        {/if}

        {if isset($order.shipping.all) && $order.shipping.all|@count > 0}

          <div class="col-md-12 col-sm-12 section-b-space">
            <div class="mb-4">
              <h5 class="mb-0">{l s='Tracking title' d='Shop.Customer.Orders'}</h5>
              <p class="mb-0">{l s='Tracking description' d='Shop.Customer.Orders'}</p>
            </div>

            <div class="order-table-section">
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
                      <td>{if isset($line.fenvio)}{$line.fenvio}{/if}</td>
                      <td>{if isset($line.description)}{$line.description}{else}-{/if}</td>
                      <td>{if isset($line.codtracking)}{$line.codtracking}{else}-{/if}</td>
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

  </div>
{/block}
