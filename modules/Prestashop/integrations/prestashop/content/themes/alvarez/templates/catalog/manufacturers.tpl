{extends file=$layout}

{block name="left_column"}
<div id="left-column" class="col-xs-12 col-lg-3 col-sm-12 ">
    {widget name='alsernetmenu' type='brands' sports=$deportes }
</div>
{/block}

{block name="content_wrapper"}

<div id="content-wrapper" class="manufacturer js-content-wrapper left-column right-column col-xs-12 col-lg-9 col-sm-12">
    <section id="main">

        {block name='brand_miniature'}
        <h2 class="title">{l s='All brands' d='Shop.Theme.Catalog'}</h2>
        <div class="letters">
            <div class="items-letters">
                {$current_letter = '#'}
                <ul>
                    {assign var="current_url" value=$smarty.server.REQUEST_URI}
                    {assign var="deporte" value=''}
                    {if preg_match('#^/([^/]+)(?:/([^/]+))?#', $current_url, $matches)}
                    {* Lista de idiomas *}
                    {assign var="idiomas" value=['fr', 'it', 'pt', 'en']}

                    {* Verifica si el primer segmento es un idioma *}
                    {if in_array($matches[1], $idiomas)}
                    {* Si es idioma, deporte está en el segundo segmento *}
                    {assign var="deporte" value=$matches[2]}
                    {else}
                    {* Si no es idioma, deporte es el primer segmento *}
                    {assign var="deporte" value=$matches[1]}
                    {/if}
                    {/if}
                    {assign var="brands_unido" value=[]}
                    {if $brands[$deporte]|@count > 0}

                    {* Si existe el deporte, usamos solo esa parte *}
                    {assign var="brands_unido" value=$brands[$deporte]}
                    {else}
                    {* Si no hay deporte, estamos en listado-marcas: unimos todo *}
                    {foreach $brands as $grupo}
                    {foreach $grupo as $k => $v}
                    {$brands_unido[$k] = $v}
                    {/foreach}
                    {/foreach}
                    {/if}

                    {foreach from=$brands_unido item=brand}
                    {$first_letter = $brand.name|substr:0:1|upper}


                    {if $current_letter != $first_letter}

                    {$current_letter = $first_letter|upper}
                    {if Context::getContext()->isMobile() != 1}
                    <li class="item-letter"><a
                            onclick="ir_a_letra('{$current_letter|upper}');">{$current_letter|upper}</a></li>
                    {else}
                    <li class="item-letter"><a rel="nofollow"
                            onclick="ir_a_letra('{$current_letter|upper}');">{$current_letter|upper}</a></li>
                    {/if}
                    {/if}
                    {/foreach}
                </ul>
            </div>
        </div>
        <div class="brands">

            {$current_letter = ''}
            {foreach from=$brands_unido item=brand}
            {$first_letter = $brand.name|substr:0:1|upper}
            {if $current_letter != $first_letter}
            {if $current_letter != ''}
        </div> <!-- Cierra el div de .items-brands anterior -->
</div> <!-- Cierra el div de .brand-title anterior -->
{/if}
{$current_letter = $first_letter}
<div id="{$current_letter}" class="box-brands">
    <p class="brand-title">{$current_letter}</p>
    <div class="row items-brands "> <!-- Comienza el nuevo div de .items-brands -->
        {/if}

        <div class="col-lg-6 col-sm-6 col-xs-6">
            {if $language.iso_code == "es"}
            <a
                href="/m/{$brand.link_rewrite|default:$brand.name|lower|replace:' ':'_'|replace:'.':'_'|replace:',':'_'|replace:'+':''|replace:'-':'_'|replace:'\'':'_'|replace:'&':''|replace:'¨':''|replace:'ä':'a'|replace:'ë':'e'|replace:'ï':'i'|replace:'ö':'o'|replace:'ü':'u'|replace:'á':'a'|replace:'é':'e'|replace:'í':'i'|replace:'ó':'o'|replace:'ú':'u'}">
                {else}
                <a
                    href="/{$language.iso_code}/m/{$brand.link_rewrite|default:$brand.name|lower|replace:' ':'_'|replace:'.':'_'|replace:',':'_'|replace:'+':''|replace:'-':'_'|replace:'\'':'_'|replace:'&':''|replace:'¨':''|replace:'ä':'a'|replace:'ë':'e'|replace:'ï':'i'|replace:'ö':'o'|replace:'ü':'u'|replace:'á':'a'|replace:'é':'e'|replace:'í':'i'|replace:'ó':'o'|replace:'ú':'u'}">
                    {/if}
                    {$brand.name}
                </a>
        </div>
        {/foreach}
    </div> <!-- Cierra el último div de .items-brands -->
</div> <!-- Cierra el último div de .brand-title -->
</div>
</div>

{/block}

</section>
</div>
{/block}
