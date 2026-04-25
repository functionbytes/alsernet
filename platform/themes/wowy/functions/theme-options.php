<?php

/**
 * Stub de compatibilidad - Opciones de tema Wowy para inoqualabs.
 *
 * En Botble, este archivo registraba campos de opciones de tema mediante
 * theme_option()->setField(). En inoqualabs, theme_option() es un helper
 * de solo lectura que devuelve valores desde Setting::get().
 *
 * Las opciones de tema se gestionan directamente desde el panel de admin
 * de inoqualabs (Settings > Opciones del tema).
 */

// No-op: las opciones se leen via theme_option('key') en las vistas.
