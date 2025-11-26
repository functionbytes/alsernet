<div class="container">
    <div class="header-left mr-md-4">
        <a href="#" class="mobile-menu-toggle" aria-label="menu-toggle">
         <i class="fa-regular fa-bars"></i>
        </a>
        <div class="header-center">
            <a href="{$urls.pages.index}" class="logo ml-lg-0">
                {if $event_classes == 'event event-navidad-sin-iva'}
                    <img src="/themes/alvarez/assets/img/theme/logo/{$language.iso_code}/logo-navidad.png" alt="logo" width="200"/>
                {else}
                    <img src="/themes/alvarez/assets/img/theme/logo/{$language.iso_code}/logo.svg" alt="logo" width="200"/>
                {/if}
            </a>
        </div>
        <div class="header-mobile-search">
            {widget name='ambjolisearch' hook='displayNav2'}
        </div>
    </div>
    <div class="header-right ml-4">
        {widget name="alsernetcustomer" hook='displayNav2' action="wishlist" }
        {widget name='alsernetauth' hook='displayNav2' action='auth'}
        {widget name="alsernetshopping" hook='displayNav2' action="shopping" }
    </div>
</div>
