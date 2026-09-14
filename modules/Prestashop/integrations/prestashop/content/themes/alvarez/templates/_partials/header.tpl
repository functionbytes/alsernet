
{block name='header_banner'}
  <div class="header-banner">
    {hook h='displayBanner'}
  </div>
{/block}

{block name='header_nav'}
  {hook h='displayNav1'}
  <nav class="header-nav">
    {hook h='displayNav2'}
  </nav>
{/block}

{block name='header_top'}
  <div class="header-top">
          {hook h='displayTop'}
  </div>
  {hook h='displayNavFullWidth'}
{/block}


