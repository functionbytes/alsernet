{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
{foreach $list as $product}
<tr>
	<td style="border: 0;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td>
					<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
						{$product['reference']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
	<td style="border: 0;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td>
					<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
						<strong>{$product['name'] nofilter}</strong>
						{if count($product['customization']) == 1}
							<br>
							{foreach $product['customization'] as $customization}
								{$customization['customization_text'] nofilter}
							{/foreach}
						{/if}

						{hook h='displayProductPriceBlock' product=$product type="unit_price"}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
	<td style="border: 0;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td align="right">
					<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
						{$product['unit_price']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
	<td style="border: 0;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td align="right">
					<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
						{$product['quantity']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
	<td style="border: 0;">
		<table class="table">
			<tr>
				<td width="5">&nbsp;</td>
				<td align="right">
					<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
						{$product['price']}
					</font>
				</td>
				<td width="5">&nbsp;</td>
			</tr>
		</table>
	</td>
</tr>
  {if count($product['customization']) > 1}
  	{foreach $product['customization'] as $customization}
  		<tr>
  		<td colspan="3" style="border: 0;">
  			<table class="table">
  				<tr>
  					<td width="5">&nbsp;</td>
  					<td>
  						<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
  							{$customization['customization_text']}
  						</font>
  					</td>
  					<td width="5">&nbsp;</td>
  				</tr>
  			</table>
  		</td>
  		<td style="border: 0;">
  			<table class="table">
  				<tr>
  					<td width="5">&nbsp;</td>
  					<td align="right">
  						<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
  							{if count($product['customization']) > 1}
  								{$customization['customization_quantity']}
  							{/if}
  						</font>
  					</td>
  					<td width="5">&nbsp;</td>
  				</tr>
  			</table>
  		</td>
  		<td style="border: 0;"></td>
  	</tr>
  	{/foreach}
  {/if}
{/foreach}
