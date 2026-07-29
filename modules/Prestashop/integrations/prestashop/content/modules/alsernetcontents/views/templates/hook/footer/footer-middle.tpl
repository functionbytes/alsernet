


<div class="container">
    <div class="row">
        <div class="col-lg-4 col-sm-12">
            <div class="widget widget-about">
                <h3 class="widget-title">{l s='About Us' mod='alsernetcontents'}</h3>
                <div class="widget-body">
                    <div class="widget-about">
                        <p class="widget-about-title">{l s='Description footer' mod='alsernetcontents'}.</p>
                        <div class="widget-about-item">
                            {if $iso == "es"}
                                <a href="tel:+34981179100" class="widget-phone-call"><i class="fa-regular fa-phone"></i>981 17 91 00</a>
                            {/if}
                            <a href="mailto:web@a-alvarez.com" class="widget-email-call"><i
                                        class="fa-regular fa-envelope"></i> web@a-alvarez.com </a>
                            {if $iso == "es"}
                            <a href="{$link->getCMSLink(91)}" class="widget-store-call"><i
                                        class="fa-regular fa-store"></i> {l s='Stores' mod='alsernetcontents'}</a>
                            {/if}
                        </div>
                    </div>

                    <div class="widget-language">
                        <p class="widget-lenguages-title">{l s='Lenguages' mod='alsernetcontents'}</p>
                        <ul class="selector">

                            {foreach from=$languages.languages item=language}
                                {capture "enlaceidioma"}{url entity='language' id=$language.id_lang}{/capture}
                                <li {if $language.id_lang == $languages.current_language.id_lang} class="current" {/if}>
                                    <a href="{$smarty.capture.enlaceidioma|regex_replace:'/(\/?\?.*)$/':''}" data-iso-code="{$language.iso_code}">
                                        <img class="img-fluid"
                                             src="{$language.lang_url|escape:'html':'UTF-8'}{$language.id_lang|escape:'html':'UTF-8'}.jpg"
                                             alt="{$language.iso_code|escape:'html':'UTF-8'}"/>
                                    </a>
                                </li>
                            {/foreach}

                        </ul>
                    </div>

                    <div class="widget-app">
                        <p class="widget-app-title">{l s='Download our App' mod='alsernetcontents'}</p>
                        <ul>
                            <li>
                                <a target="_blank"
                                   href="https://play.google.com/store/apps/details?id=com.alvarez.mobile">
                                    <img src="/themes/alvarez/assets/img/theme/footer/app/{$iso}/play-store.png" alt=""
                                         width="100">
                                </a>
                            </li>
                            <li>
                                <a target="_blank" href="https://apps.apple.com/app/id1069006948">
                                    <img src="/themes/alvarez/assets/img/theme/footer/app/{$iso}/apple-store.png" alt=""
                                         width="100">
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        {foreach from=$footers item=footer}
        <div class="col-sp-12 col-xs-12 col-sm-12 col-md-6 col-lg-{$size} col-xl-{$size} ">
            <div class="widget">
                <h3 class="widget-title">{$footer.title}</h3>
                <ul class="widget-body">
                    {foreach from=$footer.items item=item}
                        <li><a href="{$item.url}">{$item.title}</a></li>
                    {/foreach}
                    {if $footer.id == 2  && $iso == "es"}
                        <li>
                            <div class="widget-app">
                                <img src="/themes/alvarez/assets/img/theme/footer/app/es/emprega.jpg" alt="" >
                            </div>
                        </li>
                    {/if}
                </ul>
            </div>
        </div>
        {/foreach}
    </div>
</div>