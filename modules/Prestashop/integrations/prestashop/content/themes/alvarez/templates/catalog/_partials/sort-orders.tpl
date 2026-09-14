<div class="col-md-12 col-xs-12 col-sp-12 ">
  <div class="sort-order-wrapper">
    <div class="row">
      <span class="col-sp-6 col-xs-6 col-sm-6 col-md-6 col-lg-6  sort-by">{l s='Sort by:' d='Shop.Theme.Global'}</span>
      <div class="col-sp-6 col-xs-6 col-sm-6 col-md-6 col-lg-6 products-sort-order dropdown">
        <button
          class="btn-unstyle select-title"
          rel="nofollow"
          data-toggle="dropdown"
          aria-label="{l s='Sort by selection' d='Shop.Theme.Global'}"
          aria-haspopup="true"
          aria-expanded="false">
          {if $listing.sort_selected}{$listing.sort_selected}{else}{l s='Select' d='Shop.Theme.Actions'}{/if}
          <i class="material-icons float-xs-right">&#xE5C5;</i>
        </button>
        <div class="dropdown-menu">
          {foreach from=$listing.sort_orders item=sort_order}
            <a
              rel="nofollow"
              href="{$sort_order.url}"
              class="select-list {['current' => $sort_order.current, 'js-search-link' => true]|classnames}">
              {l s=$sort_order.label d='Shop.Theme.Catalog'}
            </a>
          {/foreach}
        </div>
      </div>
    </div>
  </div>
</div>