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
<section id="content" style="max-width: none;" class="page-content page-not-found">
  {block name='page_content'}
    <div class="content not-found">
        <b style="color: #FF3100; font-size: 18px;font-weight: 900;">{l s='No inventaries found' d='Shop.Theme.Catalog'}</b>
    </div>
    <br>

    <a class="btn" href="{$urls.base_url}" style="background: #90BB13;">{l s='Back to Home' d='Shop.Theme.Global'}</a>
    <br><br><br><br>
    <!-- retail rocket -->
    {widget name='retailrocket' hook='displayRetailRocketSearchPageEmptyPersonal'}
  {/block}
</section>
