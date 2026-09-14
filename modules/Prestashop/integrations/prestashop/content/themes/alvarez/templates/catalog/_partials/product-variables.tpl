{assign var="request_price" value=null}
{assign var="product_type" value=null}
{assign var="show_price_from" value=null}
{assign var="phone_sale" value=null}
{assign var="text_unsaleable_products" value=null}

{foreach from=$product.grouped_features item=feature}
  {assign var="feature`$feature.id_feature`" value=['name'=>{$feature.name}, 'value'=>{$feature.value|escape:'htmlall'|nl2br nofilter}]}

  {if $feature.id_feature == Configuration::get('BAN_PRODUCT_FEATURE_REQUEST_PRICE')}
    {assign var="request_price" value=['name'=>{$feature.name}, 'value'=>{$feature.value|escape:'htmlall'|nl2br nofilter}]}
  {/if}

  {if $feature.id_feature == Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE')}
      {assign var="product_type" value=['name'=>{$feature.name}, 'value'=>{$feature.value|escape:'htmlall'|nl2br nofilter}]}
  {/if}

  {if $feature.id_feature == Configuration::get('BAN_PRODUCT_FEATURE_SHOW_PRICE_FROM')}
    {assign var="show_price_from" value=['name'=>{$feature.name}, 'value'=>{$feature.value|escape:'htmlall'|nl2br nofilter}]}
  {/if}

  {if $feature.id_feature == Configuration::get('BAN_PRODUCT_FEATURE_ID_PHONE_SALE')}
    {assign var="phone_sale" value=['name'=>{$feature.name}, 'value'=>{$feature.value|escape:'htmlall'|nl2br nofilter}]}
  {/if}

  {if $feature.id_feature == Configuration::get('BAN_PRODUCT_FEATURE_ID_TEXT_UNSALEABLE_PRODUCTS')}
    {assign var="text_unsaleable_products" value=['name'=>{$feature.name}, 'value'=>{$feature.value|escape:'htmlall'|nl2br nofilter}]}
  {/if}

  {if $feature.id_feature == Configuration::get('BAN_PRODUCT_FEATURE_ID_FORCE_SALE')}
    {assign var="force_sale" value=['name'=>{$feature.name}, 'value'=>{$feature.value|escape:'htmlall'|nl2br nofilter}]}
  {/if}
{/foreach}

{* no permitir la venta porque esta agotado -> stock < 0 && no permitir compra sin stock *}
{assign var="product_unavailable" value=0}
{if StockAvailable::getQuantityAvailableByProduct($product.id_product, 0) < 1 && !$product.allow_oosp}
  {assign var="product_unavailable" value=1}
{/if}

{* precio 0 *}
{assign var="product_price_zero" value=0}
{if isset($product.price_amount) && (!$product.price_amount || $product.price_amount == 0)}
  {assign var="product_price_zero" value=1}
{/if}

{* mostrar precio *}
{assign var="custom_show_price" value=1}
{if $product_price_zero}
  {assign var="custom_show_price" value=0}
{/if}
{if isset($request_price) && $request_price.value}
  {assign var="custom_show_price" value=0}
{/if}
{if isset($phone_sale) && $phone_sale.value}
  {assign var="custom_show_price" value=0}
{/if}

{* mostrar botón comprar por marca *}
{assign var="custom_show_add_basket_btn" value=1}
{if !isset($force_sale) || empty($force_sale.value)}
    {if isset($product.id_manufacturer) && $product.id_manufacturer}
        {assign var='list_of_manufacturers' value=','|cat:Configuration::get('BAN_MANUFACTURER_ID_LIST_NOT_ALLOW_ORDER')|cat:','}
        {assign var='product_manufacturer' value=','|cat:$product.id_manufacturer|cat:','}
        {if $list_of_manufacturers|strstr:$product_manufacturer}
            {assign var="custom_show_add_basket_btn" value=0}
            {assign var="phone_sale" value=['name'=>'', 'value'=>'1']}

            {* ver precio para ping *}
            {assign var="custom_show_price" value=1}

        {/if}
    {/if}
{/if}
{if isset($text_unsaleable_products) && $text_unsaleable_products} {* no comparo valores porque no tenemos ejemplos de esta caracteristica, de momento lo dejamos con que exista la caracteristica y cuando tengamos ejemplos de valores lo rellenamos. RELLENAR TAMBIÉN EN product-add-to-cart.tpl *}
  {assign var="custom_show_add_basket_btn" value=0}
{/if}
{*
{if Context::getContext()->isAppiOS() }
  {assign var='features' value=Product::getFeaturesStatic((int)$product.id)}
  {foreach from=$features item='featureitem'}
      {if $featureitem.id_feature == Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE')}
          {if $featureitem.id_feature_value==Configuration::get('BAN_PRODUCT_FEATURE_VALUE_PRODUCT_TYPE_WEAPON')}
            {assign var="custom_show_add_basket_btn" value=0}
          {/if}
      {/if}
  {/foreach}
{/if}
*}
{* AQUÍ TODAS LAS VARIABLES QUE NECESITE UTILIZAR FUERA DE ESTE TPL *}
{assign var='request_price' value=$request_price scope='global'}
{assign var='product_type' value=$product_type scope='global'}
{assign var='show_price_from' value=$show_price_from scope='global'}
{assign var='phone_sale' value=$phone_sale scope='global'}
{assign var='product_unavailable' value=$product_unavailable scope='global'}
{assign var='product_price_zero' value=$product_price_zero scope='global'}
{assign var='custom_show_price' value=$custom_show_price scope='global'}
{assign var='custom_show_add_basket_btn' value=$custom_show_add_basket_btn scope='global'}
{assign var='text_unsaleable_products' value=$text_unsaleable_products scope='global'}