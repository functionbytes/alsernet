;(function($){

$(document).ready(function(){

//========== PAGE PROGRESS STARTS ============= //
var progressPath = document.querySelector(".progress-wrap path");
if (progressPath) {
  var pathLength = progressPath.getTotalLength();
  progressPath.style.transition = progressPath.style.WebkitTransition = "none";
  progressPath.style.strokeDasharray = pathLength + " " + pathLength;
  progressPath.style.strokeDashoffset = pathLength;
  // Defer layout read to next frame to avoid forced synchronous layout
  requestAnimationFrame(function() {
    progressPath.style.transition = progressPath.style.WebkitTransition = "stroke-dashoffset 10ms linear";
  });
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
//========== PAGE PROGRESS ENDS ============= //

//========== AOS ============= //
if (typeof AOS !== 'undefined') {
  AOS.init({disable: 'mobile'});
}

//========== SELECT2 ============= //
if ($('select').length) {
  $('select').select2({ minimumResultsForSearch: 6 });
}

//========== LAZY MAPS ============= //
document.querySelectorAll('.ctf-map-placeholder').forEach(function(el) {
  el.addEventListener('click', function() {
    var iframe = document.createElement('iframe');
    iframe.src = el.dataset.src;
    iframe.title = el.dataset.title || 'Mapa';
    iframe.setAttribute('allowfullscreen', '');
    iframe.setAttribute('loading', 'lazy');
    iframe.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = '0';
    el.parentNode.replaceChild(iframe, el);
  });
});

});

//========== PRELOADER ============= //
$(window).on("load", function (event) {
  setTimeout(function () {
    $(".preloader").fadeToggle();
  }, 400);
});

})(jQuery);
