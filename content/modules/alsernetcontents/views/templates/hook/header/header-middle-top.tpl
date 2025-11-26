<div class="header-action">
    <div class="container">
        <div class="header-right">

            {if $language.iso_code == "es"}
                <a href="/blog" class="">{l s='Blog' mod='alsernetcontents'}</a>
                <a href="{$link->getCMSLink(91)}" class="">{l s='Stores' mod='alsernetcontents'}</a>
                <a href="/contacto" class="">Contacto</a>
                <a href="tel:+34{l s='Phone' mod='alsernetcontents'}" class="">{l s='Phone' mod='alsernetcontents'}</a>
                <span class="divider"></span>
            {/if}

            <div class="dropdown">
                <a class="select-dropdown" title="{l s='Language' d='Shop.Theme.Global'}"aria-label="{l s='Language dropdown' d='Shop.Theme.Global'}">
                    <img src="{$languages.current_language.lang_url|escape:'html':'UTF-8'}{$languages.current_language.id_lang|escape:'html':'UTF-8'}.jpg"
                         alt="{$languages.current_language.iso_code|escape:'html':'UTF-8'}" width="14" height="8"
                         class="dropdown-image">
                    {$languages.current_language.name_simple|truncate:2:"":true}
                </a>
                <div class="dropdown-box">
                    {foreach from=$languages.languages item=language}
                        {capture "enlaceidioma"}
                            {if isset($language_switch_urls[$language.id_lang])}
                                {$language_switch_urls[$language.id_lang]|escape:'html':'UTF-8'}
                            {else}
                                {url entity='language' id=$language.id_lang}
                            {/if}
                        {/capture}
                        <a href="{$smarty.capture.enlaceidioma|regex_replace:'/(\/?\?.*)$/':''}">
                            <img src="{$language.lang_url|escape:'html':'UTF-8'}{$language.id_lang|escape:'html':'UTF-8'}.jpg"
                                 alt="{$language.iso_code|escape:'html':'UTF-8'}" width="14" height="8"
                                 class="dropdown-image">
                            {$language.name_simple}
                        </a>
                    {/foreach}
                </div>
            </div>

        </div>
    </div>
</div>
