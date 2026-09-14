function init_comments(number_to_display)
{
    var counter = 0;
    $('.productComment').each(function(){
        if (counter < number_to_display) {
            $(this).show();
        } else {
            $(this).hide();
        }
        counter++;
    });
    if (lgcomments_displayed <= lgcomments_products_default_display) {
        $('#displayLess').hide();
    } else {
        $('#displayLess').show();
    }
    if (lgcomments_displayed >= $('.productComment').length) {
        $('#displayMore').hide();
    } else {
        $('#displayMore').show();
    }
}

function goToCommentsTab() {
    /*var tab = '';
    if (comment_tab == 3) {
        var tab_href = '#'+$('#lgcomment').closest('.tab-pane').attr('id');
        tab = $('a.nav-link[href="'+tab_href+'"]');
        $(tab).click();
    } else {
        tab = $('#lgcomment');
    }

    if (tab != '') {
        $('html,body').animate({
            scrollTop: $(tab).offset().top - 100
        }, 1000);
    }*/
    $('html,body').animate({
        scrollTop: $('#product_details_reviews').offset().top - 100
    }, 1000);
}

function setTabEvent() {
    $('*[id^="idTab"]').addClass('block_hidden_only_for_screen');
    $('div#idTab798').removeClass('block_hidden_only_for_screen');
    $('ul#more_info_tabs a[href^="#idTab"]').removeClass('selected');
    $('a[href="#idTab798"]').addClass('selected');
    $('div.page-product-box').removeClass('active');
    $('div#idTab798').addClass('active');
    $('li.active').removeClass('active');
    $(this).addClass('active');
}

$(document).ready(function () {
    lgcomments_displayed = lgcomments_products_default_display;
    init_comments(lgcomments_displayed);

    if (comment_tab === 2) {
        $(document).on('click', 'a[href=#idTab798]', function () {
            setTabEvent();
        });
    }

    if (comment_tab > 1) {
        if ($("#idTab798")) {
            setTabEvent();
        }
    }

    $(document).on('click', '.comment_anchor', function (e) {
        goToCommentsTab();
        e.preventDefault();
    });

    $(document).on('click', '#displayMore', function () {
        totalComments        = $('.productComment').length;
        lgcomments_displayed = (parseInt(parseInt(lgcomments_displayed) + parseInt(lgcomments_products_extra_display)) < parseInt(totalComments)) ? parseInt(parseInt(lgcomments_displayed)+parseInt(lgcomments_products_extra_display)) : parseInt(totalComments);
        init_comments(lgcomments_displayed)
    });

    $(document).on('click', '#displayLess', function () {
        minComments          = lgcomments_products_default_display;
        lgcomments_displayed = (parseInt(parseInt(lgcomments_displayed) - parseInt(lgcomments_products_extra_display)) > parseInt(minComments)) ? parseInt(parseInt(lgcomments_displayed) - parseInt(lgcomments_products_extra_display)) : parseInt(lgcomments_products_default_display);
        init_comments(lgcomments_displayed)
    });

    $(document).on('click', '.commentfilter', function() {
        var clicked = $(this).data('filter');
        $('.productComment').each(function(){
            if ($(this).data('filter') != clicked) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
        $('#displayMore').hide();
        $('#displayLess').hide();
    });

    $(document).on('click', '.commentfilterreset', function() {
        lgcomments_displayed = lgcomments_products_default_display;
        init_comments(lgcomments_displayed);
        $('#displayMore').show();
        $('#displayLess').show();
    });
});
