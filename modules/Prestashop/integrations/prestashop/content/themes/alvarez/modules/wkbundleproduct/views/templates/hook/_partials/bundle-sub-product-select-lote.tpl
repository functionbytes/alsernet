{**
* 2010-2021 Webkul.
*
* NOTICE OF LICENSE
*
* All right is reserved,
* Please go through LICENSE.txt file inside our module
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize this module for your
* needs please refer to CustomizationPolicy.txt file inside our module for more information.
*
* @author Webkul IN
* @copyright 2010-2021 Webkul IN
* @license LICENSE.txt
*}

<div class="wk-bundle-product-select" style="margin-top:-5px;margin-left:5px;">
       

        <button {if $themeType == 'list'} style="display:none;"{/if} class="btn btn-secondary wk-select-sub-product wk-select-sub-product_{$product.id}_{$sections.id_wk_bundle_section} wk_select_list_{$product.id}_{$sections.id_wk_bundle_section}" data-id-product="{$product.id}" data-id-product-attribute="{$product.id_product_attribute}" data-id-section="{$sections.id_wk_bundle_section}" data-theme_type = {$themeType} data-is_selected = "0" data-is_required = '0' data-id-ps-product ="{$idpsproduct}" {if $product.product_groups['groups'] && $product.product_groups['groups']|count == 0}disabled{/if}>
           <span>{l s="Select" d="Shop.Theme.Global"} {*{l s='Add' mod='wkbundleproduct'}*}</span>
        </button>
    
</div>


