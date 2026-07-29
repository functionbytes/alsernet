<div class="dashboard-left-sidebar">
    <div class="close-button d-flex d-lg-none">
        <button class="close-sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="profile-box">
        <div class="profile-contain">
            <div class="profile-image ">
                    <div class="profile-container">
                    <i class="fa-duotone fa-light fa-user-hair blur-up update_img lazyloaded" ></i>
                    </div>
            </div>
            <div class="profile-name">
                {if isset($customerauth)}
                    <h3>
                        {$customerauth.firstname|regex_replace:'/ .*/':''|capitalize} {$customerauth.lastname|substr:0:1|upper}.
                    </h3>
                    <h6 class="text-content">{$customerauth.email}</h6>
                {/if}
            </div>
        </div>
    </div>

    <ul class="nav nav-pills user-nav-pills" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {if $page.page_name == 'my-account'}active{/if}" href="{$link->getPageLink('my-account', true)}">
                <i class="fa-light fa-house"></i>
                {l s='Dashboard' d='Shop.Customer.Nav'}
            </a>
        </li>

        {if !$configuration.is_catalog}
            <li class="nav-item" role="presentation">
                <a class="nav-link {if $page.page_name == 'history'}active{/if}" href="{$link->getPageLink('history', true)}">
                    <i class="fa-light fa-bag-shopping"></i>
                    {l s='Orders' d='Shop.Customer.Nav'}
                </a>
            </li>
        {/if}

        <li class="nav-item" role="presentation">
            <a class="nav-link {if $page.page_name == 'wishlist'}active{/if}" href="{$link->getPageLink('wishlist', true)}">
                <i class="fa-light fa-heart"></i>
                {l s='Wishlists' d='Shop.Customer.Nav'}
            </a>
        </li>

        <li class="nav-item" role="presentation">
                <a class="nav-link {if $page.page_name == 'addresses'}active{/if}" href="{$link->getPageLink('addresses', true)}">
                    <i class="fa-light fa-location-dot"></i>
                    {l s='Addresses' d='Shop.Customer.Nav'}
                </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {if $page.page_name == 'identity'}active{/if}" href="{$link->getPageLink('identity', true)}">
                <i class="fa-regular fa-user"></i>
                {l s='Information' d='Shop.Customer.Nav'}
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {if $page.page_name == 'gdpr'}active{/if}" href="{$link->getPageLink('gdpr', true)}">
                <i class="fa-regular fa-shield-exclamation"></i>
                {l s='My personal data' d='Shop.Customer.Nav'}
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a class="nav-link {if $page.page_name == 'cookies'}active{/if}" href="{$link->getPageLink('cookies', true)}">
                <i class="fa-regular fa-cookie-bite"></i>
                {l s='Revoke cookie consent' d='Shop.Customer.Nav'}
            </a>
        </li>

        <li class="nav-item logout-cls" role="presentation">
            <a href="{$link->getPageLink('index', true, null, 'mylogout')}" class="btn">
                <i class="fa-regular fa-arrow-right-from-bracket"></i>
                {l s='Sign out' d='Shop.Customer.Nav'}
            </a>
        </li>
    </ul>
</div>
