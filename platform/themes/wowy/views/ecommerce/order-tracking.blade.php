<section class="pt-100 pb-100">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 m-auto">
                <div class="login_wrap widget-taber-content p-30 background-white border-radius-10">
                    <div class="padding_eight_all bg-white">
                        <div class="heading_s1 mb-20 text-center">
                            <h3 class="mb-20">{{ __('Order tracking') }}</h3>
                            <p>{{ __('Tracking your order status') }}</p>
                        </div>

                        <div class="mb-30">
                            {!!
                                $form
                                    ->modify('submit', 'button', [
                                        'label' => __('Find'),
                                        'attr' => [
                                            'type' => 'submit',
                                            'class' => 'w-100 btn btn-primary',
                                        ],
                                    ], true)
                                    ->renderForm()
                            !!}
                        </div>

                        <div style="margin-top: 60px;">
                            @include(EcommerceHelper::viewPath('includes.order-tracking-detail'))
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
