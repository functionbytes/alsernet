$(document).ready(function() {


    var pathArray = window.location.pathname.split('/');
    var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : 'es';

    var userAgent = navigator.userAgent;
    var version = "";

    // Obtener la resolución de la pantalla
    var screenWidth = $(window).width();
    var screenHeight = $(window).height();

    var imageElement = $('.trail-img'); // Selecciona el elemento de la imagen por su ID
    // Definir rango de resolución para dispositivos móviles (incluyendo tabletas)
    var isMobileResolution = (screenWidth < 900 && screenHeight < 1280);
    var isIpad = /iPad/.test(userAgent) || (isMobileResolution && /Macintosh/.test(userAgent) && 'ontouchend' in document);
    var isIphone = /iPhone/.test(userAgent);
    var isAndroid = /Android/.test(userAgent);
    var isWeb = !isIphone && !isAndroid && !isIpad; // Asumimos que es un navegador web si no es iPhone, iPad ni Android

    if (isIphone || isAndroid || isIpad) {
        imageElement.attr('src', '/themes/alvarez/assets/img/theme/cms/101/'+language+'/banner-mobile.webp');
    } else if (isWeb) {
        imageElement.attr('src', '/themes/alvarez/assets/img/theme/cms/101/'+language+'/banner-web.webp');
    }
});