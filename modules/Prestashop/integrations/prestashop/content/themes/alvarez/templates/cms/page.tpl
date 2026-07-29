{extends file=$layout}

{assign var="cms_ids" value=[50, 136, 131, 134, 130, 121, 124, 43, 17, 101, 68, 111, 14, 120, 115]}
{if in_array($cms.id, $cms_ids) || ($cms.id >= 103 && $cms.id < 111)}

    {block name='page_header'}
    {/block}

    {block name='breadcrumb'}
    {/block}


    {block name='main'}
        <section id="content" class="page-content page-cms cms-pages page-cms-{$cms.id}">
            {hook h='displayPages' cms=$cms }
        </section>
    {/block}

{elseif  $cms.id == 91 || $cms.id == 13 || $cms.id == 1 || $cms.id == 12 || $cms.id == 70  }

    {block name='page_header'}
        {include file='_partials/page-header.tpl' title={$cms.meta_title}}
    {/block}

    {block name='breadcrumb'}
    {/block}

    {block name='main'}
        <section id="content" class="page-content page-cms cms-pages page-cms-{$cms.id}">
            {hook h='displayPages' cms=$cms }
        </section>
    {/block}

{else}

    {block name='page_header'}
        {include file='_partials/page-header.tpl' title={$cms.meta_title}}
    {/block}

    {block name='breadcrumb'}
    {/block}

    {block name='content_wrapper'}
        <section id="content" class="page-content page-cms page-cms-{$cms.id}">

            {hook h='displayPages'  cms=$cms  }

            {block name='cms_content'}
                {if isset($referrer) && $referrer && $referrer.model != ''}
                    {if $referrer && $referrer.model != ''}
                        {if $referrer.model|lower == 'product'}
                            <h3>{l s='Product' d='Shop.Theme.Catalog'}: {$referrer.name}</h3>
                        {/if}
                        <div style="display: none;" class="inputs-hidden-referrer-source">
                            <input type="hidden" name="referrer-model" value="{$referrer.model}"/>
                            <input type="hidden" name="referrer-id" value="{$referrer.id}"/>
                            <input type="hidden" name="referrer-name" value="{$referrer.name}"/>
                        </div>
                    {/if}
                {/if}
                {$cms.content nofilter}
            {/block}

            {block name='hook_cms_dispute_information'}
                {hook h='displayCMSDisputeInformation'}
            {/block}

            {block name='hook_cms_print_button'}
                {hook h='displayCMSPrintButton'}
            {/block}

        </section>
    {/block}

{/if}