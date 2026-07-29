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
{foreach $list as $cart_rule}
	<tr class="conf_body">
		<td bgcolor="#ffffff" colspan="4" style="border: 0; color: #000000; padding: 7px 0">
			<table class="table" style="width: 100%; border-collapse:collapse">
				<tr>
					<td width="5" style="color: #000000; padding:0"></td>
					<td align="right" style="color: #000000; padding:0">
						<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
							<strong>{$cart_rule['voucher_name']}</strong>
						</font>
					</td>
					<td width="5" style="color: #000000; padding: 0"></td>
				</tr>
			</table>
		</td>
		<td bgcolor="#ffffff" colspan="4" style="border: 0; color: #000000; padding: 7px 0">
			<table class="table" style="width: 100%; border-collapse:collapse">
				<tr>
					<td width="5" style="color: #000000; padding:0"></td>
					<td align="right" style="color: #000000; padding:0">
						<font size="2" face="Open-sans, sans-serif" color="#000000" style="font-family: Open-sans, sans-serif; color: #000000; font-size: 14px; font-weight: 400;">
							{$cart_rule['voucher_reduction']}
						</font>
					</td>
					<td width="5" style="color: #000000; padding: 0"></td>
				</tr>
			</table>
		</td>
	</tr>
{/foreach}
