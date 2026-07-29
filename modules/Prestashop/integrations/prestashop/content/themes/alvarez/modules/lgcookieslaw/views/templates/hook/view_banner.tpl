{if isset($lgcookieslaw_view_header_content) && !empty($lgcookieslaw_view_header_content)}
    {$lgcookieslaw_view_header_content nofilter} {* HTML CONTENT *}
{/if}

<div id="lgcookieslaw_banner" class="lgcookieslaw-banner{if isset($lgcookieslaw_banner_position)} lgcookieslaw-banner-{if $lgcookieslaw_banner_position == 1}top{elseif $lgcookieslaw_banner_position == 2}bottom{elseif $lgcookieslaw_banner_position == 3}floating{/if}{/if}{if isset($lgcookieslaw_show_reject_button) && $lgcookieslaw_show_reject_button} lgcookieslaw-reject-button-enabled{/if}{if isset($lgcookieslaw_show_close_button) && $lgcookieslaw_show_close_button} lgcookieslaw-banner-close-button-enabled{/if}">
    {if isset($lgcookieslaw_show_close_button) && $lgcookieslaw_show_close_button}
        <div class="lgcookieslaw-banner-close-button">
            <i class="lgcookieslaw-icon-close"></i>
        </div>
    {/if}

    <div class="container">
        <div class="lgcookieslaw-banner-message">
            {$lgcookieslaw_banner_message nofilter} {* HTML CONTENT *}
            <a class="lgcookieslaw-info-link lgcookieslaw-link"{if isset($lgcookieslaw_info_link_target) && $lgcookieslaw_info_link_target} target="_blank"{/if} href="{$lgcookieslaw_info_link_url|escape:'quotes':'UTF-8'}">{stripslashes($lgcookieslaw_info_link_title|escape:'htmlall':'UTF-8')}</a>
        </div>
        <div class="lgcookieslaw-button-container">
            <a id="lgcookieslaw_customize_cookies_link" style="width: 30%;border-color:#90BB13!important;border-width:2px;border-style:outset;padding-top:12px;font-family: 'Noto Sans', sans-serif;display: inline-block;text-align:center;width: 30%;font-size: 18px;text-transform:uppercase;background-color:#90BB13;border-color:#90BB13;color:#FFF;" class="lgcookieslaw_customize_cookies_link_new lgcookieslaw-button lgcookieslaw-customize-cookies-link lgcookieslaw-link">{l s='Customize' d='Shop.Theme.Actions'}</a>
		    <button class="lgcookieslaw-button lgcookieslaw-accept-button">
                {*{stripslashes($lgcookieslaw_accept_button_title|escape:'htmlall':'UTF-8')}*}
                {l s='Accept' d='Shop.Theme.Actions'}
            </button>
            {if isset($lgcookieslaw_show_reject_button) && $lgcookieslaw_show_reject_button}
                <button class="lgcookieslaw-button lgcookieslaw-reject-button">
                    {stripslashes($lgcookieslaw_reject_button_title|escape:'htmlall':'UTF-8')}
                </button>
            {/if}
        </div>
    </div>
</div>

<div id="lgcookieslaw_modal" class="lgcookieslaw-modal">
    <div class="lgcookieslaw-modal-header">
        <h2 class="lgcookieslaw-modal-header-title">
            {l s='Cookie preferences' mod='lgcookieslaw'}

            <div class="lgcookieslaw-modal-header-title-user-consent-elements">
                <div
                    class="lgcookieslaw-badge lgcookieslaw-tooltip-container lgcookieslaw-user-consent-consent-date"
                    role="tooltip"
                    title="{if !empty($lgcookieslaw_cookie_values) && isset($lgcookieslaw_cookie_values->lgcookieslaw_user_consent_consent_date)}{l s='Last updated' mod='lgcookieslaw'}: {$lgcookieslaw_cookie_values->lgcookieslaw_user_consent_consent_date|date_format:'%d/%m/%Y %H:%M:%S'|escape:'htmlall':'UTF-8'}{/if}"
                >
                    <i class="lgcookieslaw-icon-schedule"></i> <span class="lgcookieslaw-user-consent-consent-date-text">{if !empty($lgcookieslaw_cookie_values) && isset($lgcookieslaw_cookie_values->lgcookieslaw_user_consent_consent_date)}{$lgcookieslaw_cookie_values->lgcookieslaw_user_consent_consent_date|date_format:'%d/%m/%Y %H:%M:%S'|escape:'htmlall':'UTF-8'}{/if}</span>
                </div>
                <a
                    class="lgcookieslaw-badge lgcookieslaw-tooltip-container lgcookieslaw-user-consent-download"
                    role="tooltip"
                    title="{l s='Click to download consent' mod='lgcookieslaw'}"
                    target="_blank"
                    href="{if !empty($lgcookieslaw_cookie_values) && isset($lgcookieslaw_cookie_values->lgcookieslaw_user_consent_download_url)}{$lgcookieslaw_cookie_values->lgcookieslaw_user_consent_download_url|escape:'quotes':'UTF-8'}{/if}"
                >
                    <i class="lgcookieslaw-icon-download"></i> {l s='Consent' mod='lgcookieslaw'}
                </a>
            </div>
        </h2>
    </div>
    <div class="lgcookieslaw-modal-body">
        <div class="lgcookieslaw-modal-body-content">
            {if isset($lgcookieslaw_purposes) && !empty($lgcookieslaw_purposes)}
                {foreach $lgcookieslaw_purposes as $lgcookieslaw_purpose}
                    <div class="lgcookieslaw-section">
                        <div class="lgcookieslaw-section-name">
                            {$lgcookieslaw_purpose.name|escape:'html':'UTF-8'}{if $lgcookieslaw_purpose.technical}
                                <div
                                    class="lgcookieslaw-badge lgcookieslaw-tooltip-container"
                                    role="tooltip"
                                    title="{l s='Mandatory' mod='lgcookieslaw'}"
                                >
                                    {l s='Technical' mod='lgcookieslaw'}
                                </div>
                            {/if}
                        </div>
                        <div class="lgcookieslaw-section-checkbox">
                            <div class="lgcookieslaw-switch{if $lgcookieslaw_purpose.technical} lgcookieslaw-switch-disabled{/if}">
                                <div class="lgcookieslaw-slider-option lgcookieslaw-slider-option-left">{l s='No' mod='lgcookieslaw'}</div>
                                <input
                                    type="checkbox"
                                    id="lgcookieslaw_purpose_{$lgcookieslaw_purpose.id_lgcookieslaw_purpose|intval}"
                                    class="lgcookieslaw-purpose"
                                    data-id-lgcookieslaw-purpose="{$lgcookieslaw_purpose.id_lgcookieslaw_purpose|intval}"
                                    data-consent-mode="{if $lgcookieslaw_purpose.consent_mode}true{else}false{/if}"
                                    {if $lgcookieslaw_purpose.consent_mode}data-consent-type="{$lgcookieslaw_purpose.consent_type|escape:'htmlall':'UTF-8'}"{/if}
                                    data-technical="{if $lgcookieslaw_purpose.technical}true{else}false{/if}"
                                    data-checked="{if $lgcookieslaw_purpose.checked}true{else}false{/if}"
                                />
                                <span
                                    id="lgcookieslaw_slider_{$lgcookieslaw_purpose.id_lgcookieslaw_purpose|intval}"
                                    class="lgcookieslaw-slider{if $lgcookieslaw_purpose.checked} lgcookieslaw-slider-checked{/if}"
                                ></span>
                                <div class="lgcookieslaw-slider-option lgcookieslaw-slider-option-right">{l s='Yes' mod='lgcookieslaw'}</div>
                            </div>
                        </div>
                        <div class="lgcookieslaw-section-purpose">
                            <a class="lgcookieslaw-section-purpose-button collapsed" data-toggle="collapse" href="#multi_collapse_lgcookieslaw_purpose_{$lgcookieslaw_purpose.id_lgcookieslaw_purpose|intval}" role="button" aria-expanded="false" aria-controls="multi_collapse_lgcookieslaw_purpose_{$lgcookieslaw_purpose.id_lgcookieslaw_purpose|intval}">
                                <span class="lgcookieslaw-section-purpose-button-title">{l s='Description' mod='lgcookieslaw'}{if isset($lgcookieslaw_purpose.associated_cookies) && !empty($lgcookieslaw_purpose.associated_cookies)} {l s='and cookies' mod='lgcookieslaw'}{/if}</span>
                            </a>
                            <div class="lgcookieslaw-section-purpose-content collapse multi-collapse" id="multi_collapse_lgcookieslaw_purpose_{$lgcookieslaw_purpose.id_lgcookieslaw_purpose|intval}">
                                <div class="lgcookieslaw-section-purpose-content-description">
                                    {$lgcookieslaw_purpose.description nofilter} {* HTML CONTENT *}
                                </div>

                                {if isset($lgcookieslaw_purpose.associated_cookies) && !empty($lgcookieslaw_purpose.associated_cookies)}
                                    <div class="lgcookieslaw-section-purpose-content-cookies">
                                        <div class="table-responsive">
                                            <table class="lgcookieslaw-section-purpose-content-cookies-table table">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            <span
                                                                class="lgcookieslaw-tooltip-container"
                                                                role="tooltip"
                                                                title="{l s='Name of the cookie' mod='lgcookieslaw'}"
                                                            >
                                                                {l s='Cookie' mod='lgcookieslaw'}
                                                            </span>
                                                        </th>
                                                        <th>
                                                            <span
                                                                class="lgcookieslaw-tooltip-container"
                                                                role="tooltip"
                                                                title="{l s='Domain associated with the cookie' mod='lgcookieslaw'}"
                                                            >
                                                                {l s='Provider' mod='lgcookieslaw'}
                                                            </span>
                                                        </th>
                                                        <th>
                                                            <span
                                                                class="lgcookieslaw-tooltip-container"
                                                                role="tooltip"
                                                                title="{l s='Cookie purpose' mod='lgcookieslaw'}"
                                                            >
                                                                {l s='Purpose' mod='lgcookieslaw'}
                                                            </span>
                                                        </th>
                                                        <th>
                                                            <span
                                                                class="lgcookieslaw-tooltip-container"
                                                                role="tooltip"
                                                                title="{l s='Cookie expiration time' mod='lgcookieslaw'}"
                                                            >
                                                                {l s='Expiry' mod='lgcookieslaw'}
                                                            </span>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {foreach $lgcookieslaw_purpose.associated_cookies as $lgcookieslaw_cookie}
                                                        <tr>
                                                            <td>{$lgcookieslaw_cookie.name|escape:'htmlall':'UTF-8'}</td>
                                                            <td>{if !empty($lgcookieslaw_cookie.provider_url)}<a href="{$lgcookieslaw_cookie.provider_url|escape:'htmlall':'UTF-8'}">{/if}{$lgcookieslaw_cookie.provider|escape:'htmlall':'UTF-8'}{if !empty($lgcookieslaw_cookie.provider_url)}</a>{/if}</td>
                                                            <td>{$lgcookieslaw_cookie.cookie_purpose nofilter} {* HTML CONTENT *}</td>
                                                            <td>{$lgcookieslaw_cookie.expiry_time|escape:'htmlall':'UTF-8'}</td>
                                                        </tr>
                                                    {/foreach}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/foreach}
            {/if}
        </div>
    </div>
    <div class="lgcookieslaw-modal-footer">
        <div class="lgcookieslaw-modal-footer-left">
            <button id="lgcookieslaw_cancel_button" class="lgcookieslaw-button lgcookieslaw-cancel-button">{l s='Cancel' mod='lgcookieslaw'}</button>
        </div>
        <div class="lgcookieslaw-modal-footer-right">
            {if isset($lgcookieslaw_show_reject_button) && $lgcookieslaw_show_reject_button}
                <button class="lgcookieslaw-button lgcookieslaw-reject-button">{l s='Reject all' mod='lgcookieslaw'}</button>
            {/if}

            <button class="lgcookieslaw-button lgcookieslaw-partial-accept-button">{l s='Accept selection' mod='lgcookieslaw'}</button>
            <button class="lgcookieslaw-button lgcookieslaw-accept-button">{l s='Accept all' mod='lgcookieslaw'}</button>
        </div>
    </div>
</div>

<div class="lgcookieslaw-overlay"></div>

{if isset($lgcookieslaw_show_fixed_button) && $lgcookieslaw_show_fixed_button}
    <div id="lgcookieslaw_fixed_button" class="lgcookieslaw-fixed-button lgcookieslaw-fixed-button-{if isset($lgcookieslaw_fixed_button_position) && $lgcookieslaw_fixed_button_position}{$lgcookieslaw_fixed_button_position|escape:'html':'UTF-8'}{else}left{/if}">
        <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 20 20" height="36px" viewBox="0 0 20 20" width="36px" fill="#FFFFFF"><g><rect fill="none" height="20" width="20" x="0"/></g><g><g><circle cx="8.75" cy="7.25" r="1.25"/><circle cx="6.75" cy="11.25" r="1.25"/><circle cx="12.5" cy="12.5" r=".75"/><path d="M17.96,9.2C16.53,9.17,15,7.64,15.81,5.82c-2.38,0.8-4.62-1.27-4.15-3.65C5.27,0.82,2,6.46,2,10c0,4.42,3.58,8,8,8 C14.71,18,18.43,13.94,17.96,9.2z M10,16.5c-3.58,0-6.5-2.92-6.5-6.5c0-3.2,2.69-6.69,6.65-6.51c0.3,2.04,1.93,3.68,3.99,3.96 c0.05,0.3,0.4,2.09,2.35,2.93C16.31,13.67,13.57,16.5,10,16.5z"/></g></g></svg>
    </div>
{/if}
