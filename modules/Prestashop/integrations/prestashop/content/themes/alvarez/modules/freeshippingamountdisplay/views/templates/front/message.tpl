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

{if $amountLeft > 0}
	<div class="shipping-amount-display clearfix custom {*$position|escape:'htmlall':'UTF-8'} {if $position == 'product' && isset($freeShippingText) && $freeShippingText}has-free-shipping{/if*}">
		<div class="inner">
			<p class="text">
				<span class="line">
					<span class="truck"><img src="/img/propias/Camion.svg"><!--<i class="fa fa-truck"></i>--></span>
				</span>
				<span class="text-inner">
					{if $has_special_products}
						<strong>{l s='[span][price][/span] missing to reduce shipping costs' sprintf=['[price]' => $amountLeftDisplay, '[span]' => '<span class="price">', '[/span]' => '</span>'] d='Shop.Theme.Checkout'}</strong>
					{else}
						<strong>{l s='[span][price][/span] missing for free shipping' sprintf=['[price]' => $amountLeftDisplay, '[span]' => '<span class="price">', '[/span]' => '</span>'] d='Shop.Theme.Checkout'}</strong>
					{/if}
				</span>
			</p>
		</div>
	</div>
{/if}