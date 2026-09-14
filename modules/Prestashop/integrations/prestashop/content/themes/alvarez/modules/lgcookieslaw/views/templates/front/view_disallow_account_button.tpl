
{if isset($lgcookieslaw_ps_version)}
    {if $lgcookieslaw_ps_version == '15'}
        <li class="lgcookieslaw-account-button">
            <a href="{$lgcookieslaw_disallow_url|escape:'quotes':'UTF-8'}" title="{l s='Revoke my consent to cookies' mod='lgcookieslaw'}">
                <span>
                    <img src="{$lgcookieslaw_image_path|escape:'quotes':'UTF-8'}" class="icon" alt="{l s='Revoke my consent to cookies' mod='lgcookieslaw'}">
                    {l s='Revoke my consent to cookies' mod='lgcookieslaw'}
                </span>
            </a>
        </li>
    {elseif $lgcookieslaw_ps_version == '16'}
        <li class="lgcookieslaw-account-button">
            <a href="{$lgcookieslaw_disallow_url|escape:'quotes':'UTF-8'}" title="{l s='Revoke my consent to cookies' mod='lgcookieslaw'}">
                <img src="{$lgcookieslaw_image_path|escape:'quotes':'UTF-8'}">
                <span>{l s='Revoke my consent to cookies' mod='lgcookieslaw'}</span>
            </a>
        </li>
    {else}
        <a class="col-lg-3 col-md-6 col-sm-6 col-xs-12 lgcookieslaw-account-button" href="{$lgcookieslaw_disallow_url|escape:'html':'UTF-8'}" title="{l s='Revoke my consent to cookies' mod='lgcookieslaw'}">
            <span class="link-item">
                <img src="{$lgcookieslaw_image_path|escape:'quotes':'UTF-8'}">
                {l s='Revoke my consent to cookies' mod='lgcookieslaw'}
            </span>
        </a>
    {/if}
{/if}
