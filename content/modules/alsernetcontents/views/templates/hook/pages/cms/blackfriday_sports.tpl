<div class="blackfriday">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="container-welcome">
                    <picture>
                        <source srcset="/themes/alvarez/assets/img/theme/cms/68/{$iso_code}/banner-desktop-{$deporte}-2025-{$iso_code}.webp"
                                media="(min-width: 601px)">
                        <source srcset="/themes/alvarez/assets/img/theme/cms/68/{$iso_code}/banner-mobile-{$deporte}-2025-{$iso_code}.webp"
                                media="(max-width: 600px)">
                        <img src="/themes/alvarez/assets/img/theme/cms/68/{$iso_code}/banner-desktop-{$deporte}-2025-{$iso_code}.webp"
                             alt="Black Friday" title="Black Friday">
                    </picture>
                </div>
                <!--<div class="message">
                    <p>{$after.$iso_code}</p>
                </div>-->
                <div class="black-deportes-wrapper">

                    <div class="row">
                        {foreach from=$imagenes.$iso_code item=img}
                            <div class="col-6 col-md-4 col-lg-4 mb-2 mb-lg-4 {if $img.title == "0"}mobile-hidden0{elseif $img.title == "0"}mobile-hidden1{elseif $img.title == "2"}mobile-hidden2{/if}">
                                <div class="banner-product">
                                    <a class="banner banner-fixed br-xs" href="{$img.url}">
                                        <figure class="banner-media h-100">
                                            <img src="/themes/alvarez/assets/img/theme/cms/68/imagenes_deportes/{$deporte}/{$img.image}"
                                                 alt="{$img.title}">
                                            <div class="overlay"></div>
                                            {if $img.title != "0" && $img.title != "1" && $img.title != "2"}
                                                <div class="banner-content">
                                                    <h2 class="banner-title  text-uppercase ">{$img.title}</h2>
                                                </div>
                                            {/if}
                                        </figure>
                                    </a>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="container-btn">
                    <a class="btn" href="{$botones.$iso_code.url}">{$botones.$iso_code.texto}</a>
                </div>
            </div>
            <div class="message">
                <h1>{$h1.$iso_code}</h1>
                <dd class="value">
                    <p>
                        {$texts.$iso_code|escape:'htmlall'|nl2br nofilter}
                    </p>
                </dd>
            </div>
        </div>
    </div>
</div>
</div>