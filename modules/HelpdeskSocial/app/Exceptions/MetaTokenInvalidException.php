<?php

namespace Modules\HelpdeskSocial\Exceptions;

use RuntimeException;

/**
 * Meta rechazó el token de acceso (HTTP 401 o error code 190): el token
 * expiró, fue revocado o la sesión se invalidó. Reintentar no sirve de nada.
 */
class MetaTokenInvalidException extends RuntimeException {}
