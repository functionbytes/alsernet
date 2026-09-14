$(document).ready(function() {
    // Función para manejar la consulta AJAX
    function ajaxEventHandler(callback) {

        var pathArray = window.location.pathname.split('/');
        var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
        var iso = language!= '' ? language : 'es';

        var link = "/modules/alserneteventmanager/controllers/routes.php?&action=get&iso="+iso;

        $.ajax({
            cache: true,
            url: link,
            data: {
            }
        }).done(function (response) {
            if (callback) {
                callback(response);
            }
        }).fail(function () {
            console.log("Error en la carga de datos.");
        });

    }

    function validateCmsEvent() {

    }


    function validateNavsEvent() {

        if ($('body').hasClass('page-category')) {

            ajaxEventHandler(function(events) {

                events = events.data || [];

                var bodyClass = $("body").attr("class") || "";

                var langMatch = bodyClass.match(/lang-([a-z]{2})/);
                var beforelang = langMatch ? langMatch[1].toLowerCase() : null;

                var categoryMatch = bodyClass.match(/category-id-(\d+)/);
                var beforeCategory = categoryMatch ? parseInt(categoryMatch[1]) : null;

                if (!beforelang || !beforeCategory) {
                    return;
                }

                var validEvents = events.filter(function(event) {

                    var langMatches = event.languages.some(function(language) {
                        return language.iso_code.toLowerCase() === beforelang;
                    });

                    var categoryMatches = event.category.some(function(cat) {
                        return parseInt(cat.id_category) === beforeCategory;
                    });

                    return langMatches && categoryMatches;
                });


                if (validEvents.length > 0) {

                    validEvents.forEach(function(event) {

                            const filteredLanguage = event.languages.find(lang => lang.iso_code.toLowerCase() === beforelang);


                            var eventButtonSpecial = `
                                    <div class="nav-item">
                                       <a class="public" style="${event.color_buttom}" href="${filteredLanguage.url_special}" title="${filteredLanguage.title_special}">
                                            ${filteredLanguage.title_special}
                                       </a>
                                    </div>
                            `;

                            $('.navs .menu-content').append(eventButtonSpecial);


                    });

                }

            });
        }


    }

    // Función para generar el descuento de eventos
    function genrateEventDiscout() {

        if (!$('body').hasClass('event')) {
            $(".f20").addClass("d-none");

            return;
        }

        ajaxEventHandler(function(events) {

            events = events.data || [];

            var bodyClass = $("body").attr("class") || "";

            var langMatch = bodyClass.match(/lang-([a-z]{2})/);
            var beforelang = langMatch ? langMatch[1].toLowerCase() : null;

            var categoryMatch = bodyClass.match(/category-depth-level-(\d+)/);
            var beforeCategory = categoryMatch ? parseInt(categoryMatch[1]) : null;

            if (!beforelang || !beforeCategory) {
                return;
            }

            var validEvents = events.filter(function(event) {
                var langMatches = event.languages.some(function(language) {
                    return language.iso_code.toLowerCase() === beforelang;
                });

                var categoryMatches = event.category.some(function(cat) {
                    return parseInt(cat.id_category) === beforeCategory;
                });

                return langMatches && categoryMatches;
            });


            if (validEvents.length > 0) {

                $(".f20").removeClass("d-none");
                $(".f20 li").addClass("d-none");

                validEvents.forEach(function(event) {

                    const filteredLanguage = event.languages.find(lang => lang.iso_code.toLowerCase() === beforelang);
                    const $price = $('input[data-url="' + filteredLanguage.filter + '"]');
                    //console.log(filteredLanguage);
                    //console.log($price);
                    if ($price.length > 0) {

                        if (filteredLanguage) {


                            const $button = $(".price-btn");
                            // Generate the HTML for the button with the event id
                            var eventButtonHTML = `
                                <span class="btn btn-action" data-event-id="${event.id_event}">
                                    <p class="one"><i class="fa-solid fa-circle-right"></i>${filteredLanguage.buttom_one}</p>
                                    <p class="all"><i class="fa-solid fa-circle-left"></i>${filteredLanguage.buttom_all}</p>
                                </span>
                            `;

                            $button.html(eventButtonHTML); // Replaces any existing content inside .price-btn

                            const $action = $(".btn-action");
                            const isChecked = $price.prop("checked");

                            if (isChecked) {
                                $action.addClass('active').attr("style", event.hover_buttom);
                            } else {
                                $action.removeClass('active').attr("style", event.color_buttom);
                            }

                            // Add the generated HTML inside the .price-btn


                            $button.off("click").on("click", function () {
                                $("#amazzing_filter").removeClass("animation-ready");
                                $("body").removeClass("show-filter");
                                $price.click();
                                $(".viewFilteredProducts").click();
                            });

                            $button.addClass('view');

                            const amazing = event.amazing;
                            $(".f20 li.item-f-" + amazing).removeClass("d-none");

                        }
                    }

                });

            } else {
                $(".f20").addClass("d-none");
            }


        });
    }

    window.genrateEventDiscout = genrateEventDiscout;
    // validateNavsEvent();
});
