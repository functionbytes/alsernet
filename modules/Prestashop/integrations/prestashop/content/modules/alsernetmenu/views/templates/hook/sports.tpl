<div class="navs">
   <div class="title">
      {l s='Brands by sport' mod='alsernetmenu'}
   </div>
    <div class="menu-content">
      {foreach from=$sports item=item}
            {if $item.id_category < 20 }
               <div class="nav-item">
                    <a {if $language.iso_code == "es"}
                     href="/{$item.link_rewrite}/{l s='listado-marcas' d='Shop.Theme.Catalog'}"
                    {else}
                     href="/{$language.iso_code}/{$item.link_rewrite}/{l s='listado-marcas' d='Shop.Theme.Catalog'}"
                    {/if}
                    title="{l s=$item.name}">
                     {l s=$item.name} <span class="cuenta">({$item.subcats})</span>
                  </a>
                  </div>
            {/if}
      {/foreach}
   </div>
</div>