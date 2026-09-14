


       <div class="mobile-menu-wrapper">
        <div class="mobile-menu-overlay"></div>
        <div class="mobile-menu-container scrollable">
                <nav class='nav mobile' role='navigation'>
                        <div class='mm-header'>
                            <span>Menu</span>
                            <p class="mobile-menu-close">
                               <i class="fa-light fa-xmark"></i>
                            </p>
                        </div>
                        <div class='mm-menu'>
                            <ul class='menu-content back-menu' id='base-menu' data-id-category='0'>
                                {foreach $categories_mobile as $category}
                                    {if $category.id == 0}
                                        {assign var='title_products' value=$category.title}
                                    {else}
                                        <li class='nav-item'>
                                            <a class='category-item' title='{$category.title}'>
                                                {$category.title}
                                                <span class='navbar-toggler collapse-icons'>
                                                    <i class='fa fa-chevron-down down'></i>
                                                    <i class='fa fa-chevron-up up'></i>
                                                </span>
                                            </a>
                                            <div class='all-items'>
                                                <a class='category-all active'> {$title_products}
                                                    <span class='navbar-toggler collapse-icons'>
                                                        <i class='fa fa-chevron-down down'></i>
                                                        <i class='fa fa-chevron-up up'></i>
                                                    </span>
                                                </a>
                                                <div class='items-submenu show'>
                                                    {if isset($category.subcategories)}
                                                            <div class='panel-submenu'>
                                                                {foreach $category.subcategories as $column_index => $column_subcategory}
                                                                    <div class='item_sub'>
                                                                        <p class='title-subcategory'>{$column_subcategory['title']}
                                                                            {if count($column_subcategory['items']) > 0}
                                                                                <span class='navbar-toggler collapse-icons'>
                                                                                    <i class='fa fa-chevron-down down'></i>
                                                                                    <i class='fa fa-chevron-up up'></i>
                                                                                </span>
                                                                            {/if}
                                                                        </p>
                                                                        <ul class='item-subcategory'>
                                                                            {foreach $column_subcategory['items'] as $item}
                                                                                {if $item['visible'] == 1 || $item['visible'] == 3}
                                                                                    <li><a href='{$item.url}'>{$item.title}</a></li>
                                                                                {/if}
                                                                            {/foreach}
                                                                        </ul>
                                                                    </div>
                                                                {/foreach}
                                                            </div>
                                                    {/if}

                                                </div>

                                                    {if isset($category.urls)}
                                                            <div class='panel-urls'>
                                                                {foreach $category.urls as $url}
                                                                    <div class='item_sub'>
                                                                        {foreach $url.items as $itemdemos}
                                                                            <a href='{$itemdemos.url}'>{$itemdemos.title}</a>
                                                                        {/foreach}
                                                                    </div>
                                                                {/foreach}
                                                            </div>
                                                    {/if}
                                            </div>
                                        </li>
                                    {/if}
                                {/foreach}
                            </ul>
                        </div>
                    </nav>


        </div>
    </div>


