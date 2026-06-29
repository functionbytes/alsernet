#!/usr/bin/env python3
"""
Scraper de reseñas de Google Maps usando Playwright.
"""

import json
import re
from datetime import datetime
from playwright.sync_api import sync_playwright

MAPS_URL = "https://www.google.com/maps/place/Caixilharia+PVC+Blanco/@39.5201241,-9.0202247,17z/data=!3m1!4b1!4m6!3m5!1s0xd18a5e735f923a7:0x3646712fc13b5572!8m2!3d39.5201241!4d-9.0202247!16s%2Fg%2F11swrtwt36"
LOCATION_ID = 1
OUTPUT_FILE = "scripts/reviews_output.json"

RATING_MAP = {1: "ONE", 2: "TWO", 3: "THREE", 4: "FOUR", 5: "FIVE"}


def get_text(handle, selector: str) -> str:
    el = handle.query_selector(selector)
    return el.inner_text().strip() if el else ""


def get_attr(handle, selector: str, attr: str) -> str | None:
    el = handle.query_selector(selector)
    return el.get_attribute(attr) if el else None


def parse_stars(handle) -> int:
    # aria-label en el contenedor de estrellas: "4 stars" / "4 estrellas"
    for sel in ['span[aria-label*="star"]', 'span[aria-label*="strel"]', '[role="img"][aria-label]']:
        el = handle.query_selector(sel)
        if el:
            label = el.get_attribute("aria-label") or ""
            m = re.search(r"(\d)", label)
            if m:
                return int(m.group(1))
    return 5


def scrape_reviews(url: str) -> list[dict]:
    reviews = []

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False, slow_mo=30)
        context = browser.new_context(
            locale="es-ES",
            user_agent=(
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/120.0.0.0 Safari/537.36"
            ),
        )
        page = context.new_page()

        print(f"Navegando a Google Maps...")
        page.goto(url, wait_until="networkidle", timeout=60000)
        page.wait_for_timeout(3000)

        # Click en tab de reseñas
        for sel in [
            'button[aria-label*="eseña"]',
            'button[aria-label*="eview"]',
            '[data-tab-index="1"]',
        ]:
            try:
                btn = page.locator(sel).first
                if btn.is_visible(timeout=2000):
                    btn.click()
                    page.wait_for_timeout(2000)
                    break
            except Exception:
                continue

        # Scroll para cargar todas las reseñas
        print("Cargando reseñas...")
        prev_count = 0
        stale = 0
        while stale < 6:
            page.evaluate("""
                const panels = document.querySelectorAll('.m6QErb');
                panels.forEach(p => p.scrollTop += 4000);
            """)
            page.wait_for_timeout(1200)

            cards = page.query_selector_all('.jftiEf')
            count = len(cards)
            if count == prev_count:
                stale += 1
            else:
                print(f"  → {count} reseñas cargadas...")
                stale = 0
                prev_count = count

        print(f"Total encontradas: {prev_count}")

        # Expandir todos los "Más" antes de extraer
        try:
            more_buttons = page.query_selector_all('button.w8nwRe, button[aria-label*="Más"], button[aria-label*="More"]')
            for btn in more_buttons:
                try:
                    btn.click()
                    page.wait_for_timeout(100)
                except Exception:
                    pass
        except Exception:
            pass

        # Extraer datos
        cards = page.query_selector_all('.jftiEf')
        for idx, card in enumerate(cards):
            try:
                review_id = card.get_attribute("data-review-id") or f"manual_{LOCATION_ID}_{idx + 1}"
                reviewer_name = get_text(card, '.d4r55') or get_text(card, '.kvMYJc') or f"Usuario {idx + 1}"
                photo_url     = get_attr(card, 'img.NBa7we', 'src') or get_attr(card, 'button img', 'src')
                stars         = parse_stars(card)
                comment       = get_text(card, '.MyEned span') or get_text(card, '.wiI7pd')
                date_str      = get_text(card, '.rsqaWe') or get_text(card, '.xRkPPb')
                reply_text    = get_text(card, '.CDe7pd .wiI7pd') or None

                reviews.append({
                    "google_review_id":  review_id,
                    "location_id":       LOCATION_ID,
                    "reviewer_name":     reviewer_name,
                    "reviewer_photo_url": photo_url,
                    "star_rating":       RATING_MAP.get(stars, "FIVE"),
                    "comment":           comment or None,
                    "google_reply_text": reply_text or None,
                    "review_time_raw":   date_str,
                    "source":            "manual",
                    "synced_at":         datetime.now().isoformat(),
                })
                print(f"  [{idx + 1:>3}] {reviewer_name:<30} {stars}★  {date_str}")

            except Exception as e:
                print(f"  Error en reseña {idx}: {e}")

        browser.close()

    return reviews


def main():
    print("=" * 60)
    print("Scraper — Caixilharia PVC Blanco")
    print("=" * 60)

    reviews = scrape_reviews(MAPS_URL)

    if not reviews:
        print("No se encontraron reseñas.")
        return

    output = {
        "location_id":   LOCATION_ID,
        "location_name": "Caixilharia PVC Blanco",
        "scraped_at":    datetime.now().isoformat(),
        "total":         len(reviews),
        "reviews":       reviews,
    }

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print(f"\n✓ {len(reviews)} reseñas guardadas en: {OUTPUT_FILE}")


if __name__ == "__main__":
    main()
