<?php

use Modules\Template\Facades\Theme;

// Las rutas del CMS están definidas en los módulos correspondientes (Page, Blog, etc.)
// Theme::routes() en inoqualabs es un no-op por compatibilidad.
Theme::routes();
