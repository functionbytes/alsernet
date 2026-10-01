// ─── Node type metadata (labels, colors, icons) and layout constants ──────────

export const NODE_LABELS: Record<string, string> = {
    start:              'Inicio',
    message:            'Mensaje',
    quick_replies:      'Respuestas rápidas',
    collect_input:      'Capturar input',
    identify_customer:  'Identificar cliente',
    request_documents:  'Solicitar documentos',
    document_link:       'Enlace de documentos',
    branches:           'Condición',
    branchItem:         'Rama',
    action:             'Acción',
    delay:              'Espera',
    ai_response:        'Respuesta IA',
    ai_agent:           'Agente IA',
    order_lookup:       'Consultar pedido',
    http_request:       'Petición HTTP',
    rich_message:       'Mensaje enriquecido',
    send_file:          'Enviar archivo',
    csat:               'Valoración (CSAT)',
    business_hours:     'Horario de atención',
    add_tag:            'Agregar etiqueta',
    set_attribute:      'Establecer atributo',
    go_to_step:         'Ir a paso',
    call_flow:          'Llamar procedimiento',
    return:             'Volver al flow llamante',
    ai_action:          'Acción IA',
    create_ticket:      'Crear ticket',
    transfer:           'Transferir agente',
    close:              'Cerrar conversación',
    end:                'Fin',
};

// Colors grouped by category (bedesk-style): content=green, data/actions=purple,
// logic=blue, terminal=orange, start=gray. Green uses the project primary (#90bb13).
export const CAT_GREEN  = '#90bb13'; // content the bot shows
export const CAT_PURPLE = '#615fff'; // data actions (tools/attributes/tags)
export const CAT_BLUE   = '#2b7fff'; // logic / conditions
export const CAT_ORANGE = '#ff6900'; // terminal / routing
export const CAT_GRAY   = '#62748e'; // start

export const NODE_COLORS: Record<string, string> = {
    start:             CAT_GRAY,
    message:           CAT_GREEN,
    quick_replies:     CAT_GREEN,
    collect_input:     CAT_GREEN,
    identify_customer: CAT_GREEN,
    request_documents: CAT_GREEN,
    document_link:     CAT_GREEN,
    rich_message:      CAT_GREEN,
    send_file:         CAT_GREEN,
    ai_response:       CAT_GREEN,
    ai_agent:          CAT_PURPLE,
    csat:              CAT_GREEN,
    branches:          CAT_BLUE,
    branchItem:        CAT_BLUE,
    business_hours:    CAT_BLUE,
    action:            CAT_PURPLE,
    set_attribute:     CAT_PURPLE,
    add_tag:           CAT_PURPLE,
    http_request:      CAT_PURPLE,
    order_lookup:      CAT_PURPLE,
    delay:             CAT_PURPLE,
    create_ticket:     CAT_PURPLE,
    go_to_step:        CAT_ORANGE,
    call_flow:         CAT_PURPLE,
    return:            CAT_ORANGE,
    ai_action:         CAT_PURPLE,
    transfer:          CAT_ORANGE,
    close:             CAT_ORANGE,
    end:               CAT_ORANGE,
};

export const NODE_ICONS: Record<string, string> = {
    start:             'fas fa-play',
    message:           'fas fa-comment',
    quick_replies:     'fas fa-list-ul',
    collect_input:     'fas fa-keyboard',
    identify_customer: 'fas fa-id-card',
    request_documents: 'fas fa-file-upload',
    document_link:     'fas fa-folder-open',
    branches:          'fas fa-code-branch',
    branchItem:        'fas fa-chevron-right',
    action:            'fas fa-bolt',
    delay:             'fas fa-clock',
    ai_response:       'fas fa-robot',
    ai_agent:          'fas fa-wand-magic-sparkles',
    order_lookup:      'fas fa-box',
    http_request:      'fas fa-plug',
    rich_message:      'fas fa-images',
    send_file:         'fas fa-paperclip',
    csat:              'fas fa-star',
    business_hours:    'fas fa-business-time',
    add_tag:           'fas fa-tag',
    set_attribute:     'fas fa-sliders',
    go_to_step:        'fas fa-share',
    call_flow:         'fas fa-diagram-project',
    return:            'fas fa-rotate-left',
    ai_action:         'fas fa-bolt-lightning',
    create_ticket:     'fas fa-ticket',
    transfer:          'fas fa-headset',
    close:             'fas fa-circle-xmark',
    end:               'fas fa-flag-checkered',
};

// Terminal node types — they end the branch (no child nodes, addStepNode shown before them).
export const TERMINAL_TYPES = new Set(['end', 'transfer', 'close', 'go_to_step', 'return']);

export const ADDABLE_TYPES = [
    'message', 'rich_message', 'send_file', 'quick_replies', 'ai_response', 'ai_agent', 'collect_input', 'identify_customer',
    'order_lookup', 'request_documents', 'document_link', 'business_hours', 'branches', 'http_request', 'action', 'delay',
    'csat', 'add_tag', 'set_attribute', 'create_ticket', 'ai_action', 'call_flow', 'go_to_step', 'return', 'transfer', 'close', 'end',
];

export const DOC_TYPE_LABELS: Record<string, string> = {
    dni_frontal:   'DNI/NIE frontal',
    dni_trasera:   'DNI/NIE trasera',
    pasaporte:     'Pasaporte',
    contrato:      'Contrato firmado',
    factura:       'Factura de compra',
    foto_producto: 'Foto del producto',
    proforma:      'Factura proforma',
    iban:          'Certificado bancario / IBAN',
    selfie:        'Selfie con documento',
    recibo:        'Recibo',
};

// Node types that end a branch when validating (no continuation expected).
export const VALIDATE_TERMINAL = new Set(['end', 'transfer', 'close', 'go_to_step', 'return']);
// Node types that wait for customer input (no "no continuation" warning needed).
export const VALIDATE_WAIT = new Set(['collect_input', 'quick_replies', 'identify_customer', 'request_documents', 'csat', 'rich_message']);

export const NODE_WIDTH  = 240;
export const NODE_HEIGHT = 72;
export const V_GAP       = 120;
export const H_GAP       = 280;

export const edgeStyle = { stroke: '#d1d5db', strokeWidth: 1.5 };
