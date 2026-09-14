{**
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
 
<section class="category-products block clearfix {if isset($productClassWidget)} {$productClassWidget}{/if}">
  <h5 class="products-section-title">
    {l s='Products similar to [product]' sprintf=['[product]' => $product.name] d='Shop.Theme.Catalog'}
  </h5>
  <div class="block_content">
    <div class="products">
      <div class="owl-row">
        <div id="category-products" class="owl-carousel owl-theme owl-loading">
          {foreach from=$products item="product" name=mypLoop}
            <div class="item{if $smarty.foreach.mypLoop.index == 0} first{/if}">
              {block name='product_miniature'}
                {if isset($productProfileDefault) && $productProfileDefault}
                  {* exits THEME_NAME/profiles/profile_name.tpl -> load template*}
                  {hook h='displayLeoProfileProduct' product=$product profile=$productProfileDefault}
                {else}
                  {include file='catalog/_partials/miniatures/product.tpl' product=$product}
                {/if}
              {/block}
            </div>
          {/foreach}
        </div>
      </div>
    </div>
  </div>
</section>