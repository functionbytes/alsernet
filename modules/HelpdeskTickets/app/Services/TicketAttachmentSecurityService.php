<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\Helpdesk\Services\AttachmentSecurityService;

/**
 * Backwards-compatible ticket namespace for the shared Helpdesk security
 * boundary. All ticket and conversation upload paths now use the same
 * settings and ClamAV implementation.
 */
class TicketAttachmentSecurityService extends AttachmentSecurityService {}
