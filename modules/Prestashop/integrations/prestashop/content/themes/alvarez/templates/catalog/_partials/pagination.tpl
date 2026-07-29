
<nav class="pagination">
  <div class="col-xs-12 col-md-6 col-lg-4 text-md-left text-xs-center">
    {block name='pagination_summary'}
      {l s='Showing %from%-%to% of %total% item(s)' d='Shop.Theme.Catalog' sprintf=['%from%' => $pagination.items_shown_from ,'%to%' => $pagination.items_shown_to, '%total%' => $pagination.total_items]}
    {/block}
  </div>

  <div class="col-xs-12 col-md-6 col-lg-8">
    {block name='pagination_page_list'}
     {if $pagination.should_be_displayed}
        <ul class="page-list clearfix text-md-right text-xs-center">
          {foreach from=$pagination.pages item="page"}


            <li {if $page.current} class="current" {/if}>
              {if $page.type === 'spacer'}
                <span class="spacer">&hellip;</span>
              {else}
                <a
                  rel="{if $page.type === 'previous'}prev{elseif $page.type === 'next'}next{else}nofollow{/if}"
                  href="{$page.url}"
                  class="{if $page.type === 'previous'}previous {elseif $page.type === 'next'}next {/if}{['disabled' => !$page.clickable, 'js-search-link' => true]|classnames}"
                >
                  {if $page.type === 'previous'}
                    <i class="material-icons">&#xE314;</i>
                  {elseif $page.type === 'next'}
                    <i class="material-icons">&#xE315;</i>
                  {else}
                    {$page.page}
                  {/if}
                </a>
              {/if}
            </li>
          {/foreach}
        </ul>
      {/if}
    {/block}
  </div>

</nav>
