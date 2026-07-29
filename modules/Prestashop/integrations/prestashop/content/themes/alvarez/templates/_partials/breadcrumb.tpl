{if $breadcrumb && $breadcrumb.links}

<nav class="breadcrumb-nav">
    <div class="container">
        <ul class="breadcrumb">
            {block name='breadcrumb'}

            {if $page.page_name == 'cart' || $page.page_name == 'checkout'}
            {foreach from=$breadcrumb.links item=breadcrumb name=breadcrumb}
            {block name='breadcrumb_item'}
            {if $smarty.foreach.breadcrumb.last}
            <li>{$breadcrumb.title}</li>
            {else}
            <li>
                <a href="{$breadcrumb.url}">{l s="Deportes Álvarez / Mi compra" d="Shop.Theme.Checkout"}</a>
            </li>
            {/if}
            {if !$smarty.foreach.breadcrumb.last && $smarty.foreach.breadcrumb.iteration !=
            ($smarty.foreach.breadcrumb.total - 1)}
            <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            {/if}
            {/block}
            {/foreach}
            {elseif $page.page_name == 'category' || $page.page_name == 'category'}

            {foreach from=$breadcrumb.links item=breadcrumb name=breadcrumb}
            {block name='breadcrumb_item'}

            {if $smarty.foreach.breadcrumb.last}
            <li>{$breadcrumb.title}</li>
            {else}
            <li>
                <a href="/{$breadcrumb.url}">{$breadcrumb.title}</a>
            </li>
            {/if}
            {if !$smarty.foreach.breadcrumb.last && $smarty.foreach.breadcrumb.iteration !=
            ($smarty.foreach.breadcrumb.total - 1)}
            <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            {/if}
            {/block}
            {/foreach}
            {else}
            {foreach from=$breadcrumb.links item=breadcrumb name=breadcrumb}
            {block name='breadcrumb_item'}
            {if $smarty.foreach.breadcrumb.last}

            <li>{$breadcrumb.title}</li>
            {else}
                <li><a href="{if $page.page_name == 'category'}
                            {if $breadcrumb.url|strpos:'/m'}
                            {if $breadcrumb.url|strpos:'/m/'}
                                {$breadcrumb.url|replace:" ?id_deporte=":""}
                            {else}
                                {$breadcrumb.url|replace:" /m":"/listado-marcas"|replace:"?id_deporte=":""}
                            {/if}
                            {elseif $breadcrumb.url|strpos:'/brands'}
                                {$breadcrumb.url|replace:" /brands":"/listado-marcas"|replace:"?id_deporte=":""}
                            {else}

                            {$breadcrumb.url}
                            {/if}
                            {elseif $breadcrumb.url|strpos:'/brands'}
                            {$breadcrumb.url|replace:"/brands":"/listado-marcas"|replace:"?id_deporte=":""}
                        {else}

                        {$breadcrumb.url}

                        {/if}">{if $breadcrumb.title == 'Marcas'}m{else}{$breadcrumb.title}{/if}</a></li>
            {/if}
            {if !$smarty.foreach.breadcrumb.last && $smarty.foreach.breadcrumb.iteration !=
            ($smarty.foreach.breadcrumb.total - 1)}
            <meta itemprop="position" content="{$smarty.foreach.breadcrumb.iteration}">
            {/if}
            {/block}
            {/foreach}
            {/if}

            {/block}
        </ul>
    </div>
</nav>
{/if}
