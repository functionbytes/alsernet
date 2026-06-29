#!/usr/bin/env python3
"""Debug: captura screenshot y estructura DOM de la página de reseñas."""

from playwright.sync_api import sync_playwright

MAPS_URL = "https://www.google.com/maps/place/Caixilharia+PVC+Blanco/@39.5201241,-9.0202247,17z/data=!3m1!4b1!4m6!3m5!1s0xd18a5e735f923a7:0x3646712fc13b5572!8m2!3d39.5201241!4d-9.0202247!16s%2Fg%2F11swrtwt36"

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=50)
    context = browser.new_context(locale="es-ES")
    page = context.new_page()

    print("Navegando...")
    page.goto(MAPS_URL, wait_until="networkidle", timeout=60000)
    page.wait_for_timeout(4000)

    page.screenshot(path="scripts/debug_1_initial.png")
    print("Screenshot inicial guardado")

    # Buscar botones en la página
    buttons = page.query_selector_all('button')
    print(f"\nBotones encontrados: {len(buttons)}")
    for b in buttons[:20]:
        label = b.get_attribute("aria-label") or ""
        text = b.inner_text()[:40].strip()
        if label or text:
            print(f"  [{label}] {text}")

    # Intentar click en tab de reseñas
    print("\nBuscando tab de reseñas...")
    for b in buttons:
        label = (b.get_attribute("aria-label") or "").lower()
        text = b.inner_text().lower().strip()
        if any(k in label or k in text for k in ["reseña", "review", "opinión", "valoracion"]):
            print(f"  → Clickando: [{label}] {text[:40]}")
            b.click()
            page.wait_for_timeout(3000)
            break

    page.screenshot(path="scripts/debug_2_after_tab.png")
    print("Screenshot después de tab guardado")

    # Scroll
    page.evaluate("document.querySelectorAll('.m6QErb').forEach(el => el.scrollTop += 2000)")
    page.wait_for_timeout(2000)

    # Ver clases CSS de los contenedores de reseñas
    html_snippet = page.evaluate("""
        () => {
            const containers = document.querySelectorAll('[data-review-id], [jsaction*="review"], .jftiEf, .GHT2ce');
            return Array.from(containers).slice(0, 3).map(el => ({
                tag: el.tagName,
                classes: el.className.substring(0, 100),
                dataReviewId: el.getAttribute('data-review-id'),
                text: el.innerText.substring(0, 100)
            }));
        }
    """)
    print(f"\nContenedores de reseña encontrados: {html_snippet}")

    # Buscar cualquier contenedor con estrellas
    stars_containers = page.evaluate("""
        () => {
            const els = document.querySelectorAll('[aria-label*="star"], [aria-label*="strel"], [aria-label*="estr"]');
            return Array.from(els).slice(0, 5).map(el => ({
                tag: el.tagName,
                classes: el.className.substring(0, 80),
                label: el.getAttribute('aria-label'),
                parentClasses: el.parentElement ? el.parentElement.className.substring(0, 80) : ''
            }));
        }
    """)
    print(f"\nElementos con estrellas: {stars_containers}")

    # Dump de clases únicas de divs en el panel
    all_classes = page.evaluate("""
        () => {
            const seen = new Set();
            document.querySelectorAll('.m6QErb *').forEach(el => {
                el.className.toString().split(' ').forEach(c => { if(c) seen.add(c); });
            });
            return Array.from(seen).slice(0, 60);
        }
    """)
    print(f"\nClases CSS en panel (muestra): {all_classes}")

    browser.close()
    print("\nDebug terminado. Revisa scripts/debug_*.png")
