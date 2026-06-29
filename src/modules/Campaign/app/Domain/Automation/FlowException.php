<?php

namespace Modules\Campaign\Domain\Automation;

/**
 * Thrown when a Flow operation would violate a graph invariant
 * (cycle, duplicate id, missing reference, condition with >2 branches, …).
 *
 * Caller should map to HTTP 422.
 */
class FlowException extends \DomainException {}
