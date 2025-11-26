$(document).ready(function () {
  $(".content-products .swiper-container").each(function () {
    var swiper = new Swiper($(this)[0], {
      loop: true,
      lazy: true,
      autoplay: {
        delay: 4000,
      },
      slidesPerView: 3, // Número de elementos por defecto
      spaceBetween: 10, // Espacio entre los elementos por defecto
      navigation: {
        nextEl: $(this).find('.swiper-button-next')[0],
        prevEl: $(this).find('.swiper-button-prev')[0],
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
          spaceBetween: 10,
        },
        480: {
          slidesPerView: 1,
          spaceBetween: 10,
        },
        640: {
          slidesPerView: 2,
          spaceBetween: 10,
        },
        767: {
          slidesPerView: 2,
          spaceBetween: 10,
        },
        1024: {
          slidesPerView: 3,
          spaceBetween: 10,
        },
        1200: {
          slidesPerView: 3,
          spaceBetween: 10,
        },
      },
    });
  });
});