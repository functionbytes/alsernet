<section class="newsletter bg-brand p-30 text-white wow fadeIn animated">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-10 mb-md-10 mb-lg-0">
                <div class="row align-items-center">
                    <div class="col flex-horizontal-center">
                        <img class="icon-email" src="{{ Theme::asset()->url('images/icons/icon-email.svg') }}" alt="icon">
                        <div class="row align-items-center">
                            <h4 class="font-size-20 mb-0 ml-3">{!! BaseHelper::clean($title) !!}</h4>
                            <h5 class="font-description">{!! BaseHelper::clean($description) !!}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-button">
                    <a class="btn" href="/suscripciones">Suscribirme</a>
                </div>
            </div>
        </div>
    </div>
</section>
