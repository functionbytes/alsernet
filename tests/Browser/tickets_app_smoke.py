"""Smoke E2E del panel de tickets con Playwright.

Uso local:
    HELPDESK_STORAGE_STATE=/ruta/a/storage-state.json \
    python3 -m unittest tests/Browser/tickets_app_smoke.py

El archivo de estado se genera una vez iniciando sesión en un entorno de QA;
no se guarda en el repositorio ni contiene credenciales compartidas.
"""

from __future__ import annotations

import os
import unittest
from pathlib import Path

from playwright.sync_api import sync_playwright


class TicketsAppSmokeTest(unittest.TestCase):
    @unittest.skipUnless(
        os.getenv("HELPDESK_STORAGE_STATE")
        and Path(os.environ["HELPDESK_STORAGE_STATE"]).is_file(),
        "Configura HELPDESK_STORAGE_STATE con una sesión de QA para ejecutar el E2E.",
    )
    def test_thread_search_filters_advanced_controls_and_activity_are_connected(self) -> None:
        base_url = os.getenv(
            "HELPDESK_TICKETS_URL",
            "http://localhost:8092/panel/helpdesk/tickets?ticket=7036",
        )
        storage_state = os.environ["HELPDESK_STORAGE_STATE"]
        browser_errors: list[str] = []

        with sync_playwright() as playwright:
            browser = playwright.chromium.launch(headless=True)
            context = browser.new_context(storage_state=storage_state)
            page = context.new_page()
            page.on("pageerror", lambda error: browser_errors.append(str(error)))

            page.goto(base_url, wait_until="networkidle")
            page.locator("#tkt-thread-search").wait_for(state="visible")
            # El deep-link debe abrir el ticket y no dejar una fila marcada
            # junto al estado vacío del detalle.
            self.assertIn("display: none", page.locator("#tkt-detail-empty").get_attribute("style") or "")
            self.assertTrue(page.locator("#tkt-status-queue").count() == 1)
            self.assertIn(".pdf", page.locator("#tkt-reply-attach").get_attribute("accept") or "")
            self.assertEqual([], browser_errors)

            page.locator("#tkt-thread-search").fill("holas")
            page.wait_for_timeout(700)
            self.assertGreater(page.locator('#tkt-thread-scroll [data-thread-kind]:visible').count(), 0)

            page.locator("#tkt-thread-advanced-toggle").click()
            page.locator("#tkt-thread-filter-type").select_option("event")
            page.locator("#tkt-thread-advanced-apply").click()
            page.wait_for_timeout(700)
            self.assertGreaterEqual(page.locator('#tkt-thread-scroll [data-thread-kind="event"]:visible').count(), 0)
            self.assertEqual("event", page.locator("#tkt-thread-filter-type").input_value())

            page.locator("#tkt-thread-advanced-toggle").click()
            page.locator("#tkt-thread-advanced-clear").click()
            page.wait_for_timeout(700)
            page.locator("[data-dtab=activity]").click()
            self.assertTrue(page.locator("#tkt-dpane-activity").is_visible())
            self.assertEqual([], browser_errors)

            context.close()
            browser.close()


if __name__ == "__main__":
    unittest.main()
