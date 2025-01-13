/*-----------------------------------------------------------------------------------
    Template Name: Wellearn - Education & LMS HTML Template
    Template URI: https://webtend.net/demo/html/wellearn/
    Author: WebTend
    Author URI:  https://webtend.net/
    Version: 1.0

    Note: This is Main JS File.
-----------------------------------------------------------------------------------
	CSS INDEX
	===================
    01. Header
    02. Dropdown menu
    03. Submenu
    04. Menu Hidden Sidebar
    05. Search Box
    06. Scroll to Top
    07. Main Slider
    08. Coach Filtering
    09. Gallery Filtering
    10. Work Step Slider
    11. Events Slider
    12. Coach Slider
    13. Testimonial Slider
    14. Testimonial Two
    15. Testimonial Three
    16. Testimonial Four
    17. Video Popups
    18. Instagram
    19. Gallery 
    20. Widget Gallery
    21. Fact Counter
    22. Price range
    23. Nice Select
    24. WOW Animation
    25. Circle Progress
    26. Preloader
-----------------------------------------------------------------------------------*/

(function ($) {

    "use strict";

    $(document).ready(function () {

        // 01. Header Style and Scroll to Top
        function headerStyle() {
            if ($('.main-header').length) {
                var windowpos = $(window).scrollTop();
                var siteHeader = $('.main-header');
                var scrollLink = $('.scroll-top');
                if (windowpos >= 250) {
                    siteHeader.addClass('fixed-header');
                    scrollLink.fadeIn(300);
                } else {
                    siteHeader.removeClass('fixed-header');
                    scrollLink.fadeOut(300);
                }
            }
        }
        headerStyle();


        // 02. Dropdown menu
        var mobileWidth = 992;
        var navcollapse = $('.navigation li.dropdown');

        navcollapse.hover(function () {
            if ($(window).innerWidth() >= mobileWidth) {
                $(this).children('ul').stop(true, false, true).slideToggle(300);
                $(this).children('.megamenu').stop(true, false, true).slideToggle(300);
            }
        });

        // 03. Submenu Dropdown Toggle
        if ($('.main-header .navigation li.dropdown ul').length) {
            $('.main-header .navigation li.dropdown').append('<div class="dropdown-btn"><span class="fa-duotone fa-arrow-down"></span></div>');

            //Dropdown Button
            $('.main-header .navigation li.dropdown .dropdown-btn').on('click', function () {
                $(this).prev('ul').slideToggle(500);
                $(this).prev('.megamenu').slideToggle(800);
            });

            //Disable dropdown parent link
            $('.navigation li.dropdown > a').on('click', function (e) {
                e.preventDefault();
            });
        }

        //Submenu Dropdown Toggle
        if ($('.main-header .main-menu').length) {
            $('.main-header .main-menu .navbar-toggle').click(function () {
                $(this).prev().prev().next().next().children('li.dropdown').hide();
            });
        }


        // 04. Menu Hidden Sidebar Content Toggle
        if($('.menu-sidebar').length){
            //Show Form
            $('.menu-sidebar').on('click', function(e) {
                e.preventDefault();
                $('body').addClass('side-content-visible');
            });
            //Hide Form
            $('.hidden-bar .inner-box .cross-icon,.form-back-drop,.close-menu').on('click', function(e) {
                e.preventDefault();
                $('body').removeClass('side-content-visible');
            });
            //Dropdown Menu
            $('.fullscreen-menu .navigation li.dropdown > a').on('click', function() {
                $(this).next('ul').slideToggle(500);
            });
        }


        // 05. Search Box
        $('.nav-search > button').on('click', function () {
            $('.nav-search form').toggleClass('hide');
        });

        // Hide Box Search WHEN CLICK OUTSIDE
        if ($(window).width() > 767){
            $('body').on('click', function (event) {
                if ($('.nav-search > button').has(event.target).length == 0 && !$('.nav-search > button').is(event.target)
                    && $('.nav-search form').has(event.target).length == 0 && !$('.nav-search form').is(event.target)) {
                    if ($('.nav-search form').hasClass('hide') == false) {
                        $('.nav-search form').toggleClass('hide');
                    };
                }
            });
        }


        // 06. Scroll to Top
        if ($('.scroll-to-target').length) {
            $(".scroll-to-target").on('click', function () {
                var target = $(this).attr('data-target');
                // animate
                $('html, body').animate({
                    scrollTop: $(target).offset().top
                }, 1000);

            });
        }


        // 07. Main Slider 
        if ($('.main-slider').length) {
            var $status = $('.pagingStatus');
            var $count = $('.pagingCount');
            var $slickElement = $('.main-slider');

            $slickElement.on('init reInit afterChange', function (event, slick, currentSlide, nextSlide) {
                //currentSlide is undefined on init -- set it to 0 in this case (currentSlide is 0 based)
                var i = (currentSlide ? currentSlide : 0) + 1;
                $status.text('0' + slick.slideCount);
                $count.text('0' + i);
            });

            $slickElement.slick({
                autoplay: true,
                dots: false,
                speed: 400,
                fade: true,
                arrows: false,
                infinite: true,
                autoplaySpeed: 5000,
                pauseOnHover: false,
            });
        }


        // 08. Coach Filtering
        $(".coach-filter li").on('click', function () {
            $(".coach-filter li").removeClass("current");
            $(this).addClass("current");

            var selector = $(this).attr('data-filter');
            $('.coach-active').imagesLoaded(function () {
                $(".coach-active").isotope({
                    itemSelector: '.item',
                    filter: selector,
                });
            });

        });


        // 09. Gallery Filtering
        $(".gallery-filter li").on('click', function () {
            $(".gallery-filter li").removeClass("current");
            $(this).addClass("current");

            var selector = $(this).attr('data-filter');
            $('.gallery-active').imagesLoaded(function () {
                $(".gallery-active").isotope({
                    itemSelector: '.item',
                    filter: selector,
                });
            });

        });


        // 10. Work Step Slider 
        if ($('.work-step-wrap').length) {
            $('.work-step-wrap').slick({
                slidesToShow: 2,
                slidesToScroll: 1,
                infinite: true,
                speed: 400,
                autoplay: false,
                autoplaySpeed: 5000,
                prevArrow: $('.work-prev'),
                nextArrow: $('.work-next'),
                responsive: [
                    {
                        breakpoint: 767,
                        settings: {
                            slidesToShow: 1,
                        }
                    }
                ]
            });
        }


        // 11. Events Slider 
        if ($('.event-wrap').length) {
            $('.event-wrap').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                infinite: true,
                speed: 400,
                autoplay: true,
                arrows: false,
                dots: true,
                autoplaySpeed: 5000,
                responsive: [
                    {
                        breakpoint: 991,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 575,
                        settings: {
                            slidesToShow: 1,
                        }
                    }
                ]
            });
        }

        // 12. Coach Slider 
        if ($('.coach-slider').length) {
            $('.coach-slider').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                infinite: true,
                speed: 400,
                autoplay: true,
                arrows: false,
                dots: true,
                autoplaySpeed: 5000,
                responsive: [
                    {
                        breakpoint: 1199,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 767,
                        settings: {
                            slidesToShow: 1,
                        }
                    }
                ]
            });
        }


        /* 13. Testimonial Slider */
        if ($('.testimonial-wrap').length) {
            $('.testimonial-wrap').slick({
                infinite: true,
                arrows: false,
                dots: true,
                fade: true,
                autoplay: false,
                autoplaySpeed: 5000,
                pauseOnHover: false,
                slidesToScroll: 1,
                slidesToShow: 1,
            });
        }


        /* 14. Testimonial Two Slider */
        if ($('.testimonial-two-wrap').length) {
            $('.testimonial-two-wrap').slick({
                infinite: true,
                arrows: false,
                dots: true,
                fade: true,
                autoplay: false,
                autoplaySpeed: 5000,
                pauseOnHover: false,
                slidesToScroll: 1,
                slidesToShow: 1,
            });
        }


        /* 15. Testimonial Three Slider */
        if ($('.testimonial-three-wrap').length) {
            $('.testimonial-three-wrap').slick({
                infinite: true,
                arrows: false,
                dots: true,
                fade: true,
                autoplay: false,
                autoplaySpeed: 5000,
                pauseOnHover: false,
                slidesToScroll: 1,
                slidesToShow: 1,
            });
        }


        /* 16. Testimonial Four Slider */
        if ($('.testimonial-four-wrap').length) {
            $('.testimonial-four-wrap').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                arrows: false,
                dots: true,
                centerMode: true,
                infinite: true,
                focusOnSelect: true,
                cssEase: 'linear',
                touchMove: true,
                variableWidth: true,
                responsive: [
                    {
                        breakpoint: 1199,
                        settings: {
                            slidesToShow: 1,

                        }
                    },
                    {
                        breakpoint: 776,
                        settings: {
                            slidesToShow: 1,
                            variableWidth: false,
                        }
                    }
                ]
            });
        }


        // 17. Video Popups
        if ($('.video-play').length) {
            $('.video-play').magnificPopup({
                type: 'video',
            });
        }
        // Youtube Video Popup
        if ($('.youtube-video-play').length) {
            $('.youtube-video-play').magnificPopup({
                type: 'video',
            });
        }
        // Course Video Popup
        if ($('.course-video-play').length) {
            $('.course-video-play').magnificPopup({
                type: 'video',
            });
        }
        // Slider Video Popup
        if ($('.slider-video-play').length) {
            $('.slider-video-play').magnificPopup({
                type: 'video',
            });
        }


        // 18. Instagram
        $('.instagram-item a').magnificPopup({
            type:'image',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
            },
        });


        // 19. Gallery
        $('.gallery-image a').magnificPopup({
            type:'image',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
            },
        });


        // 20. Widget Gallery
        $('.gallery-widget-item a').magnificPopup({
            type:'image',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
            },
        });


        /* 21. Fact Counter + Text Count - Our Success */
        if ($('.success-item').length) {
            $('.success-item').appear(function () {

                var $t = $(this),
                    n = $t.find(".count-text").attr("data-stop"),
                    r = parseInt($t.find(".count-text").attr("data-speed"), 10);

                if (!$t.hasClass("counted")) {
                    $t.addClass("counted");
                    $({
                        countNum: $t.find(".count-text").text()
                    }).animate({
                        countNum: n
                    }, {
                        duration: r,
                        easing: "linear",
                        step: function () {
                            $t.find(".count-text").text(Math.floor(this.countNum));
                        },
                        complete: function () {
                            $t.find(".count-text").text(this.countNum);
                        }
                    });
                }

            }, {
                accY: 0
            });
        }


        // 22. Price range Fliter jQuery UI
        if ($('.price-slider-range').length) {
            $( ".price-slider-range" ).slider({
                range: "min",
                value: 596,
                min: 5,
                max: 800,
                slide: function( event, ui ) {
                    $( "#price" ).val( "$" + ui.value );
                }
            });
            $( "#price" ).val( "$" + $( ".price-slider-range" ).slider( "value" ) );
        }



        // 24. WOW Animation
        if ($('.wow').length) {
            var wow = new WOW({
                boxClass: 'wow', // animated element css class (default is wow)
                animateClass: 'animated', // animation css class (default is animated)
                offset: 0, // distance to the element when triggering the animation (default is 0)
                mobile: false, // trigger animations on mobile devices (default is true)
                live: true // act on asynchronously loaded content (default is true)
            });
            wow.init();
        }


        /* 25. Circle Progress */
        if ($.fn.circleProgress) {
            var progress = $('.counter-number')
            if($.fn.circleProgress) {
                progress.appear(function () {
                    progress.circleProgress({
                        value: 0.85,
                        size: 85,
                        thickness: 3,
                        fill: "white",
                        lineCap: 'square',
                        startAngle: -Math.PI / 4 * 2,
                        animation : { duration: 2000},
                    }).on('circle-animation-progress', function(event, progress) {
                        $(this).find('span').html(Math.round(85 * progress) + '<span>%</span>');
                    });
                });
            };
        };


    });


    /* ==========================================================================
       When document is resize, do
       ========================================================================== */

    $(window).on('resize', function () {
        var mobileWidth = 992;
        var navcollapse = $('.navigation li.dropdown');
        navcollapse.children('ul').hide();
        navcollapse.children('.megamenu').hide();

    });


    /* ==========================================================================
       When document is scroll, do
       ========================================================================== */

    $(window).on('scroll', function () {

        // Header Style and Scroll to Top
        function headerStyle() {
            if ($('.main-header').length) {
                var windowpos = $(window).scrollTop();
                var siteHeader = $('.main-header');
                var scrollLink = $('.scroll-top');
                if (windowpos >= 150) {
                    siteHeader.addClass('fixed-header');
                    scrollLink.fadeIn(300);
                } else {
                    siteHeader.removeClass('fixed-header');
                    scrollLink.fadeOut(300);
                }
            }
        }

        headerStyle();

    });

    /* ==========================================================================
       When document is loaded, do
       ========================================================================== */

    $(window).on('load', function () {

        if ($('.coach-slider').length) {
            $('.coach-slider').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                infinite: true,
                speed: 400,
                autoplay: true,
                arrows: false,
                dots: true,
                autoplaySpeed: 5000,
                responsive: [
                    {
                        breakpoint: 1199,
                        settings: {
                            slidesToShow: 2,
                        }
                    },
                    {
                        breakpoint: 767,
                        settings: {
                            slidesToShow: 1,
                        }
                    }
                ]
            });
        }

        // 08. Coach Filtering
        if ($('.coach-active').length) {
            $(".coach-active").isotope({
                itemSelector: '.item',
            });
        };

        // 09. Gallery Filtering
        if ($('.gallery-active').length) {
            $(".gallery-active").isotope({
                itemSelector: '.item',
            });
        };

        // 26. Preloader
        function handlePreloader() {
            if ($('.preloader').length) {
                $('.preloader').delay(200).fadeOut(500);
            }
        }
        handlePreloader();

    });

})(window.jQuery);