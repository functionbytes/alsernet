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
{if isset($smarty.get.message_send) && $smarty.get.message_send == 1}
<aside id="notifications">
  <div class="container">
    <article class="alert alert-success" role="alert" data-alert="success">
      <ul>
        <li>{l s='Message successfully sent' mod='wkrma'}</li>
      </ul>
    </article>
  </div>
</aside>
{/if}
<div class="wk-rma-content">
  {if isset($guidelineMessage)}
    {include file='module:wkrma/views/templates/front/_partials/rma-guideline.tpl'}
  {/if}

  {block name='wk-rma-request-product-detail'}
    {include file='module:wkrma/views/templates/front/_partials/request-product-detail.tpl'}
  {/block}

  {block name='wk-rma-request-status'}
    {include file='module:wkrma/views/templates/front/_partials/status-bar.tpl'}
  {/block}
  {block name='wk-rma-request-history-info'}
    {include file='module:wkrma/views/templates/front/_partials/history.tpl'}
  {/block}
</div>