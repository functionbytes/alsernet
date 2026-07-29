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
* needs please, contact us for extra customization service at an affordable price
*
*  @author ETS-Soft <etssoft.jsc@gmail.com>
*  @copyright  2007-2022 ETS-Soft
*  @license    Valid for 1 website (or project) for each purchase of license
*  International Registered Trademark & Property of ETS-Soft
*}
{extends file='page.tpl'}

{block name='breadcrumb'}
    <section data-depth="{$breadcrumb.count|intval}" class="breadcrumb hidden-sm-down panel-title">
        <div class="container">
            <ul itemscope itemtype="http://schema.org/BreadcrumbList" class="nav-breadcrumbs">
                {foreach from=$breadcrumb.links item=path name=breadcrumb}
                    <li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <a itemprop="item" href="{$path.url|escape:'html':'UTF-8'}">
                            <span itemprop="name">{$path.title|escape:'html':'UTF-8'}</span>
                        </a>
                        <meta itemprop="position"
                              content="{$smarty.foreach.breadcrumb.iteration|escape:'html':'UTF-8'}"/>
                    </li>
                {/foreach}
            </ul>
        </div>
    </section>
{/block}

{block name='page_title'}
    {if isset($thank_you_page_title) && $thank_you_page_title}
        <h1 class="panel-title-heading">{$thank_you_page_title|escape:'html':'UTF-8'}</h1>
    {/if}
{/block}

{block name='page_content_container'}
  <section id="content" class="page-content">
    {$thank_you_message nofilter}
  </section>
{/block}