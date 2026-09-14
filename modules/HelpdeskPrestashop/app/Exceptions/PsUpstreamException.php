<?php

namespace Modules\HelpdeskPrestashop\Exceptions;

use RuntimeException;

/**
 * Thrown by PrestashopContextService when a request fails due to network
 * errors, HTTP 5xx responses, or an open circuit breaker — i.e. an
 * infrastructure problem rather than a semantic API error (ok=false).
 *
 * Controllers catching this should respond with 503 Service Unavailable
 * rather than 404/422, which would mislead agents into thinking the
 * resource does not exist.
 */
class PsUpstreamException extends RuntimeException {}
