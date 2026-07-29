
// Caso especifico de filtro devoluciones
$(document).ready(function() {

    loader = '<div class="text-center animated bounce infinite flash primary-color p10"> <i class="zmdi zmdi-hc-5x zmdi-spinner zmdi-hc-spin"></i> <br> Cargando...</div>';

	$("body").addClass("alsernet");

	//MENU MOBILE

	$(window).on("resize", function() {
		$("body").removeClass("mmenu-active");
	});

	$("body").on("click", ".mobile-menu-close", function() {
		$("body").removeClass("mmenu-active");
	});

	$('body').on("click", ".mobile-menu-overlay", function(e) {
		$("body").removeClass("mmenu-active");
	});

	$("body").on("click", ".mobile-menu-toggle", function(e) {
		e.preventDefault();
		
		var pathArray = window.location.pathname.split('/');
		var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
		var iso = language!= '' ? language : 'es';

		if(iso!='') {
			get_panel_mobile($(this));
		}

		$("body").addClass("mmenu-active");

	});

	function get_panel_mobile($this) {

		var pathArray = window.location.pathname.split('/');
		var language = (pathArray.length > 1 && pathArray[1].length === 2) ? pathArray[1] : '';
		var iso = language!= '' ? language : 'es';
		
		var fullUrl = "/module/alsernetmenu/menu?method=mobile&language="+iso;

			$.ajax({
				cache: !0,
				url: fullUrl
			}).done(function(data) {
				$(".mobile-menu-wrapper").html(data);
			}).fail(function(err) {
				reset_panel_mobile()
			});

	}


	$("body").on("click", ".mobile-menu-wrapper .mobile .nav-item .category-item", function() {
		var $this = $(this);
		toggleActiveCategory($this);
	});
	
	function toggleActiveCategory(element) {
		var isActive = element.hasClass("active");
		$(".mobile-menu-wrapper .mobile .nav-item .category-item").removeClass("active");
		$(".mobile-menu-wrapper .mobile .nav-item .category-item").each(function() {
			toggleIconCategory($(this), false);
		});
	
		if (!isActive) {
			element.addClass("active");
			toggleIconCategory(element, true);
		}
	}
	
	function toggleIconCategory(element, isActive) {
		var iconDown = element.find('.fa-chevron-down');
		var iconUp = element.find('.fa-chevron-up');
		var submenu = element.next('.all-items');
	
		if (isActive) {
			iconDown.hide();
			iconUp.show();
			submenu.addClass('show');
		} else {
			iconDown.show();
			iconUp.hide();
			submenu.removeClass('show');
		}
	}


	$("body").on("click", ".mobile-menu-wrapper .mobile .nav-item .all-items .category-all", function() {
		console.log("subcategoria");
		var $this = $(this);
		toggleActiveSubcategory($this);
	});

	function toggleActiveSubcategory(element) {
		var isActive = element.hasClass("active");
		$(".mobile-menu-wrapper .mobile .nav-item .all-items .category-all").removeClass("active");
		$(".mobile-menu-wrapper .mobile .nav-item .all-items .category-all").each(function() {
			toggleIconSubcategory($(this), false);
		});
	
		if (!isActive) {
			element.addClass("active");
			toggleIconSubcategory(element, true);
		}
	}
	
	function toggleIconSubcategory(element, isActive) {
		var iconDown = element.find('.fa-chevron-down');
		var iconUp = element.find('.fa-chevron-up');
		var submenu = element.next('.items-submenu');
	
		if (isActive) {
			iconDown.hide();
			iconUp.show();
			submenu.addClass('show');
		} else {
			iconDown.show();
			iconUp.hide();
			submenu.removeClass('show');
		}
	}

	$("body").on("click", ".mobile-menu-wrapper .mobile .nav-item .items-submenu .panel-submenu .item_sub .title-subcategory", function() {
        var $this = $(this);

        if ($this.hasClass("active")) {
            $this.removeClass("active");
            toggleSubcategoryIcon($this, false);

        } else {
            $(".mobile .nav-item .items-submenu .panel-submenu .item_sub .title-subcategory").each(function() {
                var $item = $(this);
                $item.removeClass("active");
                toggleSubcategoryIcon($item, false);
            });

            $this.addClass("active");
            toggleSubcategoryIcon($this, true);
        }
    });

    function toggleSubcategoryIcon(element, isActive) {
        var submenu = element.next('.item-subcategory');
        var iconDown = element.find('.fa-chevron-down');
        var iconUp = element.find('.fa-chevron-up');

        if (isActive) {
            iconDown.hide();
            iconUp.show();
            submenu.addClass('show');
        } else {
            iconDown.show();
            iconUp.hide();
            submenu.removeClass('show');
        }
    }



	//MENU AWEB
	$("body").on("click", ".navs .nav-item a.nav-action", function(e) {
		e.preventDefault();
	
		var $this = $(this);
		var link = $this.data('load-panel');
		var subcategory = $this.data('id_subcategory');
	
		console.log(subcategory, link);
	
		if (link !== 'not_async' && !$this.data('loading')) {
			$this.data('loading', true);
			$.ajax({
				cache: true,
				url: link
			}).done(function(data) {
				$this.data('load-panel', 'not_async');
				$this.attr('data-load-panel', 'not_async');
				$this.next(".items-submenu").html(data);
			}).fail(function() {
				console.log("Error en la carga de datos.");
			}).always(function() {
				$this.data('loading', false);
			});
	
		} else {
			console.log("El enlace ya está en estado not_async.");
		}
	
		toggleActiveMenu($this);
	});
	
	function toggleActiveMenu($element) {
		var isActive = !$element.hasClass("active");
		$(".nav-action").removeClass("active").each(function() {
			toggleIconMenu($(this), false);
			$(this).next(".items-submenu").removeClass("show");
		});
		$element.toggleClass("active", isActive);
		toggleIconMenu($element, isActive);
		if (isActive) {
			$element.next(".items-submenu").addClass("show");
		}
	}
	
	function toggleIconMenu(element, isActive) {
		var iconContainer = element.find('.collapse-icons');
		var iconDown = iconContainer.find('.down');
		var iconUp = iconContainer.find('.up');
		iconDown.toggle(!isActive);
		iconUp.toggle(isActive);
	}

	let hoverTimeout;

	$(".supernav-content-wrapper .nav .mm-menu .nav-item a").on("mouseenter", function() {
		const $this = $(this);
		const $nav = $this.closest('.nav-item');
		const link = $this.data('load-panel');

		clearTimeout(hoverTimeout);

		hoverTimeout = setTimeout(function() {
			$('.nav-item').removeClass('hovered');

			if (link && link !== 'not_async' && !$this.data('loading')) {
				$this.data('loading', true);
				$.ajax({
					cache: true,
					url: link
				}).done(function(data) {
					$this.data('load-panel', 'not_async');
					$this.attr('data-load-panel', 'not_async');
					$this.next(".items-submenu").html(data);
					$nav.addClass('hovered');
				}).fail(function() {
					reset_panel_categories();
				}).always(function() {
					$this.removeData('loading');
					$this.attr('data-load-panel', 'not_async');
				});

			} else if (link === 'not_async') {
				$nav.addClass('hovered');
			}
		}, 300);
	});


	$(".supernav-content-wrapper .nav .mm-menu .nav-item a").on("mouseleave", function() {
		clearTimeout(hoverTimeout);
		$(this).closest('.nav-item').removeClass('hovered');
	});
	
	$(".supernav-content-wrapper .nav .mm-menu .nav-item a").on("mouseleave", function() {
		var $this = $(this);
		var $navItem = $this.closest('.nav-item');

		setTimeout(function() {
			if (!$navItem.find('.items-submenu:hover').length) {
				$navItem.removeClass('hovered');
			}
		}, 100);
	});
	

	$(".supernav-content-wrapper .nav .mm-menu .nav-item .items-submenu").on("mouseenter", function() {
		$(this).closest('.nav-item').addClass('hovered');
	}).on("mouseleave", function() {
		var $navItem = $(this).closest('.nav-item');
	

		setTimeout(function() {
			if (!$navItem.find('a:hover').length) {
				$navItem.removeClass('hovered');
			}
		}, 100);
	});
	

	function reset_panel_categories() {
		$("#base-menu").removeClass("d-none");
		$(".back-menu").attr('data-id_category', 'none')
	}

	function reset_panel_subcategories() {
		$("#subcat-menu").addClass("d-none");
		$("#base-menu").removeClass("d-none");
		$(".back-menu").attr('data-id_category', 'none')
	}



	//MENU AWEB
	$("body").on("click", ".navs.category .title", function(e) {
        e.preventDefault();

        var $category = $(this).closest('.navs.category');
        toggleActiveMenuCategory($category);
    });

    function toggleActiveMenuCategory($element) {
        var isActive = !$element.hasClass("active");
        $(".navs.category").removeClass("active").find(".menu-content").removeClass("show");

        if (isActive) {
            $element.addClass("active").find(".menu-content").addClass("show");
        }

        toggleIconMenuCategory($element, isActive);
    }

    function toggleIconMenuCategory($element, isActive) {
        var iconContainer = $element.find('.collapse-icons');
        var iconDown = iconContainer.find('.down');
        var iconUp = iconContainer.find('.up');
        iconDown.toggle(!isActive);
        iconUp.toggle(isActive);
    }


});


