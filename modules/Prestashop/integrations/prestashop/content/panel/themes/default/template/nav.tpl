<nav class="nav-bar" role="navigation" id="nav-sidebar">
	<span class="menu-collapse" data-toggle-url="{$toggle_navigation_url}">
		<i class="material-icons">chevron_left</i>
		<i class="material-icons">chevron_left</i>
	</span>

  <div class="nav-bar-overflow">
      <ul class="main-menu{if $collapse_menu} sidebar-closed{/if}">
      {foreach $tabs as $level_1}
        {if $level_1.active}
          {* Dashboard exception *}
          {if $level_1.class_name == 'AdminDashboard'}
            <li class="link-levelone{if $level_1.current} link-active{/if}" id="tab-{$level_1.class_name}" data-submenu="{$level_1.id_tab}">
              <a href="{if $level_1.sub_tabs|@count && isset($level_1.sub_tabs[0].href)}{$level_1.sub_tabs[0].href|escape:'html':'UTF-8'}{else}{$level_1.href|escape:'html':'UTF-8'}{/if}" class="link" >
                <i class="material-icons">{$level_1.icon}</i>
                <span>{if $level_1.name eq ''}{$level_1.class_name|escape:'html':'UTF-8'}{else}{$level_1.name|escape:'html':'UTF-8'}{/if}</span>
              </a>
            </li>
          {else}
            <li class="category-title{if $level_1.current} link-active{/if}" id="tab-{$level_1.class_name}" data-submenu="{$level_1.id_tab}">
              <span class="title">
                <span>{if $level_1.name eq ''}{$level_1.class_name|escape:'html':'UTF-8'}{else}{$level_1.name|escape:'html':'UTF-8'}{/if}</span>
              </span>
            </li>

            {if $level_1.sub_tabs|@count}
              {foreach $level_1.sub_tabs as $level_2}
                {if $level_2.active}
                  {assign var="mainTabClass" value=''}

                  {if $level_2.current and not $collapse_menu}
                    {assign var="mainTabClass" value=" link-active open ul-open"}
                  {elseif $level_2.current and $collapse_menu}
                    {assign var="mainTabClass" value=" link-active"}
                  {/if}
                  <li class="link-levelone{if $level_2.sub_tabs|@count} has_submenu{/if}{$mainTabClass}" id="subtab-{$level_2.class_name|escape:'html':'UTF-8'}" data-submenu="{$level_2.id_tab}">
                    <a href="{$level_2.href|escape:'html':'UTF-8'}" class="link">
                      <i class="material-icons mi-{$level_2.icon}">{$level_2.icon}</i>
                      <span>
                        {if $level_2.name eq ''}{$level_2.class_name|escape:'html':'UTF-8'}{else}{$level_2.name|escape:'html':'UTF-8'}{/if}
                      </span>
                      {if $level_2.sub_tabs|@count}
                        <i class="material-icons sub-tabs-arrow">
                          {if $level_2.current}
                            keyboard_arrow_up
                          {else}
                            keyboard_arrow_down
                          {/if}
                        </i>
                      {/if}
                    </a>

                    {if $level_2.sub_tabs|@count}
                      <ul id="collapse-{$level_2.id_tab}" class="submenu panel-collapse">

                        {foreach $level_2.sub_tabs as $level_3}
                          {if $level_3.active}
                            <li class="link-leveltwo{if $level_3.current} link-active{/if}" id="subtab-{$level_3.class_name|escape:'html':'UTF-8'}" data-submenu="{$level_3.id_tab}">
                              <a href="{$level_3.href|escape:'html':'UTF-8'}" class="link">
                                {if $level_3.name eq ''}{$level_3.class_name|escape:'html':'UTF-8'}{else}{$level_3.name|escape:'html':'UTF-8'}{/if}
                              </a>
                            </li>
                          {/if}
                        {/foreach}

                        {if $level_2.class_name == 'AdminCatalog'}
                          <!--DESDE AQUI ADDIS - /httpdocs/panel/themes/default/template-->
                          <li class="link-leveltwo{if $level_3.current} link-active{/if}" id="tab-Test-Addis" data-submenu="{$level_3.id_tab}" id="subtab-{$level_3.class_name}">
                            <a href="/panel/index.php?controller=AdminModules&configure=addis_categorias_marcas&token=3661432553a3683bf584580111479c8e" class="link" target="_self">
                              Categorias para marcas
                            </a>
                          </li>
                          <!--HASTA AQUI-->
                        {/if}

                      </ul>
                    {/if}
                  </li>
                {/if}
              {/foreach}
            {/if}
          {/if}

          {if $level_1.class_name == 'DEFAULT'}
            <!--DESDE AQUI ADDIS - /httpdocs/panel/themes/default/template-->
            <li class="link-levelone{if $level_1.current} link-active{/if}" id="tab-Test2-Addis" data-submenu="{$level_1.id_tab}" id="subtab-{$level_1.class_name}">
              <a href="/panel/index.php?controller=AdminModules&configure=hicallmeback&token=3661432553a3683bf584580111479c8e" class="link" target="_self">
                <i class="material-icons">settings_applications</i> <span>Call center</span>
              </a>
            </li>
            <!--HASTA AQUI-->
          {/if}

        {/if}
      {/foreach}

    </ul>
    {hook h='displayAdminNavBarBeforeEnd'}
  </div>
	</nav>
