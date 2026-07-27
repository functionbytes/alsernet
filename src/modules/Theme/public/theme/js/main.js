(() => {
    'use strict';
	
	/* ================================
	   GLOBAL CONSTANTS
	================================ */
	const docEl = document.documentElement;


	/* ================================
	   APP TOGGLER
	================================ */
	const initAppToggler = () => {
		const appTogglers = document.querySelectorAll('.app-toggler');
		const appMenubar = document.getElementById('appMenubar');

		if (!appTogglers.length || !appMenubar) return;

		appTogglers.forEach(toggler => {
			toggler.addEventListener('click', () => {
				toggler.classList.toggle('active');

				if (window.innerWidth >= 1191) {
					const current = docEl.getAttribute('data-app-sidebar');
					docEl.setAttribute(
						'data-app-sidebar',
						current === 'full' ? 'mini' : 'full'
					);
				} else {
					appMenubar.classList.toggle('open');
				}
			});
		});

		appMenubar.addEventListener('mouseenter', () => {
			if (docEl.getAttribute('data-app-sidebar') === 'mini' && !appMenubar.classList.contains('no-sidebar-open')) {
				appMenubar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
					bootstrap.Tooltip.getInstance(el)?.hide();
				});
				docEl.setAttribute('data-app-sidebar', 'mini-hover');
				setTimeout(() => {
					appMenubar.querySelectorAll('.tab-pane.active [data-simplebar]').forEach(el => {
						const instance = SimpleBar.instances.get(el);
						if (instance) {
							instance.recalculate();
						} else {
							new SimpleBar(el);
						}
					});
				}, 310);
			}
		});

		appMenubar.addEventListener('mouseleave', () => {
			if (docEl.getAttribute('data-app-sidebar') === 'mini-hover') {
				docEl.setAttribute('data-app-sidebar', 'mini');
			}
		});

		appMenubar.querySelectorAll('#appMenubarTabs [data-bs-toggle="tab"]').forEach(tabLink => {
			tabLink.addEventListener('mouseenter', () => {
				if (docEl.getAttribute('data-app-sidebar') === 'mini-hover') {
					bootstrap.Tab.getOrCreateInstance(tabLink).show();
				}
			});

			tabLink.addEventListener('click', () => {
				if (docEl.getAttribute('data-app-sidebar') === 'mini') {
					appMenubar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
						bootstrap.Tooltip.getInstance(el)?.hide();
					});
					docEl.setAttribute('data-app-sidebar', 'mini-hover');
					setTimeout(() => {
						const pane = appMenubar.querySelector('.tab-pane.active [data-simplebar]');
						if (!pane) return;
						const instance = SimpleBar.instances.get(pane);
						if (instance) {
							instance.recalculate();
						} else {
							new SimpleBar(pane);
						}
					}, 310);
				}
			});
		});

	};



	/* ================================
	   SIDEBAR MENU (jQuery)
	================================ */
	const initSidebarMenu = () => {
		if (typeof jQuery === 'undefined') return;
		const $ = jQuery;

		/* =============================
		   MENU TOGGLE
		============================= */
		$('.app-navbar .menu-inner').hide();

		$('.app-navbar').on('click', 'li > a', function (e) {

			var $link = $(this);
			var $submenu = $link.next('.menu-inner');

			if ($submenu.length) {
				e.preventDefault();

				if ($link.hasClass('open')) {
					$link.removeClass('open');
					$submenu.slideUp();
				} else {
					$link.closest('.app-navbar')
						.find('a.open').removeClass('open')
						.next('.menu-inner').slideUp();

					$link.addClass('open');
					$submenu.slideDown();
				}
			}
		});

		// Active states and tab sync are managed server-side via nav.blade.php
	};


	/* ================================
	   SIDEBAR PANEL
	================================ */
	function initSidebarPanel() {
		document.addEventListener('click', function(e) {
			const toggler = e.target.closest('.sidebar-panel-toggler');
			const closeBtn = e.target.closest('.sidebar-close');
			
			if (!toggler || closeBtn) return;
			
			if (toggler) {
				const panel = document.querySelector('.app-sidebar-panel');
				if (panel) {
					panel.classList.toggle('show');
				}
			}		
			if (closeBtn) {
				document.querySelectorAll('.app-sidebar-panel').forEach(panel => {
					panel.classList.remove('show');
				});
			}
		});
	}


	document.addEventListener("DOMContentLoaded", () => {
		try {
			//Waves.init();
			initAppToggler();
			initSidebarMenu();
			initSidebarPanel();
		} catch (e) {
			console.error('Init Error:', e);
		}
	});
	
})();