<section class="mt-50 pb-50">
    <div class="container">
        <div class="row">
            <div class="col-xl-8 col-lg-10 m-auto">
                <div class="contact-from-area  padding-20-row-col wow tmFadeInUp animated" style="visibility: visible;">
                    <h3 class="mb-10 text-center">{{ __('Drop Us a Line') }}</h3>

                    <p class="text-muted mb-30 text-center font-sm">{{ __('Contact Us For Any Questions') }}</p>
                    <form action="{{ route('contact.send') }}" method="POST" class="contact-form-style text-center contact-form">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6 col-md-6">
                                <div class="input-style mb-20">
                                    <input name="name" value="{{ old('name') }}" placeholder="{{ __('Name') }}" type="text">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <div class="input-style mb-20">
                                    <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <div class="input-style mb-20">
                                    <input name="address" value="{{ old('address') }}" placeholder="{{ __('Address') }}" type="text">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <div class="input-style mb-20">
                                    <input name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone') }}" type="tel">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="input-style mb-20">
                                    <input name="subject" value="{{ old('subject') }}" placeholder="{{ __('Subject') }}" type="text">
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12">
                                <div class="textarea-style">
                                    <textarea name="content" placeholder="{{ __('Message') }}">{{ old('content') }}</textarea>
                                </div>

                                <button class="submit submit-auto-width mt-30" type="submit">{{ __('Send message') }}</button>
                            </div>
                        </div>
                        <div class="form-group text-left">
                            <div class="contact-message contact-success-message mt-30" style="display: none"></div>
                            <div class="contact-message contact-error-message mt-30" style="display: none"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).on('submit', '.contact-form', function (e) {
    e.preventDefault();

    var form = $(this);
    var successMsg = form.find('.contact-success-message');
    var errorMsg = form.find('.contact-error-message');

    successMsg.hide();
    errorMsg.hide();

    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (response) {
            form[0].reset();
            successMsg.text(response.message).show();
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var $list = $('<ul class="mb-0 ps-3">');
                $.each(errors, function (field, fieldErrors) {
                    $('<li>').text(fieldErrors[0]).appendTo($list);
                });
                errorMsg.empty().append($list).show();
            } else {
                errorMsg.text('{{ __("An error occurred. Please try again.") }}').show();
            }
        }
    });
});
</script>
