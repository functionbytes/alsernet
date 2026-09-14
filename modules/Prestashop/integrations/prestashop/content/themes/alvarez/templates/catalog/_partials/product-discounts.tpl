{**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}



  <section class="product-discounts js-product-discounts">

    {block name='product_discount_table'}
        {if isset($cantidades) && $cantidades != ''}



      {foreach from=$product.quantity_discounts item='quantity_discount' name='quantity_discounts'}

            {if !in_array($quantity_discount.quantity, $cantidades)}
                  {capture append="cantidades"}{$quantity_discount.quantity}{/capture}
            {/if}




      {/foreach}

      {foreach $cantidades as $cantidad}

          {assign var='price_discount_unit' value=Product::getPriceStatic($product.id_product|intval, true, $product.id_product_attribute|intval, 6, null, false, true, $cantidad)}
           <div class="product-discounts-line">{l s='[price]/unit buying [quantity] units' sprintf=['[price]' => Tools::displayPrice($price_discount_unit), '[quantity]' => $cantidad] d='Shop.Theme.Catalog'}</div>


       {/foreach}

       {/if}

      {*
      {foreach from=$product.quantity_discounts item='quantity_discount' name='quantity_discounts'}
        {assign var='discount_quantity_from' value=$quantity_discount.quantity}
        {assign var='price_discount_unit' value=Product::getPriceStatic($product.id_product|intval, true, $product.id_product_attribute|intval, 6, null, false, true, $discount_quantity_from)}

        {if $product.id_product_attribute==$quantity_discount.id_product_attribute}

        <div class="product-discounts-line">{l s='[price]/unit buying [quantity] units' sprintf=['[price]' => Tools::displayPrice($price_discount_unit), '[quantity]' => $discount_quantity_from] d='Shop.Theme.Catalog'}</div>
        {/if}


      {/foreach}
      *}
    {/block}
  </section>
