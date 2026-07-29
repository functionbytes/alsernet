
{extends file='layouts/layout-both-columns.tpl'}

{block name='left_column'}{/block}

{block name='content_wrapper'}
  <div id="content-wrapper" class="js-content-wrapper right-column col-xs-12 col-md-8 col-lg-9">
    {hook h="displayContentWrapperTop"}
    {block name='content'}
      <p>Hello world! This is HTML5 Boilerplate.</p>
    {/block}
    {hook h="displayContentWrapperBottom"}
  </div>
{/block}
