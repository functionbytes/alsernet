
<div class="product-action-vertical">
	<a class="wishlist-button-stick btn-product btn-primary btn{if $added_wishlist} delete-to-wishlist {else} add-to-wishlist {/if}" href="javascript:void(0)" data-id-wishlist="{$id_wishlist}" data-id-product="{$id_product}" data-id-product-attribute="{$id_product_attribute}" title="{if $added_wishlist}{l s='Remove from Wishlist' mod='alsernetcustomer'}{else}{l s='Add to Wishlist' mod='alsernetcustomer'}{/if}">
		{if $added_wishlist}
			<i class="fa-solid fa-heart"></i>
		{else}
			<i class="fa-light fa-heart"></i>
		{/if}
	</a>
</div>


