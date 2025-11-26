{widget name="alvarezbanner" type="home"}

<div class="banner-product-wrapper banner-product-wrapper2 appear-animate pb-1 mt-3 mb-3 fadeIn appear-animation-visible">
    <div class="row">
        {foreach from=$categorias item=categoria}
            <div class="banner-product col-xl-4 col-lg-6 col-md-12 col-sm-12 mb-2" style="animation-duration: 1.2s;">
                <a class="banner banner-fixed overlay-zoom br-xs" href="{$categoria.url}">
                    <figure class="banner-media h-100">
                        <picture >
                            <source srcset="{$categoria.img}"
                                    media="(min-width: 601px)">
                            <source srcset="{$categoria.img}"
                                    media="(max-width: 600px)">
                            <img src="{$categoria.img}" alt="{$categoria.nombre}">
                        </picture>
                        {if $categoria.class != ""}
                            <div class="overlay"></div>
                        {/if}
                        <div class="banner-content">
                            <h3 class="banner-title  text-uppercase ">{$categoria.nombre}</h3>
                        </div>
                    </figure>
                </a>
            </div>
        {/foreach}
    </div>
</div>

<div class="home-description">
    <p>{l s="Welcome to the" mod='alsernetcontents'}  <strong>{l s="leader on-line store in your sport and leader in price." mod='alsernetcontents'}</strong></p>
    <p>{l s="For more than 60 years we have been working to offer you the widest catalogue of products in golf, hunting, fishing, horse riding, diving, recreational boating, padel tennis, skiing, adventure" mod='alsernetcontents'}</p>
    <p><strong>{l s="Select your sport and enter into the online store with more products variety:" mod='alsernetcontents'}</strong> {l s="rom a simple hunting accesory to all the new products on the golf's world. We work with the main brands of each sector, national and international, so we can offer total guarantee in all your shopping. Several products we market, can be bought only at Álvarez" mod='alsernetcontents'}</p>
    <p>{l s="Apart from the greatest variety in sports products, on our online store, you can find different sections such as buyer/seller deals, used firearms, second hand products, noticeboards, and a lot of useful information to do your favourite sport... Enter, become informed and join in! We'll appreciate your comments and suggestions to help us to improve." mod='alsernetcontents'}</p>
    <p>{l s="And with" mod='alsernetcontents'}<strong> {l s="the best price commitment." mod='alsernetcontents'}</strong> {l s="At Álvarez's you can shop with the tranquility that you can't find the same product at a better price in any other place: we commit ourselves to have the best price in all our articles." mod='alsernetcontents'}</p>
    <p>{l s="We have products and brands for all options and budgets; from high range products to articles to start out in each sport. Moreover, to get away your worries, our online store fulfills all the security requirements for personal data treatment, as well as the most advanced systems against fraud on the market " mod='alsernetcontents'} <strong>{l s="Total security in your shopping!" mod='alsernetcontents'}</strong></p>
    <p>{l s="Maximum security before and after purchase, if the purchased product does not meet your expectations, you can return it without problems; we offer a " mod='alsernetcontents'}<strong>{l s="complete return guarantee." mod='alsernetcontents'}</strong> </p>
    {if $iso_code == 'es'}
        <p>{l s="If you prefer, you can also make your purchases in our physical stores. We have stores in " mod='alsernetcontents'} <strong>{l s="Madrid" mod='alsernetcontents'}</strong>{l s="(C/ Poeta Joan Maragall nº60 - before Capitán Haya - and / Diego de León nº56), and" mod='alsernetcontents'} <strong>{l s="La Coruña" mod='alsernetcontents'}</strong> {l s="(Polígono de Pocomaco, primera Avenida , 81. Parcela C-13). Come and visit us, we have at your disposal a wide team of professionals, fans of every sport." mod='alsernetcontents'} </p>
    {/if}
    <p>{l s="Let us advise you, " mod='alsernetcontents'} <strong>{l s="we are experts in each one of our sports" mod='alsernetcontents'}</strong>  {l s="golf, hunting, fishing, horse riding, diving, recreational boating, padel, adventure and leisure time), and we'll be glad to help you in everything you need." mod='alsernetcontents'}</p>
    <p>{l s="More than 1,000,000 customers already trust us, we encourage you to do the same." mod='alsernetcontents'}</p>
</div>
