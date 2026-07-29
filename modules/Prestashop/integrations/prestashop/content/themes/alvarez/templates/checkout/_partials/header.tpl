
{block name='header'}
  {block name='header_nav'}
    <nav class="header-nav">
      <div class="topnav">
        {if isset($fullwidth_hook.displayNav1) AND $fullwidth_hook.displayNav1 == 0}
        <div class="container">
        {/if}
          <div class="inner">{hook h='displayNav1'}</div>
        {if isset($fullwidth_hook.displayNav1) AND $fullwidth_hook.displayNav1 == 0}
        </div>
        {/if}
      </div>
      <div class="bottomnav">
        {if isset($fullwidth_hook.displayNav2) AND $fullwidth_hook.displayNav2 == 0}
          <div class="container">
        {/if}
          <div class="inner">{hook h='displayNav2'}</div>
        {if isset($fullwidth_hook.displayNav2) AND $fullwidth_hook.displayNav2 == 0}
          </div>
        {/if}
      </div>
    </nav>
  {/block}

  {block name='header_top'}
    <div class="header-top">
      {if isset($fullwidth_hook.displayTop) AND $fullwidth_hook.displayTop == 0}
        <div class="container">
      {/if}
        <div class="inner">{hook h='displayTop'}</div>
      {if isset($fullwidth_hook.displayTop) AND $fullwidth_hook.displayTop == 0}
        </div>
      {/if}
    </div>
    {hook h='displayNavFullWidth'}
  {/block}
{/block}
