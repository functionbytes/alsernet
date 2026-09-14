{extends file='layouts/layout-full-width.tpl'}

{block name="content"}
    <div class="prices-drop-categories">
        {foreach from=$deportes item=deporte}
            <h3 class="prices-drop-categories-item{if $deporte.current} active{/if}"><a href="{$deporte.url}"{if $deporte.current} onclick="event.preventDefault();"{/if}>{$deporte.category_name}</a></h3>
        {/foreach}
    </div>

    <h1 class="ofertas-deporte-title">{l s='Highlighted offers on [sport]' sprintf=['[sport]' => $deporte_self.category_name] d='Shop.Theme.Catalog'}</h1>

{/block}
