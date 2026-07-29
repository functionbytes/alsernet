<a class="wishlist label-down link d-xs-show" href="{$link->getPageLink('wishlist', true)}">
   <i class="fa-light fa-heart">
      <span class="wishlist-count {if $isloggedwishlist == false} d-none {/if}">{$wishlist_product}</span>
   </i>
   <span class="wishlist-label d-lg-show">{l s='Wishlist' mod='alsernetcustomer'}</span>
</a>
