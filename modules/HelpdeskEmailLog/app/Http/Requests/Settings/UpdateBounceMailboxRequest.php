<?php

namespace Modules\HelpdeskEmailLog\Http\Requests\Settings;

/**
 * Mismas reglas que el alta — la contraseña opcional (se conserva la ya
 * guardada si llega vacía) ya se resuelve en
 * BounceMailboxesRepository::update(), no aquí.
 */
class UpdateBounceMailboxRequest extends StoreBounceMailboxRequest {}
