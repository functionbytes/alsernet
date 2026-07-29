

{if $logged}
    <div class="account-auth">
        <a class="account auth label-down link " href="{$link->getPageLink('my-account')|escape:'html':'UTF-8'}" >
            <i class="fa-light fa-user-large"></i>
            <span class="account-label d-lg-show">{l s='My account' mod='alsernetauth'}</span>
        </a>
        <ul class="account_links">
            <li>
                <a href="{$link->getPageLink('my-account')|escape:'html':'UTF-8'}">{l s='My init' mod='alsernetauth'}</a>
            </li>
            <li>
                <a href="{$link->getPageLink('identity')|escape:'html':'UTF-8'}">{l s='My information' mod='alsernetauth'}</a>
            </li>
            <li>
                <a href="{$link->getPageLink('addresses')|escape:'html':'UTF-8'}">{l s='My Address account' mod='alsernetauth'}</a>
            </li>
            <li>
                <a href="{$link->getPageLink('history')|escape:'html':'UTF-8'}">{l s='My orders' mod='alsernetauth'}</a>
            </li>
            <a class="account_logout" href="{$link->getPageLink('index', true, null, 'mylogout=')|escape:'html':'UTF-8'}">{l s='Logout' mod='alsernetauth'}</a>
        </ul>
    </div>
{else}
    <a class="account label-down link " href="{$link->getPageLink('authentication')|escape:'html':'UTF-8'}"  title="{l s='Log in to your customer account' mod='alsernetauth'}">
        <i class="fa-light fa-user-hair"></i>
        <span class="account-label d-lg-show">{l s='My account' mod='alsernetauth'}</span>
    </a>
{/if}
