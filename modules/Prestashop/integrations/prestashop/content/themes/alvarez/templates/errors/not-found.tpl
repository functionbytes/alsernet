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
  {*{block name='page_content'}*}
  <div class="gone_main" style="margin-top: 5rem; display:flex; flex-direction: column; justify-content: center; align-items: center; font-family: Barlow;">
    <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 20px">
        <h2 id="gone" style="color:#333333; font-weight: bold; ">{$product.name}</h2>
        <h3 id="sub_gone" style="color:#FF3100; text-transform: uppercase;font-size: 1.25rem; opacity: 1; font-weight: bolder;">Lo sentimos, este producto ya no está disponible</h3>
    </div>
    <div style="margin-bottom: 7rem;">
        <a class="btn" id="gone_btn" style="margin-top: 22px; background-color:#90BB13; display: flex; flex-direction: row; align-items: center; justify-content: center; font-weight: bold; font-size: 0.85rem;" href="{$urls.base_url}">{l s='Back to Home' d='Shop.Theme.Global'}</a>
    </div>
  </div>

    {widget name='retailrocket' hook='displayRetailRocket404PagePersonal'}


{*
    {block name='search'}
      {hook h='displaySearch'}
    {/block}

    {block name='hook_not_found'}
      {hook h='displayNotFound'}
    {/block}
*}


  {*{/block}*}

