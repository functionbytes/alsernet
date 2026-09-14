<a  href="javascript:void(0)" class="product-wishlist-btn {if $added_wishlist} delete-to-wishlist {else} add-to-wishlist {/if}"
    data-id-wishlist="{$wishlist_id}"
    data-id-product="{$wishlist_id_product}"
    data-id-product-attribute="{$wishlist_id_product_attribute}"
    title="{if $added_wishlist}{l s='Remove from Wishlist' mod='alsernetcustomer'}{else}{l s='Add to Wishlist' mod='alsernetcustomer'}{/if}">
        {if $added_wishlist}
                <i class="fa-solid fa-heart"></i>
        {else}
                <i class="fa-light fa-heart"></i>
        {/if}
</a>