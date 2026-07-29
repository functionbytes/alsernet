   <div class="container">
      <div class="supernav-content-wrapper" >
            <div class="nav" role="navigation">
               <div class="mm-menu shadow">
                  <ul class="menu-content back-menu super-nav" id="base-menu" data-id-category="0">
                     {foreach from=$categories item=category}
                        <li class="nav-item">
                           <a href="/{$category.url}"  data-id_category="{$category.id}" data-load-panel="{$category.action}">
                              {l s=$category.title}
                           </a>
                           <div class="items-submenu">
                           </div>
                        </li>
                     {/foreach}
                  </ul>
               </div>
            </div>
      </div>
   </div>