{**
* 2012-2021 INNERCODE
*
* NOTICE OF LICENSE
*
* This source file is subject to the EULA (End User License Agreement)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://www.innercode.lt/ps-module-eula.txt
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to info@innercode.lt so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future.
*
* @author    Innercode
* @copyright Copyright (c) 2012 - 2021 INNERCODE, UAB. (https://www.innercode.lt)
* @license   https://www.innercode.lt/ps-module-eula.txt
* @package   freeshippingamountdisplay
* @site      https://www.innercode.lt
*}

<div class="shipping-amount-display clearfix custom {*$position|escape:'htmlall':'UTF-8'} {if $position == 'product' && isset($freeShippingText) && $freeShippingText}has-free-shipping{/if*}">
	<div class="inner">
		{assign var='freeshipping_price' value=Configuration::get('PS_SHIPPING_FREE_PRICE')}
		{if $freeshipping_price}

			{if $cart.totals.total.amount}
				{math equation='a-b' a=$cart.totals.total.amount b=$cart.subtotals.shipping.amount assign='total_without_shipping'}
				{math equation='a-b' a=$freeshipping_price b=$total_without_shipping assign='remaining_to_spend'}
			{else}
				{math equation='a-b' a=$cart_sumario.total_price b=$cart_sumario.total_shipping assign='total_without_shipping'}
				{math equation='a-b' a=$freeshipping_price b=$cart_sumario.total_products assign='remaining_to_spend'}
			{/if}

			{if $remaining_to_spend > 0}
				<p class="text">
					<span class="line">
						<span class="truck"><img src="/img/propias/Camion.svg"><!--<i class="fa fa-truck"></i>--></span>
					</span>
					<span class="text-inner">
						{assign var='freeshipping_price' value=Configuration::get('PS_SHIPPING_FREE_PRICE')}
						{assign var='price_get_free_shipping' value=$freeshipping_price|intval}
						{if $freeshipping_price}
						    {math equation='a-b' a=$cart.totals.total.amount b=$cart.subtotals.shipping.amount assign='total_without_shipping'}
						    {math equation='a-b' a=$freeshipping_price b=$total_without_shipping assign='remaining_to_spend'}
						    {if $remaining_to_spend > 0}
						      	{assign var=currency value=Context::getContext()->currency}
								{*{Tools::displayPrice($remaining_to_spend,$currency)}*}
								{assign var='price_get_free_shipping' value=Tools::displayPrice($remaining_to_spend, $currency)}
						    {/if}
						{/if}
						<strong>{l s='[span][price][/span] missing for free shipping' sprintf=['[price]' => $price_get_free_shipping, '[span]' => '<span class="price">', '[/span]' => '</span>'] d='Shop.Theme.Checkout'}</strong>
						<br>
						<span>{$messageText nofilter}</span>

						{*<strong>{l s='Faltan' mod='freeshippingamountdisplay'} 
						<span class="price">
							{assign var='freeshipping_price' value=Configuration::get('PS_SHIPPING_FREE_PRICE')}
							{if $freeshipping_price}
							    {math equation='a-b' a=$cart.totals.total.amount b=$cart.subtotals.shipping.amount assign='total_without_shipping'}
							    {math equation='a-b' a=$freeshipping_price b=$total_without_shipping assign='remaining_to_spend'}
							    {if $remaining_to_spend > 0}
							      	{assign var=currency value=Context::getContext()->currency}
									{Tools::displayPrice($remaining_to_spend,$currency)}
							    {/if}
							{/if}
						</span> {l s='para envío gratuito' mod='freeshippingamountdisplay'}</strong>
						<br>
						<span>{$messageText nofilter}</span>*}
					</span>
				</p>
			{/if}
		{/if}
	</div>
</div>

<!--
<div class="row shipping-amount-display {if ! $amountLeft}fsad-free{/if} {$position|escape:'htmlall':'UTF-8'}">
	<div class="inner">
		<p class="text">
			<span class="line">
				<span class="filled-line" style="width: {$percentage|floatval}%">
					<span class="truck"><i class="fa fa-truck"></i></span>
				</span>
			</span>

			{if isset($messageText) && $messageText}
				<strong>{$messageText nofilter}</strong>
			{else}
				<strong>{l s='Left until a free shipping' mod='freeshippingamountdisplay'}:</strong>
				<span class="price">{$amountLeftDisplay|escape:'htmlall':'UTF-8'}</span>
			{/if}
		</p>
	</div>
</div>
-->