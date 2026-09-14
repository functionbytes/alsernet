
{extends file=$layout}

<div class="page-content" id="wrapper">

    <div class="container">

        {hook h="displayWrapperTop"}

        {block name='content'}

            <div class="cart-container d-none">

                <div class="checkout-container d-none row checkout-flex flex-wrap ">
                    <div class="col-12 col-lg-8 left-sidebar-checkout">
                        <div class="container-products"></div>
                    </div>
                    <div class="col-12 col-lg-4 right-sidebar-checkout">
                        <div class="container-shipping"></div>
                        <div class="container-summary"></div>
                    </div>
                </div>


                {include file='module:alsernetshopping/views/templates/front/cart/partials/empty.tpl'}
                {include file='module:alsernetshopping/views/templates/front/cart/modal/delete.tpl'}
                {include file='module:alsernetshopping/views/templates/front/cart/modal/error.tpl'}

            </div>

            <div class="cart-container-process">
                <div class="row justify-content-center align-items-center" style="min-height: 300px;">
                    <div class="col-12 d-flex justify-content-center align-items-center">
                        <div class="preloader-new text-center">
                            <svg class="cart_preloader" role="img" aria-label="Shopping cart_preloader line animation"
                                 viewBox="0 0 128 128" width="128px" height="128px" xmlns="http://www.w3.org/2000/svg">
                                <g fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="8">
                                    <g class="cart__track" stroke="#4C4C4C">
                                        <polyline points="4,4 21,4 26,22 124,22 112,64 35,64 39,80 106,80"></polyline>
                                        <circle cx="43" cy="111" r="13"></circle>
                                        <circle cx="102" cy="111" r="13"></circle>
                                    </g>
                                    <g class="cart__lines" stroke="currentColor">
                                        <polyline class="cart__top" points="4,4 21,4 26,22 124,22 112,64 35,64 39,80 106,80"
                                                  stroke-dasharray="338 338" stroke-dashoffset="-338"></polyline>
                                        <g class="cart__wheel1" transform="rotate(-90,43,111)">
                                            <circle class="cart__wheel-stroke" cx="43" cy="111" r="13"
                                                    stroke-dasharray="81.68 81.68" stroke-dashoffset="81.68"></circle>
                                        </g>
                                        <g class="cart__wheel2" transform="rotate(90,102,111)">
                                            <circle class="cart__wheel-stroke" cx="102" cy="111" r="13"
                                                    stroke-dasharray="81.68 81.68" stroke-dashoffset="81.68"></circle>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {widget name='retailrocket' hook='displayRetailRocketCartPageBundleRelated'}

        {/block}

    </div>

</div>
