;(function($){

$(document).ready(function(){

//========== PAGE PROGRESS STARTS ============= //
try {
  var progressPath = document.querySelector(".progress-wrap path");
  if (progressPath && typeof progressPath.getTotalLength === 'function') {
    var pathLength = progressPath.getTotalLength();
    progressPath.style.transition = progressPath.style.WebkitTransition = "none";
    progressPath.style.strokeDasharray = pathLength + " " + pathLength;
    progressPath.style.strokeDashoffset = pathLength;
    progressPath.getBoundingClientRect();
    progressPath.style.transition = progressPath.style.WebkitTransition = "stroke-dashoffset 10ms linear";
    var updateProgress = function () {
      var scroll = $(window).scrollTop();
      var height = $(document).height() - $(window).height();
      var progress = pathLength - (scroll * pathLength) / height;
      progressPath.style.strokeDashoffset = progress;
    };
    updateProgress();
    $(window).scroll(updateProgress);
    var offset = 50;
    var duration = 550;
    jQuery(window).on("scroll", function () {
      if (jQuery(this).scrollTop() > offset) {
        jQuery(".progress-wrap").addClass("active-progress");
      } else {
        jQuery(".progress-wrap").removeClass("active-progress");
      }
    });
    jQuery(".progress-wrap").on("click", function (event) {
      event.preventDefault();
      jQuery("html, body").animate({ scrollTop: 0 }, duration);
      return false;
    });
  }
} catch (error) {
  console.error('Progress bar error:', error);
}
//========== PAGE PROGRESS ENDS ============= //

//========== AOS ============= //
if (typeof AOS !== 'undefined') {
  AOS.init({disable: 'mobile'});
}

//========== SELECT2 ============= //
$('select').select2({ minimumResultsForSearch: 6 });

});

//========== COUNTER UP ============= //
const ucounter = $('.counter');
if (ucounter.length > 0) {
  try {
    ucounter.counterUp({ time: 1000, delay: 16 });
  } catch (error) {
    console.error('Error while executing counterUp:', error);
  }
}

//========== HERO SLIDER ============= //
$(document).ready(function() {
  var carouselEl = $('.slider-header-carousel');
  if (carouselEl.length === 0) return;

  carouselEl.owlCarousel({
    loop: true,
    margin: 0,
    nav: true,
    dots: true,
    mouseDrag: false,
    items: 1,
    autoplay: false,
    vertical: false,
    navText: ["<i class='fa-solid fa-angle-left'></i>", "<i class='fa-solid fa-angle-right'></i>"],
    active: true,
    smartSpeed: 1000,
    responsiveClass: true,
    responsive: {
      0: { items: 1, nav: true },
      600: { items: 1 },
      1000: { items: 1 }
    }
  });

  var owl = carouselEl.data('owl.carousel');
  var autoplayInterval;
  var totalSlides = carouselEl.find('.hero1-section-area').length / 2;

  function goToSlide(slideIndex) {
    var realSlide = (slideIndex % totalSlides) + 1;
    owl.to(realSlide);
  }

  function nextSlide() {
    var current = owl.current();
    owl.next();
  }

  function startAutoplay() {
    autoplayInterval = setInterval(nextSlide, 4000);
  }

  function stopAutoplay() {
    clearInterval(autoplayInterval);
  }

  startAutoplay();
  carouselEl.on('mouseenter', stopAutoplay).on('mouseleave', startAutoplay);
  carouselEl.find('.owl-nav button').on('click', function() {
    stopAutoplay();
    setTimeout(startAutoplay, 100);
  });

  // Accessibility: add aria-labels to nav buttons
  carouselEl.find('.owl-nav button').each(function() {
    var $btn = $(this);
    if ($btn.hasClass('owl-prev')) {
      $btn.attr('aria-label', 'Diapositiva anterior');
    } else if ($btn.hasClass('owl-next')) {
      $btn.attr('aria-label', 'Diapositiva siguiente');
    }
    $btn.removeAttr('role');
  });

  // Accessibility: add aria-labels to dot buttons
  carouselEl.find('.owl-dots button').each(function(index) {
    $(this).attr('aria-label', 'Ir a diapositiva ' + (index + 1));
  });
});

//========== PRELOADER ============= //
$(window).on("load", function (event) {
  setTimeout(function () {
    $(".preloader").fadeToggle();
  }, 400);
});

})(jQuery);
