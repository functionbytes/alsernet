
{block name='social_sharing'}
    {if isset($social_share_links) && $social_share_links|is_array}
        <div class="sharing-container">
            <div class="sharing-content">
                <h4 class="icon-box-title">{l s='Share' mod='alsernetproducts'}</h4>
                <div class="social-links">
                    <div class="social-icons social-no-color border-thin">
                        {foreach from=$social_share_links item='social_share_link'}
                            <a href="{$social_share_link.url}" class="social-icon">
                                <i class="fa-brands fa-{$social_share_link.class}"></i>
                            </a>
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    {/if}
{/block}
