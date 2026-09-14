
{extends file='page.tpl'}

{block name="breadcrumb"}{/block}

{block name='page_title'}
  {$page.title}
{/block}

{capture assign="errorContent"}
  <h4>{l s='No inventaries available' d='Shop.Theme.Catalog'}</h4>
  <p>{l s='Stay tuned! More inventaries will be shown here as they are added.' d='Shop.Theme.Catalog'}</p>
{/capture}

{block name='page_content_container'}
  {include file='errors/not-found.tpl' errorContent=$errorContent}
{/block}
