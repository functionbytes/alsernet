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

<div class="row">
  <div class="col-sm-12">
    {block name='wk-rma-search-order'}
      <div class="row wk-rma-panel-box">
        <div class='wk-rma-order-filter row'>
          <label class="control-label col-lg-3">
            <span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Search for an existing order by typing the order reference.' mod='wkrma'}">
              {l s='Search for an order' mod='wkrma'}
            </span>
          </label>
          <div class="col-lg-6">
            <div class="row">
            <input class="form-control" size="10" type="text" placeholder="{l s='Search order by reference number' mod='wkrma'}" aria-label="Search" name='rma-order-search' id='rma-order-search' />
            </div>
            <div class="row mt-1 mb-1">
              <div class="col-lg-12" style="text-align:center">
                <span class="text-center">{l s='OR' mod='wkrma'}</span>
              </div>
            </div>
            <div class="row">
              {* <div class="col-lg-6"> *}
                <input class="form-control" size="10" type="text" placeholder="{l s='Search order by product name' mod='wkrma'}" aria-label="Search" name='rma-orderProduct-search' id='rma-orderProduct-search' />
              {* </div> *}
            </div>
          </div>
        </div>
        <div class="form-group row wk-hidden">
          <div class='table-responsive wk-rma-order-table'>
            <table class="table">
              <thead>
                <tr>
                  <th>{l s='Order reference' mod='wkrma'}</th>
                  <th>{l s='Order date' mod='wkrma'}</th>
                  <th>{l s='Price' mod='wkrma'}</th>
                </tr>
              </thead>
              <tbody id="add-order-list">
              </tbody>
            </table>
          </div>
        </div>
      </div>
    {/block}
    {block name='wk-rma-search-product'}
      <div class="wk-hidden row wk-rma-panel-box">
        <div id='wk-rma-order-request-detail'>
        </div>
      </div>
    {/block}
  </div>
</div>