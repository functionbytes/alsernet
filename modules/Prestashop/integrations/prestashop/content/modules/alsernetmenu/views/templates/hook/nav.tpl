<div class="navs">
    <h1 class="title">
        {if $category.category->tituloh1!=""}
            {$category.category->tituloh1}
        {else}
            {$name}
        {/if}
    </h1>
    <div class="menu-content">
      {foreach from=$subcategories item=subcategory}
            <div class="nav-item">
               <a  class="nav-action"  title="{l s=$subcategory.title}" data-id_subcategory="{$subcategory.id}" data-load-panel="{$subcategory.action}">
                  {l s=$subcategory.title}
                  
                  {if $subcategory.items|@count > 0}
                     <span class="navbar-toggler collapse-icons">
                           <i class="fa fa-chevron-down down"></i>
                           <i class="fa fa-chevron-up up"></i>
                     </span>
                  {/if}
               </a> 
               {if $subcategory.items|@count > 0}
                  <div class="items-submenu shadow">
                  </div>
               {/if}
            </div>
      {/foreach}
      {foreach from=$specials item=special}
            <div class="nav-item">
               <a  class="public" style="{$special.style}" {if $special.url != ''} href="{$special.url}"   {/if} title="{l s=$special.title}">
                  {l s=$special.title}
               </a> 
            </div>
      {/foreach}
   </div>
</div>