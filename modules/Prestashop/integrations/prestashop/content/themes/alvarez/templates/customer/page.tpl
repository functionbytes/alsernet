
{extends file='page.tpl'}

{block name='notifications'}{/block}

{block name='page_content_container'}

    <div class="user-dashboard-section" id="content" >
        <div class="col-xxl-3 col-lg-3 col-md-12 col-sm-12">
            {include file='customer/_partials/nav.tpl'}
        </div>
        <div class="col-xxl-9 col-lg-9 col-md-12 col-sm-12">
            {block name='page_content'}
            {/block}
        </div>
        <div class="col-xxl-12 col-lg-12 col-md-12 col-sm-12">

            {block name='page_rocket'}
            {/block}
        </div>

    </div>

{/block}
