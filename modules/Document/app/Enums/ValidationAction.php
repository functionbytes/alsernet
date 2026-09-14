<?php

namespace Modules\Document\Enums;

enum ValidationAction: string
{
    case APPROVE = 'approve';

    case REJECT = 'reject';
    case SEND_APPROVAL_EMAIL = 'send_approval_email';

    case ADD_COMMENT = 'add_comment';

    case REQUEST_ADDITIONAL_DOCS = 'request_additional_docs';

    case MOVE_TO_NEXT_STAGE = 'move_to_next_stage';

    case ACCESS_ADDITIONAL_FILES = 'access_additional_files';

    public function label(): string
    {
        return match ($this) {
            self::APPROVE => 'Aprobar',
            self::REJECT => 'Rechazar',
            self::SEND_APPROVAL_EMAIL => 'Enviar correo de aprobación',
            self::ADD_COMMENT => 'Agregar comentario',
            self::REQUEST_ADDITIONAL_DOCS => 'Solicitar documentos adicionales',
            self::MOVE_TO_NEXT_STAGE => 'Pasar a siguiente etapa',
            self::ACCESS_ADDITIONAL_FILES => 'Acceder archivos adicionales',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APPROVE => 'Valida la etapa actual y avanza a la siguiente (o completa si es la última)',
            self::REJECT => 'Rechaza el documento completo y lo devuelve para revisión',
            self::SEND_APPROVAL_EMAIL => 'Envía correo de aprobación al cliente',
            self::ADD_COMMENT => 'Agrega comentarios o notas internas',
            self::REQUEST_ADDITIONAL_DOCS => 'Solicita documentos adicionales al cliente',
            self::MOVE_TO_NEXT_STAGE => 'Avanza a la siguiente etapa sin aprobar completamente',
            self::ACCESS_ADDITIONAL_FILES => 'Accede a archivos y adjuntos adicionales',
        };
    }
}
