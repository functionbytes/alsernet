
{extends file='customer/page.tpl'}


{block name='page_content'}

  <button class="btn btn-dashboard-show mb-4 d-lg-none">
    {l s='Show menu' d='Shop.Customer.Dashboard'}
  </button>

  <div class="dashboard-right-sidebar">
    <div class="dashboard-home">

      <div class="dashboard-title d-flex justify-content-between align-items-center">
        <div class="title">
          <h2>{l s='Your account' d='Shop.Customer.Dashboard'}</h2>
        </div>
      </div>

      <div class="total-box row">

        <div class="col-12 col-sm-12 col-md-4 col-lg-4 col-xxl-4">

          <a class="total-contain" href="{$link->getPageLink('history', true)}">
            <i class="fa-light fa-bag-shopping"></i>
            <div class="total-detail">
              <h5>{l s='Total order' d='Shop.Customer.Dashboard'}</h5>
              <h3>{$orders_count}</h3>
            </div>
          </a>
        </div>

        <div class="col-12 col-sm-12 col-md-4 col-lg-4 col-xxl-4">

          <a class="total-contain" href="{$link->getPageLink('wishlist', true)}">
            <iclass="fa-light fa-heart"></i>
            <div class="total-detail">
              <h5>{l s='Total wishlist' d='Shop.Customer.Dashboard'}</h5>
              <h3>{$wishlist_count}</h3>
            </div>
          </a>
        </div>

        <div class="col-12 col-sm-12 col-md-4 col-lg-4 col-xxl-4">

          <a class="total-contain" href="{$link->getPageLink('addresses', true)}">
            <i class="fa-light fa-location-dot"></i>
            <div class="total-detail">
              <h5>{l s='Total address' d='Shop.Customer.Dashboard'}</h5>
              <h3>{$addresses_count}</h3>
            </div>
          </a>
        </div>
      </div>


    </div>
  </div>
{/block}

{block name='page_rocket'}
  {hook h='displayCustomerAccount'}
{/block}
