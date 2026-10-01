<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use RuntimeException;

/**
 * A procedure can't be entered (not callable, cycle, too deep). The reason is
 * logged by the caller, which decides what the flow does next.
 */
class FlowCallRefused extends RuntimeException {}
