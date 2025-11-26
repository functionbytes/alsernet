<div class="page-product-fitting page-product-default">
    <div class="container">
        <div class="">



            <div class="row">
                <div class="col-sm-12  col-md-12  mb-2">
                    <div class="img-fitting">
                        <!-- Mostrar solo en pantallas md en adelante (escritorio) -->
                        <!-- Mostrar solo en pantallas md en adelante (escritorio) -->
                        <a href="javascript:void(0);" onclick="scrollToElement('#add-to-cart-or-refresh')" class="d-none d-md-block w-100 mt-2 mb-2">
                            <img src="/themes/alvarez/assets/img/theme/product/fitting-pc.jpg" alt="Fitting PC"/>
                        </a>

                        <!-- Mostrar solo en pantallas menores a md (móviles) -->
                        <a href="javascript:void(0);" onclick="scrollToElement('#add-to-cart-or-refresh')" class="d-md-none w-100 mt-2 mb-2">
                            <img src="/themes/alvarez/assets/img/theme/product/fitting-mobile.jpg" alt="Fitting Mobile"/>
                        </a>

                    </div>
                </div>
                <div class="col-sm-12  col-md-5  mb-2">
                    <iframe width="360" height="640" src="https://www.youtube.com/embed/LNHBG9Pnask?si=lhUYcZeoeyJcFlOg" title="YouTube video player" frameborder="0"></iframe>
                </div>
                <div class="col-sm-12  col-md-7  ">
                    <div class="content">
                        <h4 class="text-left">IMPORTANTE: Para un correcto estudio es necesario que traigas tus palos habituales</h4>
                        <h4 class="text-left">El fitting tendrá lugar en: Álvarez , C/Poeta Joan Maragall, nº60, Madrid</h4>
                        <p><strong>VEN A PROBAR NUESTRA NUEVA TECNOLOGÍA DE FITTINGS</strong>; te ayudaremos a mejorar tus resultados, sea cual sea tu nivel y hándicap.</p>
                        <p><strong>En nuestra tienda de C/Poeta Joan Maragall, nº60 hemos incorporado un sistema de tecnología punta </strong>que nos permite medir desde la velocidad de la cabeza del palo y el ángulo de lanzamiento hasta el spin de la bola y la eficiencia del impacto, datos cruciales que antes eran difíciles de obtener.Con toda esta información detallada, podemos recomendar y ajustar el equipamiento que se adapte perfectamente a tu swing individual.Ajustaremos el loft, el lie, la longitud, el peso y la varilla hasta encontrar la combinación perfecta que te permita liberar todo tu potencial en el campo.</p>
                        <p><strong>Con más de 250 cabezas y más de 500 varillas de las principales marcas; podrás probar infinitas combinaciones hasta encontrar los palos perfectos para tu juego.</strong>Entiende tu swing y mejora tu juego con la tecnología más avanzada del mercado.</b></i><strong><i></i></strong></p>
                        <p><strong>SOLUCIONES PERSONALIZADAS PARA CADA GOLFISTA.</strong></p>

                        <ul>
                            <li>
                                <p>El coste del fitting es de <strong>45€.</strong></p>
                            </li>
                            <li>
                                <p>Podrás descontar estos <strong>45€.</strong> en la compra de material, para lo cual dispondrás de un plazo máximo de 10 días desde la realización del fitting.</p>
                            </li>
                        </ul>

                        <p><b>RESERVA AHORA, NO TE QUEDES SIN CITA</b></p>
                    </div>
                </div>
            </div>

        </div>
        <div class="">
            <div class="details">
                {if count($groups) > 0}
                <div class="product-actions fitting">
                    <form id="add-to-cart-or-refresh">
                        <input type="hidden" name="token" value="{$static_token}">
                        <input type="hidden" name="id_product" value="{$product.id}" id="id_product">
                        <input type="hidden" name="id_product_attribute" value="{$product.id_product_attribute}" id="id_product_attribute">
                        <input type="hidden" name="id_customization" value="{$product.id_customization}" id="id_customization">

                        {include file='catalog/_partials/product-variants.tpl'}

                        <div class="add-cart-fitting product-add-to-cart-fitting">
                            {include file='catalog/_partials/product-add-to-cart.tpl'}
                        </div>
                    </form>

                    {else}

                    <div class="alerts">
                        {widget name="alsernetforms" forms="fitting"}
                    </div>

                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>