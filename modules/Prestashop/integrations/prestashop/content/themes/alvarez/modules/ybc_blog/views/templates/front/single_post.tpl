{*
* 2007-2022 ETS-Soft
*
* NOTICE OF LICENSE
*
* This file is not open source! Each license that you purchased is only available for 1 wesite only.
* If you want to use this file on more websites (or projects), you need to purchase additional licenses.
* You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs, please contact us for extra customization service at an affordable price
*
*  @author ETS-Soft <etssoft.jsc@gmail.com>
*  @copyright  2007-2022 ETS-Soft
*  @license    Valid for 1 website (or project) for each purchase of license
*  International Registered Trademark & Property of ETS-Soft
*}
{extends file="page.tpl"}
{block name='product_activation'}
{if $blog_post.enabled==-2 && isset($smarty.get.preview)}
    <div class="alert alert-warning row" role="alert">
        <div class="container">
          <div class="row">
                <div class="col-sm-12">
                    <i class="material-icons float-xs-left icon-preview">&#xE001;</i>
                    <p class="alert-text">{l s='This post is not visible to your customers.' mod='ybc_blog'}</p>
              </div>
          </div>
        </div>
    </div>
{/if}
{/block}
{block name="content"}



<div class="row">
    {if isset($blog_config.YBC_BLOG_SIDEBAR_POSITION) && $blog_config.YBC_BLOG_SIDEBAR_POSITION=='left'}
        <div id="left-column" class="col-xs-12 col-sm-4 col-md-3">
          {hook h="blogSidebar"}
        </div>
    {/if}
<div id="content-wrapper" class="{if isset($blog_config.YBC_BLOG_SIDEBAR_POSITION) && $blog_config.YBC_BLOG_SIDEBAR_POSITION=='left'}left-column col-xs-12 col-sm-8 col-md-9{elseif isset($blog_config.YBC_BLOG_SIDEBAR_POSITION) && $blog_config.YBC_BLOG_SIDEBAR_POSITION=='right'}right-column col-xs-12 col-sm-8 col-md-9{/if}">
    {if $blog_config.YBC_BLOG_CAPTCHA_TYPE=='google3'}
        <script src="https://www.google.com/recaptcha/api.js?render={$blog_config.YBC_BLOG_CAPTCHA_SITE_KEY3|escape:'html':'UTF-8'}"></script>
    {/if}
    {if isset($blog_post.enabled) && ($blog_post.enabled ==1 || $blog_post.pending==1 || ($blog_post.enabled==-2 && isset($smarty.get.preview))) }
        <script type="text/javascript">
            ybc_blog_report_url = '{$report_url nofilter}';
            ybc_blog_report_warning ="{l s='Do you want to report this comment?' mod='ybc_blog'}";
            ybc_blog_error = "{l s='There was a problem while submitting your report. Try again later' mod='ybc_blog'}";
            ybc_blog_delete_comment ="{l s='Do you want to delete this comment?' mod='ybc_blog'}";
            prettySkin = '{$prettySkin|addslashes|escape:'html':'UTF-8'}';
            var placeholder_reply= "{l s='Enter your message...' mod='ybc_blog'}";
            prettyAutoPlay = false;
            var number_product_related_per_row ={$blog_config.YBC_BLOG_RELATED_PRODUCT_ROW|intval};
            var number_post_related_per_row ={$blog_config.YBC_BLOG_RELATED_POST_ROW|intval};
        </script>
        {if $blog_post.enabled==-1}
            <div class="alert alert-warning">
                {l s='Your post is in preview process, it will be published once our moderator approve it' mod='ybc_blog'}
            </div>
        {/if}
        <div class="ybc_blog_layout_{$blog_layout|escape:'html':'UTF-8'} ybc-blog-wrapper-detail" itemscope itemType="http://schema.org/newsarticle">
            <div itemprop="publisher" itemtype="http://schema.org/Organization" itemscope="">
                <meta itemprop="name" content="{Configuration::get('PS_SHOP_NAME')|escape:'html':'UTF-8'}" />
                {if Configuration::get('PS_LOGO')}
                    <div itemprop="logo" itemscope itemtype="http://schema.org/ImageObject">
                        <meta itemprop="url" content="{$blog_config.YBC_BLOG_SHOP_URI|escape:'html':'UTF-8'}img/{Configuration::get('PS_LOGO')|escape:'html':'UTF-8'}" />
                        <meta itemprop="width" content="200px" />
                        <meta itemprop="height" content="100px" />
                    </div>
                {/if}
            </div>
            {if $blog_post.image}
                <div class="ybc_blog_img_wrapper" itemprop="image" itemscope itemtype="http://schema.org/ImageObject">
                    {if $enable_slideshow}<a href="{$blog_post.image|escape:'html':'UTF-8'}" class="prettyPhoto">{/if}
                    <div class="ybc_image-single{if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD} ybc_item_img_ladyload{/if}">
                        <img title="{$blog_post.title|escape:'html':'UTF-8'}" src="{if isset($blog_config.YBC_BLOG_LAZY_LOAD) && $blog_config.YBC_BLOG_LAZY_LOAD}{$link->getMediaLink("`$smarty.const._MODULE_DIR_`ybc_blog/views/img/bg-grey.png")|escape:'html':'UTF-8'}{else}{$blog_post.image|escape:'html':'UTF-8'}{/if}" alt="{$blog_post.title|escape:'html':'UTF-8'}" itemprop="url" {if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD} data-original="{$blog_post.image|escape:'html':'UTF-8'}" class="lazyload"{/if}/>
                    </div>
                    <meta itemprop="width" content="600px" />
                    <meta itemprop="height" content="300px" />
                    {if $enable_slideshow}</a>{/if}
                </div>
             {/if}
             <div class="ybc-blog-wrapper-content">
            {if $blog_post}
                <h1 class="page-heading product-listing" itemprop="mainEntityOfPage"><span  class="title_cat" itemprop="headline">{$blog_post.title|escape:'html':'UTF-8'}</span></h1>

		{foreach from=$blog_post.categories item='cat'}
			{if $cat.id_category==$blog_post.id_category_default}
                        <h2><a href="{$cat.link|escape:'html':'UTF-8'}">{ucfirst($cat.title)|escape:'html':'UTF-8'}</a></h2>
			{/if}
               {/foreach}


		<h2></h2>

                <div class="post-details">
                    <div class="blog-extra">
                        <div class="ybc-blog-latest-toolbar">
                            {if $show_views}
                                <span title="{l s='Page views' mod='ybc_blog'}" class="ybc-blog-latest-toolbar-views">
                                    {$blog_post.click_number|intval}
                                    {if $blog_post.click_number != 1}
                                        <span>{l s='Views' mod='ybc_blog'}</span>
                                    {else}
                                        <span>{l s='View' mod='ybc_blog'}</span>
                                    {/if}
                                </span>
                            {/if}
                            {if $allow_like}
                                <span title="{if $likedPost}{l s='Unlike this post' mod='ybc_blog'}{else}{l s='Like this post' mod='ybc_blog'}{/if}" class="ybc-blog-like-span ybc-blog-like-span-{$blog_post.id_post|intval}{if $likedPost} active{/if}"  data-id-post="{$blog_post.id_post|intval}">
                                    <span class="ben_{$blog_post.id_post|intval}">{$blog_post.likes|intval}</span>
                                    <span class="blog-post-like-text blog-post-like-text-{$blog_post.id_post|intval}"><span>{l s='Liked' mod='ybc_blog'}</span></span>
                                </span>
                            {/if}
                            {if $allow_rating && $everage_rating}
                                <div class="blog_rating_wrapper">
                                    {if $total_review}
                                        <span title="{l s='Comments' mod='ybc_blog'}" class="blog_rating_reviews">
                                             <span class="total_views">{$total_review|intval}</span>
                                             <span>
                                                {if $total_review != 1}
                                                    {l s='Comments' mod='ybc_blog'}
                                                {else}
                                                    {l s='Comment' mod='ybc_blog'}
                                                {/if}
                                            </span>
                                        </span>
                                    {/if}
                                    <div title="{l s='Average rating' mod='ybc_blog'}" class="ybc_blog_review">
                                        {for $i = 1 to $everage_rating}
                                            {if $i <= $everage_rating}
                                                <div class="star star_on"></div>
                                            {else}
                                                <div class="star star_on_{($i-$everage_rating)*10|intval}"></div>
                                            {/if}
                                        {/for}
                                        {if Ceil($everage_rating)<5}
                                            {for $i = ceil($everage_rating)+1 to 5}
                                                <div class="star"></div>
                                            {/for}
                                        {/if}
                                        <span class="ybc-blog-rating-value">({$everage_rating|escape:'html':'UTF-8'})</span>
                                    </div>
                                </div>
                            {/if}
                            {if $show_date}
                                {if !$date_format}{assign var='date_format' value='F jS Y'}{/if}
                                <span class="post-date">
                                    <span class="be-label">{l s='Posted on' mod='ybc_blog'}: </span>
                                    <span>{date($date_format,strtotime($blog_post.datetime_added))|escape:'html':'UTF-8'}</span>
                                    <meta itemprop="datePublished" content="{date('Y-m-d',strtotime($blog_post.datetime_added))|escape:'html':'UTF-8'}" />
                                    <meta itemprop="dateModified" content="{date('Y-m-d',strtotime($blog_post.datetime_modified))|escape:'html':'UTF-8'}" />
                                </span>
                            {/if}
                            {if $show_author && isset($blog_post.employee) &&  $blog_post.employee}
                                <div class="author-block" itemprop="author" itemscope itemtype="http://schema.org/Person">
                                    <span class="post-author-label">{l s='By ' mod='ybc_blog'}</span>
                                    <a itemprop="url" href="{$blog_post.author_link|escape:'html':'UTF-8'}">
                                        <span class="post-author-name" itemprop="name">
                                            {if isset($blog_post.employee.name) && $blog_post.employee.name}
                                                {ucfirst($blog_post.employee.name)|escape:'html':'UTF-8'}
                                            {else}
                                                {ucfirst($blog_post.employee.firstname)|escape:'html':'UTF-8'} {ucfirst($blog_post.employee.lastname)|escape:'html':'UTF-8'}
                                            {/if}
                                        </span>
                                    </a>
                                </div>
                            {/if}
                            {if isset($blog_post.link_edit) && $blog_post.link_edit}
                                <a class="ybc-block-post-edit" href="{$blog_post.link_edit|escape:'html':'UTF-8'}" title="{l s='Edit' mod='ybc_blog'}"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;{l s='Edit' mod='ybc_blog'}</a>
                            {/if}

                        </div>
                        <div class="ybc-blog-tags-social">
                        {if $use_google_share || $use_facebook_share || $use_twitter_share || $blog_config.YBC_BLOG_ENABLE_PINTEREST_SHARE || $blog_config.YBC_BLOG_ENABLE_LIKED_SHARE}
                            <div class="blog-extra-item blog-extra-facebook-share">
                                <ul>
                                    {if $use_facebook_share}
                                        <li class="facebook icon-gray">
                                            <a target="_blank" title="{l s='Share' mod='ybc_blog'}" class="text-hide" href="http://www.facebook.com/sharer.php?u={$post_url|escape:'html':'UTF-8'}">{l s='Share' mod='ybc_blog'}</a>
                                        </li>
                                    {/if}
                                    {if $use_twitter_share}
                                        <li class="twitter icon-gray">
                                            <a target="_blank" title="{l s='Twitter' mod='ybc_blog'}" class="text-hide" href="https://twitter.com/intent/tweet?text={$blog_post.title|escape:'html':'UTF-8'} {$post_url|escape:'html':'UTF-8'}">{l s='Twitter' mod='ybc_blog'}</a>
                                        </li>
                                    {/if}
                                    {if $blog_config.YBC_BLOG_ENABLE_PINTEREST_SHARE}
                                        <li class="pinterest icon-gray">
                                            <a target="_blank" title="{l s='Pinterest' mod='ybc_blog'}" class="text-hide" href="http://www.pinterest.com/pin/create/button/?media={$blog_post.image|escape:'html':'UTF-8'}&url={$post_url|escape:'html':'UTF-8'}">{l s='Pinterest' mod='ybc_blog'}</a>
                                        </li>
                                    {/if}
                                    {if $blog_config.YBC_BLOG_ENABLE_LIKED_SHARE}
                                        <li class="linkedin icon-gray">
                                            <a target="_blank" title="{l s='LinkedIn' mod='ybc_blog'}" class="text-hide" href="https://www.linkedin.com/shareArticle?mini=true&url={$post_url|escape:'html':'UTF-8'}&title={$blog_post.title|escape:'html':'UTF-8'}&summary={$blog_post.short_description|strip_tags:'UTF-8'|truncate:500|escape:'html':'UTF-8'}&source=LinkedIn">{l s='LinkedIn' mod='ybc_blog'}</a>
                                        </li>
                                    {/if}
                                    {if $blog_config.YBC_BLOG_ENABLE_TUMBLR_SHARE}
                                        <li class="tumblr icon-gray">
                                            <a target="_blank" title="{l s='Tumblr' mod='ybc_blog'}" class="text-hide" href="http://www.tumblr.com/share/link?url={$post_url|escape:'html':'UTF-8'}">{l s='Tumblr' mod='ybc_blog'}</a>
                                        </li>
                                    {/if}
                                </ul>
                            </div>
                        {/if}
                    </div>
                    </div>
                    <div class="blog_description{if $enable_slideshow} popup_image{/if}">
                        {if $blog_post.description}
                            {$blog_post.description nofilter}
                        {else}
                            {$blog_post.short_description nofilter}
                        {/if}
                    </div>
                    {if $blog_config.YBC_BLOG_ENABLE_POLLS && $allowPolls}
                        <form>
                            <div class="form-polls">
                                <div class="form-group polls-title noactive">
                                    {if $blog_config.YBC_BLOG_POLLS_TEXT}
                                        <span>{$blog_config.YBC_BLOG_POLLS_TEXT|escape:'html':'UTF-8'}</span>
                                    {else}
                                        <span>{l s='Was this blog post helpful to you?' mod='ybc_blog'}</span>
                                    {/if}
                                    <label for="polls_post_1" {if $polls_class && $polls_class->polls==1}class="disabled"{/if}>
                                        <i class="fa fa-thumbs-o-up"></i> {l s='Yes' mod='ybc_blog'} <span id="polls_post_helpful_yes">({$polls_post_helpful_yes|intval})</span>
                                        <input id="polls_post_1" type="radio" name="polls_post" value="1" {if $polls_class && $polls_class->polls==1}disabled="disabled"{/if}/>
                                    </label>
                                    <label for="polls_post_0" {if $polls_class && $polls_class->polls==0}class="disabled"{/if}>
                                        <i class="fa fa-thumbs-o-down"></i> {l s='No' mod='ybc_blog'}<span id="polls_post_helpful_no">({$polls_post_helpful_no|intval})</span>
                                        <input id="polls_post_0" type="radio" name="polls_post" value="0" {if $polls_class && $polls_class->polls==0}disabled="disabled"{/if}/>
                                    </label>
                                </div>
                                <div class="form-polls-body hidden">
                                    <div class="form-group polls-name">
                                        <input name="polls_name" id="polls_name" placeholder="{l s='Your name' mod='ybc_blog'}" value="{if $polls_customer}{$polls_customer->lastname|escape:'html':'UTF-8'} {$polls_customer->firstname|escape:'html':'UTF-8'}{/if}" {if $polls_customer}readonly="true"{/if}/>
                                    </div>
                                    <div class="form-group polls-email">
                                        <input name="polls_email" id="polls_email" placeholder="{l s='Your email' mod='ybc_blog'}" {if $polls_customer}readonly="true"{/if} value="{if $polls_customer}{$polls_customer->email|escape:'html':'UTF-8'}{/if}"/>
                                    </div>
                                    <div class="form-group polls-feedback">
                                        <textarea name="polls_feedback" id="polls_feedback" placeholder="{l s='Please leave us your feedback' mod='ybc_blog'}"></textarea>
                                    </div>
                                    {if $blog_config.YBC_BLOG_ENABLE_POLLS_CAPCHA}
                                        {if $blog_config.YBC_BLOG_CAPTCHA_TYPE!=='google' && $blog_config.YBC_BLOG_CAPTCHA_TYPE!=='google3'}
                                            <div class="form-group polls-capcha">
                                               <span class="poll-capcha-wrapper">
                                                    <img rel="{$blog_poll_random_code|escape:'html':'UTF-8'}" class="ybc-captcha-img-data" id="ybc-blog-polls-capcha-img" src="{$polls_capcha_image|escape:'html':'UTF-8'}" alt="{l s='Security code' mod='ybc_blog'}" />
                                                    <input placeholder="{l s='Secure code' mod='ybc_blog'}" class="form-control" name="polls_capcha_code" type="text" id="polls-capcha" value="" />
                                                    <span id="ybc-blog-polls-capcha-refesh" title="{l s='Refresh code' mod='ybc_blog'}">{*l s='Refresh code'*}</span>
                                                </span>
                                            </div>
                                        {else}
                                            {if $blog_config.YBC_BLOG_CAPTCHA_TYPE=='google'}
                                                <script src="https://www.google.com/recaptcha/api.js?onload=ybc_polls_lonloadCallback&render=explicit" async defer></script>
                                                <div id="ybc_blog_polls_g_recaptcha" class="ybc_blog_g_recaptcha" ></div>
                                            {/if}
                                            {if $blog_config.YBC_BLOG_CAPTCHA_TYPE=='google3'}
                                                <input type="hidden" id="ybc_blog_polls_g_recaptcha" name="g-recaptcha-response" />
                                                <script type="text/javascript">
                                                    ybc_polls_lonloadCallback();
                                                </script>
                                            {/if}
                                        {/if}
                                    {/if}
                                    <div class="form_action_footer">
                                        <input type="hidden" value="1" name="polls_submit"/>
                                        <button type="submit" name="polls_submit">{l s='Submit' mod='ybc_blog'}</button>
                                        <button type="button" name="polls_cancel" style="margin-right: 10px;">{l s='Cancel' mod='ybc_blog'}</button>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </form>
                    {/if}
                    {if ($show_tags && $blog_post.tags) || ($show_categories && $blog_post.categories)}
                    <div class="extra_tag_cat">
                        {if $show_tags && $blog_post.tags}
                            <div class="ybc-blog-tags">
                                {assign var='ik' value=0}
                                {assign var='totalTag' value=count($blog_post.tags)}
                                <span class="be-label">
                                    {if $totalTag > 1}{l s='Tags' mod='ybc_blog'}
                                    {else}{l s='Tag' mod='ybc_blog'}{/if}:
                                </span>
                                {foreach from=$blog_post.tags item='tag'}
                                    {assign var='ik' value=$ik+1}
                                    <a href="{$tag.link|escape:'html':'UTF-8'}">{ucfirst($tag.tag)|escape:'html':'UTF-8'}</a>{if $ik < $totalTag}, {/if}
                                {/foreach}
                            </div>
                        {/if}
                        {if $show_categories && $blog_post.categories}
                            <div class="ybc-blog-categories">
                                {assign var='ik' value=0}
                                {assign var='totalCat' value=count($blog_post.categories)}
                                <div class="be-categories">
                                    <span class="be-label">{l s='Posted in' mod='ybc_blog'}: </span>
                                    {foreach from=$blog_post.categories item='cat'}
                                        {assign var='ik' value=$ik+1}
                                        <a href="{$cat.link|escape:'html':'UTF-8'}">{ucfirst($cat.title)|escape:'html':'UTF-8'}</a>{if $ik < $totalCat}, {/if}
                                    {/foreach}
                                </div>
                            </div>
                        {/if}
                    </div>
                    {/if}
                    {if isset($blog_config.YBC_BLOG_AUTHOR_INFORMATION)&& $blog_config.YBC_BLOG_AUTHOR_INFORMATION && isset($blog_post.employee.description)&& $blog_post.employee.description}
                        <div class="ybc-block-author ybc-block-author-avata {if $blog_post.employee.avata} ybc-block-author-avata{/if}">
                            {if $blog_post.employee.avata}
                                <div class="avata_img">
                                    <img class="avata" src="{$link->getMediaLink("`$smarty.const._PS_YBC_BLOG_IMG_`avata/`$blog_post.employee.avata|escape:'htmlall':'UTF-8'`")}"/>
                                </div>

                            {/if}
                            <div class="ybc-des-and-author">
                                <div class="ybc-author-name">
                                    <a href="{$blog_post.author_link|escape:'html':'UTF-8'}">
                                        {l s='Author' mod='ybc_blog'}: {$blog_post.employee.name|escape:'html':'UTF-8'}
                                    </a>
                                </div>
                                {if isset($blog_post.employee.description)&&$blog_post.employee.description}
                                    <div class="ybc-author-description">
                                        {$blog_post.employee.description nofilter}
                                    </div>
                                {/if}
                            </div>
                        </div>
                    {/if}
                    {if $display_related_products && $blog_post.products}
                        <div id="ybc-blog-related-products" class="" >
                            <h4 class="title_blog">
                                {if count($blog_post.products) > 1}{l s='Related inventaries ' mod='ybc_blog'}
                                {else}{l s='Related product' mod='ybc_blog'}{/if}
                            </h4>
                            <div class="ybc-blog-related-products-wrapper ybc-blog-related-products-list">
                                {assign var='product_row' value=$blog_config.YBC_BLOG_RELATED_PRODUCT_ROW|intval}
                                <ul class="dt-{$product_row|intval} blog-product-list product_list grid row ybc_related_products_type_{if $blog_related_product_type}{$blog_related_product_type|escape:'html':'UTF-8'}{else}default{/if}">
                                    {assign var='product_row' value=$blog_config.YBC_BLOG_RELATED_PRODUCT_ROW|intval}
                                    {foreach from=$blog_post.products item='product'}
                                        <li class="ajax_block_product col-xs-12 col-sm-4 col-lg-{12/$product_row|intval}">
                                            <div class="product-container" itemprop="offers" itemscop itemtype="https://schema.org/Offer">
                                                <div class="left-block">
                                                    <a class="ybc_item_img{if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD} ybc_item_img_ladyload{/if}" href="{$product.link|escape:'html':'UTF-8'}">
                                                        <img src="{if isset($blog_config.YBC_BLOG_LAZY_LOAD) && $blog_config.YBC_BLOG_LAZY_LOAD}{$link->getMediaLink("`$smarty.const._MODULE_DIR_`ybc_blog/views/img/bg-grey.png")|escape:'html':'UTF-8'}{else}{$product.img_url|escape:'html':'UTF-8'}{/if}" alt="{$product.name|escape:'html':'UTF-8'}" {if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD} data-original="{$product.img_url|escape:'html':'UTF-8'}" data-src="{$product.img_url|escape:'html':'UTF-8'}" class="lazyload"{/if} />
                                                        {if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD}
                                                            <div class="loader_lady_custom"></div>
                                                        {/if}
                                                    </a>
                                                </div>
                                                <div class="right-block">
                                                    <h5><a href="{$product.link|escape:'html':'UTF-8'}">{$product.name|escape:'html':'UTF-8'}</a></h5>
                                                    {if $show_price}
                                                        <div class="blog-product-extra content_price">
                                                            {if $product.price!=$product.old_price}
                                                                <span class="bp-price-old old-price"><span class="bp-price-old-label">{l s='Old price: ' mod='ybc_blog'}</span><span class="bp-price-old-display">{$product.old_price|escape:'html':'UTF-8'}</span></span>
                                                            {/if}
                                                            <span class="bp-price price product-price"><span class="bp-price-label">{l s='Price:  ' mod='ybc_blog'}</span><span class="bp-price-display" itemprop="price" content="{$product.price|escape:'html':'UTF-8'}">{$product.price|escape:'html':'UTF-8'}</span></span>
                                                            {if $product.price!=$product.old_price}
                                                                <span class="bp-percent price-percent-reduction"><span class="bp-percent-label">{l s='Discount: ' mod='ybc_blog'}</span><span class="bp-percent-display">{$product.discount_percent|escape:'html':'UTF-8'}{l s='%' mod='ybc_blog'}</span></span>
                                                                <span class="bp-save"><span class="bp-save-label">{l s='Save up: ' mod='ybc_blog'}</span><span class="bp-save-display">-{$product.discount_amount|escape:'html':'UTF-8'}</span></span>
                                                            {/if}
                                                        </div>
                                                    {/if}
                                                    {if $product.short_description}
                                                        <div class="blog-product-desc">
                                                            {$product.short_description|strip_tags:'UTF-8'|truncate:80:'...'|escape:'html':'UTF-8'}
                                                        </div>
                                                    {/if}
                                                </div>
                                            </div>
                                        </li>
                                    {/foreach}
                                </ul>
                            </div>
                        </div>
                    {/if}
                    <div class="ybc-blog-wrapper-comment">
                        {if $allowComments}
                            {if $allowComments==2}
                                <div id="fb-root"></div>
                                <script async defer crossorigin="anonymous" src="https://connect.facebook.net/{$langLocale|escape:'html':'UTF-8'}/sdk.js#xfbml=1&version=v8.0" nonce="xHtLYTmH"></script>
                                <h4 class="title_blog">{l s='Facebook comment' mod='ybc_blog'}</h4>
                                <div class="fb-comments" data-width="760" data-href="{$post_url|escape:'html':'UTF-8'}" data-numposts="5" data-width=""></div>
                            {else}
                            <div class="ybc_comment_form_blog">
                                <h4 class="title_blog">{l s='Leave a comment' mod='ybc_blog'}</h4>
                                <div class="ybc-blog-form-comment" id="ybc-blog-form-comment">
                                    {if $hasLoggedIn || $allowGuestsComments}
                                        <form action="#ybc-blog-form-comment" method="post">
                                            {if isset($comment_edit->id) && $comment_edit->id && !$justAdded}
                                                <input type="hidden" value="{$comment_edit->id|intval}" name="id_comment" />
                                            {/if}
                                            {if !$hasLoggedIn}
                                                <div class="blog-comment-row blog-name">
                                                    <label for="bc-name">{l s='Name' mod='ybc_blog'}</label>
                                                    <input class="form-control" name="name_customer" id="bc-name" type="text" value="{if isset($name_customer)}{$name_customer|escape:'html':'UTF-8'}{elseif isset($comment_edit->name) && !$justAdded}{$comment_edit->name|escape:'html':'UTF-8'}{/if}" />
                                                </div>
                                                <div class="blog-comment-row blog-email">
                                                    <label for="bc-email">{l s='Email' mod='ybc_blog'}</label>
                                                    <input class="form-control" name="email_customer" id="bc-email" type="text" value="{if isset($email_customer)}{$email_customer|escape:'html':'UTF-8'}{elseif isset($comment_edit->email)&& !$justAdded}{$comment_edit->email|escape:'html':'UTF-8'}{/if}" />
                                                </div>
                                            {/if}
                                            <div class="blog-comment-row blog-title">
                                                <label for="bc-subject">{l s='Subject ' mod='ybc_blog'}</label>
                                                <input class="form-control" name="subject" id="bc-subject" type="text" value="{if isset($subject)}{$subject|escape:'html':'UTF-8'}{elseif isset($comment_edit->subject)&& !$justAdded}{$comment_edit->subject|escape:'html':'UTF-8'}{/if}" />
                                            </div>
                                            <div class="blog-comment-row blog-content-comment">
                                                <label for="bc-comment">{l s='Comment ' mod='ybc_blog'}</label>
                                                <textarea   class="form-control" name="comment" id="bc-comment">{if isset($comment)}{$comment|escape:'html':'UTF-8'}{elseif isset($comment_edit->comment)&& !$justAdded}{$comment_edit->comment|escape:'html':'UTF-8'}{/if}</textarea>
                                            </div>
                                            <div class="blog-comment-row flex_space_between flex-bottom">
                                                {if $allow_rating || $use_capcha}
                                                    <div class="blog-rate-capcha">
                                                        {if $allow_rating}
                                                            <div class="blog-rate-post">
                                                                <label>{l s='Rating: ' mod='ybc_blog'}</label>
                                                                <div class="blog_rating_box">
                                                                    {if $default_rating > 0 && $default_rating <5}
                                                                        <input id="blog_rating" type="hidden" name="rating" value="{$default_rating|intval}" />
                                                                        {for $i = 1 to $default_rating}
                                                                            <div rel="{$i|intval}" class="star star_on blog_rating_star blog_rating_star_{$i|intval}"></div>
                                                                        {/for}
                                                                        {for $i = $default_rating + 1 to 5}
                                                                            <div rel="{$i|intval}" class="star blog_rating_star blog_rating_star_{$i|intval}"></div>
                                                                        {/for}
                                                                    {else}
                                                                        <input id="blog_rating" type="hidden" name="rating" value="5" />
                                                                        {for $i = 1 to 5}
                                                                            <div rel="{$i|intval}" class="star star_on blog_rating_star blog_rating_star_{$i|intval}"></div>
                                                                        {/for}
                                                                    {/if}
                                                                </div>
                                                            </div>
                                                        {/if}
                                                        {if $use_capcha}
                                                            {if $blog_config.YBC_BLOG_CAPTCHA_TYPE!=='google' && $blog_config.YBC_BLOG_CAPTCHA_TYPE!=='google3'}
                                                                <div class="blog-capcha">
                                                                    <label for="bc-capcha">{l s='Security code: ' mod='ybc_blog'}</label>
                                                                    <span class="bc-capcha-wrapper">
                                                                        <img rel="{$blog_random_code|escape:'html':'UTF-8'}" class="ybc-captcha-img-data" id="ybc-blog-capcha-img" src="{$capcha_image|escape:'html':'UTF-8'}" alt="{l s='Security code' mod='ybc_blog'}" />
                                                                        <input class="form-control" name="capcha_code" type="text" id="bc-capcha" value="" />
                                                                        <span id="ybc-blog-capcha-refesh" title="{l s='Refresh code' mod='ybc_blog'}">{*l s='Refresh code'*}</span>
                                                                    </span>
                                                                </div>
                                                            {else}
                                                                {if $blog_config.YBC_BLOG_CAPTCHA_TYPE=='google'}
                                                                    <script src="https://www.google.com/recaptcha/api.js?onload=ybc_comment_lonloadCallback&render=explicit" async defer></script>
                                                                    <div id="ybc_blog_comment_g_recaptcha" class="ybc_blog_g_recaptcha" ></div>
                                                                {/if}
                                                                {if $blog_config.YBC_BLOG_CAPTCHA_TYPE=='google3'}
                                                                    <input type="hidden" id="ybc_blog_comment_g_recaptcha" name="g-recaptcha-response" />
                                                                    <script type="text/javascript">
                                                                        ybc_comment_lonloadCallback();
                                                                    </script>
                                                                {/if}
                                                            {/if}
                                                        {/if}
                                                    </div>
                                                {/if}
                                                <div class="blog-submit-form">
                                                    {if !Configuration::get('YBC_BLOG_DISPLAY_GDPR_NOTIFICATION')}
                                                        <div class="blog-submit">
                                                            <input class="button" type="submit" value="{l s='Submit Comment' mod='ybc_blog'}" name="bcsubmit" />
                                                        </div>
                                                    {/if}
                                                </div>
                                                {if $blog_errors && is_array($blog_errors) && !isset($replyCommentsave)}
                                                    <div class="alert alert-danger ybc_alert-danger">
                                                        <button class="close" type="button" data-dismiss="alert">×</button>
                                                        <ul>
                                                            {foreach from=$blog_errors item='error'}
                                                                <li>{$error|escape:'html':'UTF-8'}</li>
                                                            {/foreach}
                                                        </ul>
                                                    </div>
                                                {/if}
                                            </div>
                                            <div class="blog-comment-row">
                                                <div class="blog-submit-form">
                                                    {if Configuration::get('YBC_BLOG_DISPLAY_GDPR_NOTIFICATION')}
                                                        <label for="check_gpdr">
                                                            <input id="check_gpdr" type="checkbox" type="check_gpdr" value="1"/>&nbsp;{$text_gdpr|escape:'html':'UTF-8'}
                                                            <a href="{if Configuration::get('YBC_BLOG_TEXT_GDPR_NOTIFICATION_URL_MORE',$id_lang)}{Configuration::get('YBC_BLOG_TEXT_GDPR_NOTIFICATION_URL_MORE',$id_lang)|escape:'html':'UTF-8'}{else}#{/if}">{if Configuration::get('YBC_BLOG_TEXT_GDPR_NOTIFICATION_TEXT_MORE',$id_lang)}{Configuration::get('YBC_BLOG_TEXT_GDPR_NOTIFICATION_TEXT_MORE',$id_lang)|escape:'html':'UTF-8'}{else}{l s='View more details here' mod='ybc_blog'}{/if}</a>
                                                        </label>
                                                        <div class="blog-submit">
                                                            <input class="button" type="submit" disabled="disabled" value="{l s='Submit Comment' mod='ybc_blog'}" name="bcsubmit" />
                                                        </div>
                                                    {/if}
                                                </div>
                                            </div>
                                            {if $blog_success && !$replyCommentsaveok}
                                                <p class="alert alert-success ybc_alert-success">
                                                <button class="close" type="button" data-dismiss="alert">×</button>
                                                {$blog_success|escape:'html':'UTF-8'}
                                                </p>
                                            {/if}
                                        </form>
                                    {else}
                                        <p class="alert alert-warning">{l s='Log in to post comments' mod='ybc_blog'}</p>
                                    {/if}
                                </div>
                            </div>
                            {if count($comments)}
                                <div class="ybc_blog-comments-list">
                                <h4 class="title_blog">{l s='Comments' mod='ybc_blog'}</h4>
                                    <ul id="blog-comments-list" class="blog-comments-list">
                                        {foreach from=$comments item='comment'}
                                                <li id="blog_comment_line_{$comment.id_comment|intval}" class="blog-comment-line"  {*itemprop="review" itemscope="" itemtype="http://schema.org/Review"*}>
                                                {*<meta itemprop="author" content="{ucfirst($comment.name)|escape:'html':'UTF-8'}"/>*}
                                                <div class="ybc-blog-detail-comment">
                                                    <h5 class="comment-subject">{$comment.subject|escape:'html':'UTF-8'}</h5>
                                                    {if $comment.name}<span class="comment-by">{l s='By: ' mod='ybc_blog'}<b>{ucfirst($comment.name)|escape:'html':'UTF-8'}</b></span>{/if}
                                                    <span class="comment-time"><span>{l s='On' mod='ybc_blog'} </span>{date($date_format,strtotime($comment.datetime_added))|escape:'html':'UTF-8'}</span>
                                                    {if $allow_rating && $comment.rating > 0}
                                                        <div class="comment-rating" >
                                                            <span>{l s='Rating: ' mod='ybc_blog'}</span>
                                                            <div class="ybc_blog_review">
                                                                {for $i = 1 to $comment.rating}
                                                                    <div class="star star_on"></div>
                                                                {/for}
                                                                {if $comment.rating<5}
                                                                    {for $i = $comment.rating + 1 to 5}
                                                                        <div class="star"></div>
                                                                    {/for}
                                                                {/if}
                                                                <span class="ybc-blog-everage-rating"> {number_format((float)$comment.rating, 1, '.', '')|escape:'html':'UTF-8'}</span>
                                                            </div>
                                                        </div>
                                                    {/if}
                                                    <div class="ybc-block-report-reply-edit-delete">
                                                        {if $allow_report_comment}
                                                            {if !($reportedComments && is_array($reportedComments) && in_array($comment.id_comment, $reportedComments))}
                                                                <span class="ybc-block-comment-report comment-report-{$comment.id_comment|intval}" rel="{$comment.id_comment|intval}"><i class="fa fa-bolt" aria-hidden="true" title="{l s='Report this comment as abused' mod='ybc_blog'}"></i></span>
                                                            {/if}
                                                        {/if}
                                                        {if isset($comment.reply) && $comment.reply}
                                                            <span class="ybc-block-comment-reply comment-reply-{$comment.id_comment|intval}" rel="{$comment.id_comment|intval}"><i class="fa fa-reply" aria-hidden="true" title="{l s='Reply' mod='ybc_blog'}"></i></span>
                                                        {/if}
                                                        {if isset($comment.url_edit)}
                                                            <a class="ybc-block-comment-edit comment-edit-{$comment.id_comment|intval}" href="{$comment.url_edit|escape:'html':'UTF-8'}"><i class="fa fa-pencil" aria-hidden="true" title="{l s='Edit this comment' mod='ybc_blog'}"></i></a>
                                                        {/if}
                                                        {if isset($comment.url_delete)}
                                                            <a class="ybc-block-comment-delete delete-edit-{$comment.id_comment|intval}" href="{$comment.url_delete|escape:'html':'UTF-8'}"><i class="fa fa-trash" aria-hidden="true" title="{l s='Delete this comment' mod='ybc_blog'}"></i></a>
                                                        {/if}
                                                    </div>
                                                    {if $comment.comment}<p class="comment-content">{$comment.comment nofilter}</p>{/if}
                                                    {if $comment.replies}
                                                        {foreach $comment.replies item='reply'}
                                                            <p class="comment-reply">
                                                                <span class="ybc-blog-replied-by">
                                                                    {l s='Replied by: ' mod='ybc_blog'}
                                                                    <span class="ybc-blog-replied-by-name">
                                                                        {ucfirst($reply.name)|escape:'html':'UTF-8'}
                                                                    </span>
                                                                </span>
                                                                <span class="comment-time"><span>{l s='On' mod='ybc_blog'} </span>{date($date_format,strtotime($reply.datetime_added))|escape:'html':'UTF-8'}</span>
                                                                <span class="ybc-blog-reply-content">
                                                                    {$reply.reply nofilter}
                                                                </span>
                                                            </p>
                                                        {/foreach}
                                                    {/if}
                                                    {if isset($replyCommentsave) && $replyCommentsave==$comment.id_comment}
                                                        {if isset($replyCommentsaveok) && $blog_success}
                                                            <p class="alert alert-success ybc_alert-success">
                                                            <button class="close" type="button" data-dismiss="alert">×</button>{$blog_success|escape:'html':'UTF-8'}
                                                            </p>
                                                        {else}
                                                            {if isset($comment.reply) && $comment.reply}
                                                                <form class="form_reply_comment" action="#blog_comment_line_{$comment.id_comment|intval}" method="post">
                                                                    {if $blog_errors && is_array($blog_errors) && isset($replyCommentsave)}
                                                                        <div class="alert alert-danger ybc_alert-danger">
                                                                            <button class="close" type="button" data-dismiss="alert">×</button>
                                                                            <ul >
                                                                                {foreach from=$blog_errors item='error'}
                                                                                    <li>{$error|escape:'html':'UTF-8'}</li>
                                                                                {/foreach}
                                                                            </ul>
                                                                        </div>
                                                                    {/if}
                                                                    <input type="hidden" name="replyCommentsave" value="{$comment.id_comment|intval}" />
                                                                    <textarea name="reply_comwent_text" placeholder="{l s='Enter your message...' mod='ybc_blog'}">{$reply_comwent_text nofilter}</textarea>
                                                                    <input type="submit" value="Send" />
                                                                </form>
                                                            {else}
                                                                {if $blog_errors && is_array($blog_errors) && isset($replyCommentsave)}
                                                                    <div class="alert alert-danger ybc_alert-danger">
                                                                        <button class="close" type="button" data-dismiss="alert">×</button>
                                                                        <ul >
                                                                            {foreach from=$blog_errors item='error'}
                                                                                <li>{$error|escape:'html':'UTF-8'}</li>
                                                                            {/foreach}
                                                                        </ul>
                                                                    </div>
                                                                {/if}
                                                            {/if}
                                                        {/if}
                                                    {/if}
                                                </div>
                                                </li>
                                        {/foreach}
                                    </ul>
                                    {if isset($link_view_all_comment)}
                                        <div class="blog_view_all_button">
                                            <a href="{$link_view_all_comment|escape:'html':'UTF-8'}" class="view_all_link">{l s='View all comments' mod='ybc_blog'}</a>
                                        </div>
                                    {/if}
                                </div>
                            {/if}
                            {/if}
                        {/if}
                    </div>
                </div>
                {else}
                    <p class="warning">{l s='No posts found' mod='ybc_blog'}</p>
                {/if}
                {if $blog_post.related_posts}
                    <div class="ybc-blog-related-posts ybc_blog_related_posts_type_{if $blog_related_posts_type}{$blog_related_posts_type|escape:'html':'UTF-8'}{else}default{/if}">
                        <h4 class="title_blog">{l s='Related posts' mod='ybc_blog'}</h4>
                        <div class="ybc-blog-related-posts-wrapper">
                            {assign var='post_row' value=$blog_config.YBC_BLOG_RELATED_POST_ROW|intval}
                            <ul class="ybc-blog-related-posts-list dt-{$post_row|intval}">
                                {foreach from=$blog_post.related_posts item='rpost'}
                                    <li class="ybc-blog-related-posts-list-li col-xs-12 col-sm-4 col-lg-{12/$post_row|intval} thumbnail-container">
                                        {if $rpost.thumb}
                                            <a class="ybc_item_img{if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD} ybc_item_img_ladyload{/if}" href="{$rpost.link|escape:'html':'UTF-8'}">
                                                <img src="{if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD}{$link->getMediaLink("`$smarty.const._MODULE_DIR_`ybc_blog/views/img/bg-grey.png")|escape:'html':'UTF-8'}{else}{$rpost.thumb|escape:'html':'UTF-8'}{/if}" alt="{$rpost.title|escape:'html':'UTF-8'}" {if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD} data-original="{$rpost.thumb|escape:'html':'UTF-8'}" class="lazyload"{/if} />
                                                {if isset($blog_config.YBC_BLOG_LAZY_LOAD)&& $blog_config.YBC_BLOG_LAZY_LOAD}
                                                    <div class="loader_lady_custom"></div>
                                                {/if}
                                            </a>

                                        {/if}
                                        <a class="ybc_title_block" href="{$rpost.link|escape:'html':'UTF-8'}">{$rpost.title|escape:'html':'UTF-8'}</a>
                                        <div class="ybc-blog-sidear-post-meta">
                                            {if $rpost.categories}
                                                {assign var='ik' value=0}
                                                {assign var='totalCat' value=count($rpost.categories)}
                                                <div class="ybc-blog-categories">
                                                    {*<span class="be-label">{l s='Posted in' mod='ybc_blog'}: </span>*}
                                                    {foreach from=$rpost.categories item='cat'}
                                                        {assign var='ik' value=$ik+1}
                                                        <a href="{$cat.link|escape:'html':'UTF-8'}">{ucfirst($cat.title)|escape:'html':'UTF-8'}</a>{if $ik < $totalCat}, {/if}
                                                    {/foreach}
                                                </div>
                                            {/if}
                                            {*<span class="post-date">{date($date_format,strtotime($rpost.datetime_added))|escape:'html':'UTF-8'}</span>*}
                                        </div>
                                        {if $allowComments || $show_views || $allow_like}
                                            <div class="ybc-blog-latest-toolbar">
                                                {if $show_views}
                                                    <span class="ybc-blog-latest-toolbar-views">
                                                        {$rpost.click_number|intval}
                                                        {if $rpost.click_number!=1}
                                                            <span>{l s='views' mod='ybc_blog'}</span>
                                                        {else}
                                                            <span>{l s='view' mod='ybc_blog'}</span>
                                                        {/if}
                                                    </span>
                                                {/if}
                                                {if $allow_like}
                                                    <span class="ybc-blog-like-span ybc-blog-like-span-{$rpost.id_post|intval} {if isset($rpost.liked) && $rpost.liked}active{/if}"  data-id-post="{$rpost.id_post|intval}">
                                                        <span class="ben_{$rpost.id_post|intval}">
                                                        {$rpost.likes|intval}
                                                        </span>
                                                        <span class="blog-post-like-text blog-post-like-text-{$rpost.id_post|intval}">
                                                            {l s='Liked' mod='ybc_blog'}
                                                        </span>
                                                    </span>
                                                {/if}
                                                {if $allowComments}
                                                    {if $rpost.comments_num > 0 }
                                                        <span class="ybc-blog-latest-toolbar-comments">{$rpost.comments_num|intval}
                                                            {if $rpost.comments_num!=1}
                                                                <span>{l s='comments' mod='ybc_blog'}</span>
                                                            {else}
                                                                <span>{l s='comment' mod='ybc_blog'}</span>
                                                            {/if}
                                                        </span>
                                                    {/if}
                                                {/if}
                                            </div>
                                        {/if}
                                        {if $display_desc}
                                            {if $rpost.short_description}
                                                <div class="blog_description">{$rpost.short_description|strip_tags:'UTF-8'|truncate:120:'...'|escape:'html':'UTF-8'}</div>
                                            {elseif $rpost.description}
                                                <div class="blog_description">{$rpost.description|strip_tags:'UTF-8'|truncate:120:'...'|escape:'html':'UTF-8'}</div>
                                            {/if}
                                        {/if}
                                         <a class="read_more" href="{$rpost.link|escape:'html':'UTF-8'}">{if $blog_config.YBC_BLOG_TEXT_READMORE}{$blog_config.YBC_BLOG_TEXT_READMORE|escape:'html':'UTF-8'}{else}{l s='Read More' mod='ybc_blog'}{/if}</a>
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    {else}
        <p class="alert alert-warning">{l s='This blog post is not available' mod='ybc_blog'}</p>
    {/if}
</div>

{if isset($blog_config.YBC_BLOG_SIDEBAR_POSITION) && $blog_config.YBC_BLOG_SIDEBAR_POSITION=='right'}
    <div id="right-column" class="col-xs-12 col-sm-4 col-md-3">
      {hook h="blogSidebar"}
    </div>
{/if}
</div>
{/block}
