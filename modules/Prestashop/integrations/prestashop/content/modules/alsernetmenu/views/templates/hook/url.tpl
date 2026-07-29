{if count($urls) > 0}
   <div class="navs urls">
      <div class="title">
         {$urls.title}
      </div>
      <div class="menu-content">
         {foreach from=$urls.items item=item}
            <div class="nav-item">
                  <a {if $item.url != ''} href="{$item.url}" {/if} title="{l s=$item.title}">
                     {l s=$item.title}
                  </a>
               </div>
         {/foreach}
      </div>
   </div>
{/if}
