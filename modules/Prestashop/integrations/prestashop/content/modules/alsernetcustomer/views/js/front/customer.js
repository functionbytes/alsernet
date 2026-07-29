$(document).ready(function () {

    $(".btn-dashboard-show").click(function () {
        $(".bg-overlay, .dashboard-left-sidebar").addClass("show");
    });

    $(".close-button, .bg-overlay, .user-nav-pills .nav-item .nav-link").click(function () {
        $(".bg-overlay, .dashboard-left-sidebar").removeClass("show");
    });

});