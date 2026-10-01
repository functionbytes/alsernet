<?php

namespace Modules\HelpdeskMedia\Services;

/**
 * Fallo temporal (429, 5xx, red): el job debe reintentarse con backoff.
 */
class TransientMediaException extends \RuntimeException {}
