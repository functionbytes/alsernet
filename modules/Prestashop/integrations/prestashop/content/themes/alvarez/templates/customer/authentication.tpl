
{extends file='page.tpl'}

{block name='breadcrumb'}
                
{/block}

{block name='page_content'}
    {block name='login_form_container'}
      {hook h='displayAuthLogin'}
    {/block}
{/block}
