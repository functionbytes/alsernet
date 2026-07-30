<?php

namespace Modules\HelpdeskEmailLog\Contracts;

/**
 * Marker interface for Mailables whose body must never be persisted in
 * the email log (password resets, magic links, OTPs, API tokens...).
 *
 * Subject, recipients and metadata are still logged; only body_html and
 * body_text are dropped. Use this for any email containing short-lived
 * credentials or otherwise sensitive content.
 */
interface RedactsEmailLogBody {}
