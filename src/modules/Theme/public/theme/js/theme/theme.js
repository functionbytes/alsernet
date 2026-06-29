document.addEventListener("DOMContentLoaded", function () {
    "use strict";
    // =================================
    // Tooltip
    // =================================
    const tooltipTriggerList = Array.from(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.forEach((tooltipTriggerEl) => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // =================================
    // Popover
    // =================================document.addEventListener("DOMContentLoaded", function () {
    //   "use strict";
    //   // =================================
    //   // Tooltip
    //   // =================================
    //   const tooltipTriggerList = Array.from(
    //     document.querySelectorAll('[data-bs-toggle="tooltip"]')
    //   );
    //   tooltipTriggerList.forEach((tooltipTriggerEl) => {
    //     new bootstrap.Tooltip(tooltipTriggerEl);
    //   });
    //
    //   // =================================
    //   // Popover
    //   // =================================
    //   var popoverTriggerList = [].slice.call(
    //     document.querySelectorAll('[data-bs-toggle="popover"]')
    //   );
    //   var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    //     return new bootstrap.Popover(popoverTriggerEl);
    //   });
    //   // =================================
    //   // Hide preloader
    //   // =================================
    //   var preloader = document.querySelector(".preloader");
    //   if (preloader) {
    //     preloader.style.display = "none";
    //   }
    //   // =================================
    //   // Increment & Decrement
    //   // =================================
    //   var quantityButtons = document.querySelectorAll(".minus, .add");
    //   if (quantityButtons) {
    //     quantityButtons.forEach(function (button) {
    //       button.addEventListener("click", function () {
    //         var qtyInput = this.closest("div").querySelector(".qty");
    //         var currentVal = parseInt(qtyInput.value);
    //         var isAdd = this.classList.contains("add");
    //
    //         if (!isNaN(currentVal)) {
    //           qtyInput.value = isAdd
    //             ? ++currentVal
    //             : currentVal > 0
    //               ? --currentVal
    //               : currentVal;
    //         }
    //       });
    //     });
    //   }
    // });
    var popoverTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="popover"]')
    );
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    // =================================
    // Hide preloader
    // =================================
    var preloader = document.querySelector(".preloader");
    if (preloader) {
        preloader.style.display = "none";
    }
    // =================================
    // Increment & Decrement
    // =================================
    var quantityButtons = document.querySelectorAll(".minus, .add");
    if (quantityButtons) {
        quantityButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                var qtyInput = this.closest("div").querySelector(".qty");
                var currentVal = parseInt(qtyInput.value);
                var isAdd = this.classList.contains("add");

                if (!isNaN(currentVal)) {
                    qtyInput.value = isAdd
                        ? ++currentVal
                        : currentVal > 0
                            ? --currentVal
                            : currentVal;
                }
            });
        });
    }

    // Sidebar Toggle Handler (prevent duplicate registration)
    if (!window._sidebarHandlerInitialized) {
        window._sidebarHandlerInitialized = true;

        // Use event delegation to avoid duplicate listeners entirely
        document.body.addEventListener("click", function (e) {
            // Check if the clicked element or its parent is a sidebartoggler
            const toggler = e.target.closest(".sidebartoggler");
            if (!toggler) return;

            e.preventDefault();
            e.stopPropagation(); // Prevent event from bubbling further

            console.log("✅ Sidebar toggle clicked");

            const mainWrapper = document.getElementById("main-wrapper");
            if (!mainWrapper) {
                console.error("❌ #main-wrapper element not found");
                return;
            }

            // Toggle the sidebar visibility
            mainWrapper.classList.toggle("show-sidebar");

            // Toggle sidebar menu classes
            document.querySelectorAll(".sidebarmenu").forEach((el) => {
                el.classList.toggle("close");
            });

            // Toggle data-sidebartype attribute
            const currentType = document.body.getAttribute("data-sidebartype");
            const newType = currentType === "full" ? "mini-sidebar" : "full";
            document.body.setAttribute("data-sidebartype", newType);

            console.log("✅ Sidebar toggled to:", newType);
        }, true); // Use capture phase to catch event early
    }
});
