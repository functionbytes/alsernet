{*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License version 3.0
* that is bundled with this package in the file LICENSE.txt
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to CustomizationPolicy.txt file inside our module for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
*}

{if isset($orders) && $orders|@count > 0}
  {foreach $orders as $order}
    <tr class='wk-add-order' data-id-order="{$order['id_order']}">
      <td>{$order['reference']}</td>
      <td>{dateFormat date=$order['date_add']|escape:'html':'UTF-8'}</td>
      <td>{$order['display_price']}</td>
      <td class="text-sm-center order-actions">
        <a href="{$link->getPageLink('refundrequest', true)}?id_order={$order['id_order']}&token={$token}" class='wk-add-rma-order-request' >
          {l s='Details' mod='wkrma'}
        </a>
      </td>
    </tr>
  {/foreach}
{else}
  <tr>
      <td colspan="4" class="center"><div class="alert alert-danger"> {l s='No product found for dispute on this order . Please contact to admin to create RMA request' mod='wkrma'}</div></td>
  </tr>
{/if}