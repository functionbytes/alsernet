$(document).ready(function () {


    window.scrollToElement = function (target, triggerClick = null, offset = 80) {
        const $el = $(target);
        if ($el.length) {
            const top = $el.offset().top - offset;
            $('html, body').animate({ scrollTop: top }, 500);

            if (triggerClick) {
                $(triggerClick).click();
            }
        }
    };
    function scrollTo(target, triggerClick = null) {
        $('html, body').animate({
            scrollTop: $(target).offset().top
        }, 500);

        if (triggerClick) {
            $(triggerClick).click();
        }
    }


    $(document).on('click', '.btn-contact', function() {
        scrollTo('#alvarezquestionblock', '#mostrarformquestion');
    });

    $(document).on('click', '.view-reviews-auth', function() {
        scrollTo('#extra-0');
    });

    $(document).on('click', '#btn_iva_info', function() {
        $('#modal-product-iva').modal('show');
    });

    $(document).on('click', '#btn_iva_info_close', function(event) {
        $('#modal-product-iva').modal('hide');
    });

    $(document).on('click', '.view-reviews-notauth', function() {
        $('#review-modal').modal('show');
    });

    $(document).on('click', '.review-action', function() {
        $('#review-modal').modal('show');
    });

    $(document).on('click', '#hourModal', function(event) {
        $('#modal-hour').modal('show');
    });

    $(document).on('click', '#hourClose', function(event) {
        $('#modal-hour').modal('hide');
    });

    $(document).on('click', '#review-action', function() {
        $('#review-modal').modal('show');
    });

    $(document).on('click', '.product-add-to-cart button', function(event) {
        $(this).prop("disabled", true);

    });

    $(document).on('click', '.product-details .custom-radio span, .product-details .custom-radio label', function() {
        const $input = $(this).siblings('input[type="radio"]');
        $input.prop('checked', true).trigger('change');
    });

    $(window).on('click', function(e) {
        if ($(e.target).is($('#modal-film'))) {
            $('#modal-film').modal('hide');
        }
    });

    $('.product-video-viewer').on('click', function(e) {
        e.preventDefault();

        var videoUrl = $('#url').val();

        if (videoUrl) {
            $('#product-film').attr('src', videoUrl);
            $('#modal-film').modal('show');
        } else {
            console.log("La URL del video está vacía.");
        }
    });

    $('.close').on('click', function() {
        $('#modal-film').modal('hide');
        $('#product-film').attr('src', '');
    });

    $(window).on('click', function(e) {
        if ($(e.target).is($('#modal-film'))) {
            $('#modal-film').modal('hide');
            $('#product-film').attr('src', '');
        }
    });

    $('#modal-film').on('hidden.bs.modal', function () {
        $('#product-film').attr('src', '');
    });
    function initializeSwipers() {
        $('.images-container').imagesLoaded(function() {
            // Destruir instancias anteriores si existen
            if (typeof thumbsSwiper !== 'undefined') thumbsSwiper.destroy(true, true);
            if (typeof mainSwiper   !== 'undefined') mainSwiper.destroy(true, true);

            // Inicializar thumbs swiper con sus propios botones
            thumbsSwiper = new Swiper('.product-thumbs-wrap', {
                spaceBetween: 10,
                slidesPerView: 3,
                freeMode: true,
                watchSlidesProgress: true,
                navigation: {
                    nextEl: '.product-thumbs-wrap .swiper-button-next',
                    prevEl: '.product-thumbs-wrap .swiper-button-prev',
                },
            });

            // Inicializar main swiper con sus propios botones
            mainSwiper = new Swiper('.product-default-swiper', {
                spaceBetween: 10,
                slidesPerView: 1,
                loop: false,
                navigation: {
                    nextEl: '.product-default-swiper .swiper-button-next',
                    prevEl: '.product-default-swiper .swiper-button-prev',
                },
                thumbs: {
                    swiper: thumbsSwiper,
                },
                pagination: {
                  el: '.product-default-swiper .swiper-pagination',
                  clickable: true, // permite hacer clic en los puntitos
                },
            });

            // Actualizar navegación tras inicialización (opcional)
            mainSwiper.on('init', function() {
                mainSwiper.navigation.update();
            });
            // Si fuese necesario, descomenta para inicializar manualmente:
            // mainSwiper.init();

            // Preparar items para PhotoSwipe
            var $pswp = $('.pswp');
            var items = [];
            $('.product-default-swiper .swiper-slide .product-image a').each(function() {
                var $link = $(this);
                var size = $link.data('size').split('x');
                items.push({
                    src:   $link.attr('data-href'),
                    w:     parseInt(size[0], 10),
                    h:     parseInt(size[1], 10),
                    title: $link.find('img').attr('alt') || ''
                });
            });

            // Función para abrir galería en el índice dado
            function openGallery(index) {
                var pswpElement = document.querySelectorAll('.pswp')[0];
                var options = {
                    index: index,
                    bgOpacity: 0.8,
                    showHideOpacity: true,
                    history: false,
                    getThumbBoundsFn: function(idx) {
                        var thumb = $('.product-thumbs-wrap .swiper-slide').eq(idx).find('img');
                        var pageY = thumb.offset().top + window.scrollY;
                        var pageX = thumb.offset().left;
                        return { x: pageX, y: pageY, w: thumb.width() };
                    }
                };
                var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
                gallery.init();
                $pswp.data('gallery', gallery).show();

                // Cerrar al hacer clic en fondo o botón close
                $pswp.on('click', function(e) {
                    if ($(e.target).hasClass('pswp__bg') || $(e.target).hasClass('pswp__button--close')) {
                        gallery.close();
                    }
                });

                // Cerrar con Escape
                $(document).off('keyup.photoswipe').on('keyup.photoswipe', function(e) {
                    if (e.key === 'Escape') gallery.close();
                });

                // Al cerrar, ocultar y limpiar datos
                gallery.listen('close', function() {
                    $pswp.hide().removeData('gallery');
                });
            }

            // Abrir galería al hacer clic en la imagen grande
            $('.product-default-swiper .product-image-full').off('click').on('click', function(e) {
                e.preventDefault();
                var idx = $(this).closest('.swiper-slide').index();
                if (idx < 0) idx = mainSwiper.activeIndex;
                openGallery(idx);
            });
        });
    }

    initializeSwipers();

    var $submitButtonCart = $('.page-product-default .product-actions').find('.add-cart-product');
    var originalText = $submitButtonCart.html();
    var isProcessing = false; // Bandera para evitar solicitudes duplicadas

// Al iniciar la solicitud AJAX
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.url.includes('controller=product') && $('.page-product-default').length > 0 ) {
            var $submitButtonCartv1 = $('.page-product-default .product-actions').find('.add-cart-product');
            // Establece la bandera para indicar que la solicitud está en curso
            isProcessing = true;

            // Deshabilita el botón y cambia el texto mientras se procesa la solicitud
            $submitButtonCartv1.prop('disabled', true);
            // console.log(originalText);
            $submitButtonCartv1.html('Procesando...');
            console.log("Solicitud AJAX en curso...");
        }
    });

// Al completar la solicitud AJAX
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url.includes('controller=product') && $('.page-product-default').length > 0 ) {
            var $submitButtonCartv2 = $('.page-product-default .product-actions').find('.add-cart-product');
            initializeSwipers();

            // Vuelve a habilitar el botón y restaura el texto original
            $submitButtonCartv2.prop('disabled', false);
            // console.log(originalText);
            $submitButtonCartv2.html(originalText);
            console.log("Solicitud AJAX completada...");

            // Restablece la bandera para permitir futuras solicitudes
            isProcessing = false;
        }
    });


// Asegurarse de que PhotoSwipe se cierre cuando se haga clic fuera de la imagen
    $(document).off('click', '.pswp__bg').on('click', '.pswp__bg', function() {
        var gallery = $('.pswp').data('gallery');
        if (gallery) {
            gallery.close();
        }
    });


    $(document).ready(function() {
        // Mostrar el modal automáticamente si quieres forzar su lectura
        $('.page-product-ping .fittingModal').modal({
            backdrop: 'static', // evita cerrar haciendo clic fuera
            keyboard: false     // evita cerrar con tecla ESC
        });

        $('.page-product-ping .closeFittingModal').on('click', function() {
            if ($('#confirmFitting').is(':checked') || $('#noFitting').is(':checked')) {
                // Permitir cerrar solo si confirma fitting
                $('#fittingModal').modal('hide');
            } else {
                // Mostrar alerta si no ha confirmado fitting
                $('#alertFitting').removeClass('d-none').text('⚠️ Debes confirmar que te has sometido a un ajuste personalizado para continuar.');
            }
        });
    });

    $(document).ready(function() {
        // Check if the .page-product-ping element exists
        if ($('.page-product-custom').length) {

            function generatePriceHTML() {
                // Get values from resume elements
                var resumeTotal = $('#resume_total_price').text().trim();
                var basePrice = $('.idxrcp_resume_opt_price_wodiscount').text().trim();
                var discountValue = $('#idxcp_discount_value').text().trim();

                // Generate the HTML structure
                var priceHTML = `
                <div class="product-price has-discount">
                    <div class="current-price">
                        <span class="current-price-value" itemprop="price" content="319">
                            ${resumeTotal}
                        </span>
                        <div class="product-discount">
                            <span class="regular-price">${basePrice}</span>
                        </div>
                        <span class="discount discount-amount">
                            ${discountValue}  €
                        </span>
                    </div>
                </div>
            `;

                // Insert the HTML after .product-bm-wrapper
                $('.product-bm-wrapper').after(priceHTML);
            }

            // Generate and insert HTML on page load
            generatePriceHTML();

            // Monitor changes in the resume elements
            const observer = new MutationObserver(function(mutations) {
                // Remove old price HTML if exists
                $('.product-bm-wrapper').next('.product-price').remove();
                // Generate new HTML with updated values
                generatePriceHTML();
            });

            // Observe the resume elements
            const config = {
                subtree: true,
                characterData: true,
                childList: true
            };

            // Observe resume_total_price
            var resumeElement = document.getElementById('resume_total_price');
            if (resumeElement) {
                observer.observe(resumeElement, config);
            }

            // Observe idxrcp_resume_opt_price_wodiscount
            var basePriceElement = document.querySelector('.idxrcp_resume_opt_price_wodiscount');
            if (basePriceElement) {
                observer.observe(basePriceElement, config);
            }

            // Observe idxcp_discount_value
            var discountElement = document.getElementById('idxcp_discount_value');
            if (discountElement) {
                observer.observe(discountElement, config);
            }
        }
    });

});


