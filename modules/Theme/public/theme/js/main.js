(() => {
    'use strict';
	
	/* ================================
	   GLOBAL CONSTANTS
	================================ */
	const docEl = document.documentElement;


	/* ================================
	   APP TOGGLER
	================================ */
	/* El menu lateral tiene dos modos y el breakpoint que los separa es el mismo
	   que usa `css/nav.css`:
	     · > 1480px  el panel se contrae/expande en linea  -> html[data-app-sidebar]
	     · <= 1480px el panel se abre como capa superpuesta -> .app-menubar-tabs.open
	   Antes este JS cortaba en 1191px mientras el CSS cortaba en 1480px, asi que
	   entre 1200px y 1480px el boton solo cambiaba un atributo que ninguna regla
	   consultaba: pulsarlo no hacia absolutamente nada. */
	const SIDEBAR_OVERLAY_MQ = '(max-width: 1480px)';
	const SIDEBAR_STORAGE_KEY = 'mc-app-sidebar';

	const initAppToggler = () => {
		const appTogglers = document.querySelectorAll('.app-toggler');
		const appMenubar = document.getElementById('appMenubar');

		if (!appTogglers.length || !appMenubar) return;

		const overlayMq = window.matchMedia(SIDEBAR_OVERLAY_MQ);
		const isOverlay = () => overlayMq.matches;

		const readStored = () => {
			try { return localStorage.getItem(SIDEBAR_STORAGE_KEY); } catch (e) { return null; }
		};
		const writeStored = value => {
			try { localStorage.setItem(SIDEBAR_STORAGE_KEY, value); } catch (e) { /* modo privado */ }
		};

		// `mini-hover` es una vista previa al pasar el raton, no un estado elegido:
		// para el boton cuenta como contraido.
		const isExpanded = () => isOverlay()
			? appMenubar.classList.contains('open')
			: docEl.getAttribute('data-app-sidebar') === 'full';

		const recalcScrollbars = () => {
			appMenubar.querySelectorAll('.tab-pane.active [data-simplebar]').forEach(el => {
				const instance = SimpleBar.instances.get(el);
				if (instance) {
					instance.recalculate();
				} else {
					new SimpleBar(el);
				}
			});
		};

		const syncTogglers = () => {
			const expanded = isExpanded();
			const overlay = isOverlay();
			appTogglers.forEach(toggler => {
				toggler.setAttribute('aria-expanded', expanded ? 'true' : 'false');
				toggler.setAttribute('aria-label', expanded ? 'Contraer menu lateral' : 'Desplegar menu lateral');
				// `Tooltip.js` promociona cualquier `title` a `data-tooltip` y borra el
				// `title`: si escribieramos ahi, el texto se congelaba en el primer valor.
				toggler.setAttribute('data-tooltip', expanded ? 'Contraer menú' : 'Desplegar menú');
				// `.active` marca "cajon abierto en modo <=1480px"; en CSS solo se usa
				// para pintar el fondo oscuro por debajo de 576px (movil estrecho,
				// unico rango que sigue flotando encima en vez de empujar el
				// contenido) — inofensivo dejarlo puesto tambien por encima de eso.
				toggler.classList.toggle('active', overlay && expanded);
			});
		};

		// Cada modo guarda su estado en un sitio distinto; al cruzar el breakpoint
		// hay que limpiar el del otro o queda una clase/atributo fantasma que
		// congela el ancho del rail o deja el backdrop puesto.
		const applyMode = () => {
			appMenubar.classList.remove('open');

			if (isOverlay()) {
				docEl.removeAttribute('data-app-sidebar');
			} else {
				docEl.setAttribute('data-app-sidebar', readStored() === 'mini' ? 'mini' : 'full');
			}

			syncTogglers();
		};

		const openOverlay = () => {
			if (!isOverlay() || appMenubar.classList.contains('open')) return;
			appMenubar.classList.add('open');
			syncTogglers();
			setTimeout(recalcScrollbars, 310);
		};

		const toggle = () => {
			if (isOverlay()) {
				const willOpen = !appMenubar.classList.contains('open');
				appMenubar.classList.toggle('open', willOpen);
				if (willOpen) setTimeout(recalcScrollbars, 310);
			} else {
				// `applyMode()` garantiza que el atributo ya vale 'full' o 'mini'.
				// Antes arrancaba sin poner y el primer clic escribia 'full' -el estado
				// en el que ya estabas-, asi que hacian falta dos clics para contraer.
				const next = docEl.getAttribute('data-app-sidebar') === 'full' ? 'mini' : 'full';
				docEl.setAttribute('data-app-sidebar', next);
				writeStored(next);
			}

			syncTogglers();
		};

		applyMode();

		appTogglers.forEach(toggler => {
			toggler.addEventListener('click', toggle);
		});

		overlayMq.addEventListener('change', applyMode);

		document.addEventListener('keydown', e => {
			if (e.key === 'Escape' && isOverlay() && appMenubar.classList.contains('open')) {
				appMenubar.classList.remove('open');
				syncTogglers();
			}
		});

		appMenubar.addEventListener('mouseenter', () => {
			if (isOverlay()) return;
			if (docEl.getAttribute('data-app-sidebar') === 'mini' && !appMenubar.classList.contains('no-sidebar-open')) {
				appMenubar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
					bootstrap.Tooltip.getInstance(el)?.hide();
				});
				docEl.setAttribute('data-app-sidebar', 'mini-hover');
				setTimeout(recalcScrollbars, 310);
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
				// Por debajo de 1480px el panel vive fuera de pantalla: pulsar un icono
				// del rail cambiaba de pestaña sin mostrar nada. Ahora abre la capa.
				if (isOverlay()) {
					openOverlay();
					return;
				}

				if (docEl.getAttribute('data-app-sidebar') === 'mini') {
					appMenubar.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
						bootstrap.Tooltip.getInstance(el)?.hide();
					});
					docEl.setAttribute('data-app-sidebar', 'mini-hover');
					setTimeout(recalcScrollbars, 310);
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