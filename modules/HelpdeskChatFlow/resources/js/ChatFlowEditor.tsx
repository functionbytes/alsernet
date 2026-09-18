import React, { useState, useCallback, useEffect, useRef } from 'react';
import {
    ReactFlow,
    Node,
    Edge,
    useNodesState,
    useEdgesState,
    Background,
    Controls,
    MiniMap,
    Panel,
    Handle,
    Position,
    NodeProps,
    BackgroundVariant,
} from '@xyflow/react';
import axios from 'axios';

// ─── Types ────────────────────────────────────────────────────────────────────

interface BackendNode {
    id: string;
    type: string;
    parentId: string | null;
    label: string;
    data: Record<string, any>;
}

interface Condition {
    variable: string;
    operator: string;
    value: string;
}

interface AssignOption { id: number; name: string; }

interface ChatFlowEditorProps {
    chatFlowId: number;
    chatFlowName: string;
    chatFlowStatus: string;
    nodes: BackendNode[];
    settings?: Record<string, any>;
    agents?: AssignOption[];
    groups?: AssignOption[];
    saveUrl: string;
    publishUrl: string;
    indexUrl: string;
    csrfToken: string;
}

// ─── Constants ────────────────────────────────────────────────────────────────

const NODE_LABELS: Record<string, string> = {
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
    transfer:           'Transferir agente',
    close:              'Cerrar conversación',
    end:                'Fin',
};

// Colors grouped by category (bedesk-style): content=green, data/actions=purple,
// logic=blue, terminal=orange, start=gray. Green uses the project primary (#90bb13).
const CAT_GREEN  = '#90bb13'; // content the bot shows
const CAT_PURPLE = '#615fff'; // data actions (tools/attributes/tags)
const CAT_BLUE   = '#2b7fff'; // logic / conditions
const CAT_ORANGE = '#ff6900'; // terminal / routing
const CAT_GRAY   = '#62748e'; // start

const NODE_COLORS: Record<string, string> = {
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
    go_to_step:        CAT_ORANGE,
    transfer:          CAT_ORANGE,
    close:             CAT_ORANGE,
    end:               CAT_ORANGE,
};

const NODE_ICONS: Record<string, string> = {
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
    transfer:          'fas fa-headset',
    close:             'fas fa-circle-xmark',
    end:               'fas fa-flag-checkered',
};

// Terminal node types — they end the branch (no child nodes, addStepNode shown before them).
const TERMINAL_TYPES = new Set(['end', 'transfer', 'close', 'go_to_step']);

const ADDABLE_TYPES = [
    'message', 'rich_message', 'send_file', 'quick_replies', 'ai_response', 'ai_agent', 'collect_input', 'identify_customer',
    'order_lookup', 'request_documents', 'document_link', 'business_hours', 'branches', 'http_request', 'action', 'delay',
    'csat', 'add_tag', 'set_attribute', 'go_to_step', 'transfer', 'close', 'end',
];

const DOC_TYPE_LABELS: Record<string, string> = {
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

const NODE_WIDTH  = 240;
const NODE_HEIGHT = 72;
const V_GAP       = 120;
const H_GAP       = 280;

const edgeStyle = { stroke: '#d1d5db', strokeWidth: 1.5 };

// ─── Helpers ──────────────────────────────────────────────────────────────────

function nanoid(): string {
    return Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
}

function getPreviewText(node: BackendNode): string {
    const d = node.data || {};
    if (node.type === 'request_documents') {
        const types = d.doc_types || [];
        return types.length ? types.map((t: string) => DOC_TYPE_LABELS[t] || t).join(', ').substring(0, 50) : '';
    }
    if (node.type === 'add_tag') return (d.tags || []).join(', ').substring(0, 50);
    if (node.type === 'set_attribute') return d.attribute ? `${d.attribute} = ${d.value || ''}` : '';
    if (node.type === 'go_to_step') return d.target_label ? `→ ${d.target_label}` : '';
    if (node.type === 'transfer') return d.assignee_id ? 'a un agente' : (d.group_id ? 'a un grupo' : 'a la cola general');
    if (node.type === 'close') return d.farewell?.substring(0, 50) || '';
    if (node.type === 'ai_response') return d.use_knowledge_base !== false ? 'RAG · centro de ayuda' : 'LLM';
    if (node.type === 'ai_agent') return 'IA con herramientas';
    if (node.type === 'order_lookup') return `pedido: {{${d.order_variable || 'numero_pedido'}}}`;
    if (node.type === 'http_request') return `${(d.method || 'GET')} ${String(d.url || '').substring(0, 40)}`;
    if (node.type === 'csat') return `escala ${d.scale || '1-5'}`;
    if (node.type === 'business_hours') return `${d.start_time || '09:00'}–${d.end_time || '18:00'}`;
    if (node.type === 'rich_message') return String(d.title || d.image_url || '').substring(0, 50);
    if (node.type === 'send_file') return String(d.caption || d.file_url || '').substring(0, 50);
    return String(d.text || d.question || d.found_message || '').substring(0, 50);
}

// Variables available across the flow: system context + each node's saved variable.
function collectFlowVariables(nodes: BackendNode[]): string[] {
    const vars = new Set<string>([
        'customer_name', 'customer_email', 'customer_nif',
        'order_status', 'order_total', 'order_tracking', 'within_business_hours',
    ]);
    nodes.forEach(n => {
        const d = n.data || {};
        if (d.variable_name) vars.add(d.variable_name);
        if (n.type === 'order_lookup' && d.order_variable) vars.add(d.order_variable);
        if ((n.type === 'http_request' || n.type === 'ai_response') && d.save_to) vars.add(d.save_to);
    });
    return Array.from(vars).sort();
}

// Renders the text a node would send the customer (for the live preview).
function nodeMessageText(node: BackendNode): string | null {
    const d = node.data || {};
    const numbered = (opts: string[]) => opts.map((o, i) => `${i + 1}. ${o}`).join('\n');
    switch (node.type) {
        case 'message': return d.text || '';
        case 'quick_replies':
            return `${d.text || 'Selecciona una opción:'}\n\n${numbered(d.options || [])}\n\nResponde con el número de la opción.`;
        case 'csat':
            return `${d.question || '¿Cómo valorarías nuestra atención?'}\n\n1. ⭐\n2. ⭐⭐\n3. ⭐⭐⭐\n4. ⭐⭐⭐⭐\n5. ⭐⭐⭐⭐⭐\n\nResponde con el número.`;
        case 'collect_input': return d.question || '';
        case 'identify_customer': return d.question || 'Para identificarte, escribe tu email, teléfono o documento.';
        case 'rich_message': {
            const head = [d.title, d.subtitle].filter(Boolean).join('\n');
            const opts = (d.options || []).length ? `\n\n${numbered(d.options)}` : '';
            return (d.image_url ? '🖼️\n' : '') + head + opts;
        }
        case 'ai_response': return '🤖 Respuesta generada por IA' + (d.use_knowledge_base !== false ? ' (centro de ayuda)' : '');
        case 'order_lookup': return '📦 Detalle del pedido del cliente';
        case 'transfer': return d.message || 'Un momento, te transfiero con un agente.';
        case 'close': return d.farewell || '';
        case 'end': return d.farewell || '';
        default: return null;
    }
}

// Highlights {{variables}} inside preview text.
function highlightVars(text: string): React.ReactNode {
    return text.split(/(\{\{\w+\}\})/g).map((part, i) =>
        /^\{\{\w+\}\}$/.test(part)
            ? <span key={i} style={{ background: '#fef08a', borderRadius: 3, padding: '0 2px', color: '#854d0e' }}>{part}</span>
            : <React.Fragment key={i}>{part}</React.Fragment>
    );
}

function migrateNodes(raw: any[]): BackendNode[] {
    if (!raw?.length) {
        return [{ id: 'start', type: 'start', parentId: null, label: 'Inicio', data: {} }];
    }
    const hasOldFormat = raw.some(n => (n.x !== undefined || n.y !== undefined) && n.parentId === undefined);
    if (hasOldFormat) {
        return raw.map(n => ({ id: n.id, type: n.type || 'message', parentId: null, label: n.label || n.type, data: n.config || n.data || {} }));
    }
    return raw.map(n => ({ ...n, data: n.data || {} }));
}

// ─── Validation ───────────────────────────────────────────────────────────────

interface FlowIssue {
    level: 'error' | 'warning';
    message: string;
    nodeId?: string;
}

const VALIDATE_TERMINAL = new Set(['end', 'transfer', 'close', 'go_to_step']);
const VALIDATE_WAIT = new Set(['collect_input', 'quick_replies', 'identify_customer', 'request_documents', 'csat', 'rich_message']);

function validateFlow(nodes: BackendNode[]): { errors: FlowIssue[]; warnings: FlowIssue[]; byNode: Map<string, FlowIssue> } {
    const errors: FlowIssue[] = [];
    const warnings: FlowIssue[] = [];
    const byNode = new Map<string, FlowIssue>();

    const add = (level: 'error' | 'warning', message: string, nodeId?: string) => {
        const issue: FlowIssue = { level, message, nodeId };
        (level === 'error' ? errors : warnings).push(issue);
        // Errors take precedence over warnings when marking a node.
        if (nodeId && (!byNode.has(nodeId) || (level === 'error' && byNode.get(nodeId)!.level === 'warning'))) {
            byNode.set(nodeId, issue);
        }
    };

    if (!nodes.length) {
        add('error', 'El flow no tiene nodos.');
        return { errors, warnings, byNode };
    }

    const ids = nodes.map(n => n.id);
    const labelOf = (n: BackendNode) => n.label || NODE_LABELS[n.type] || n.type;
    const starts = nodes.filter(n => n.type === 'start');

    if (starts.length === 0) add('error', 'El flow no tiene nodo de inicio.');
    else if (starts.length > 1) starts.forEach(s => add('error', 'Hay más de un nodo de inicio.', s.id));

    nodes.forEach(n => {
        if (n.parentId && !ids.includes(n.parentId)) {
            add('error', `«${labelOf(n)}» apunta a un paso que no existe.`, n.id);
        }

        const children = nodes.filter(c => c.parentId === n.id && c.type !== 'branchItem');

        if (!VALIDATE_TERMINAL.has(n.type) && !VALIDATE_WAIT.has(n.type)
            && n.type !== 'branchItem' && n.type !== 'branches' && !children.length) {
            add('warning', `«${labelOf(n)}» no tiene continuación; el flow terminará ahí.`, n.id);
        }

        const d = n.data || {};
        if (n.type === 'message' && !String(d.text || '').trim()) add('warning', `«${labelOf(n)}» no tiene texto.`, n.id);
        if (n.type === 'quick_replies' && !(d.options || []).length) add('warning', `«${labelOf(n)}» no tiene opciones.`, n.id);
        if (n.type === 'collect_input' && !String(d.question || '').trim()) add('warning', `«${labelOf(n)}» no tiene pregunta.`, n.id);
        if (n.type === 'go_to_step' && !d.target_node_id) add('warning', `«${labelOf(n)}» no tiene destino seleccionado.`, n.id);

        if (n.type === 'branches') {
            const items = nodes.filter(c => c.parentId === n.id && c.type === 'branchItem');
            if (!items.some(i => i.data?.isElse)) {
                add('warning', `«${labelOf(n)}» no tiene rama «si no» (else).`, n.id);
            }
        }
    });

    return { errors, warnings, byNode };
}

// ─── Auto Layout ──────────────────────────────────────────────────────────────

function computeLayout(backendNodes: BackendNode[]): { xyNodes: Node[]; xyEdges: Edge[] } {
    const xyNodes: Node[] = [];
    const xyEdges: Edge[] = [];

    const startNode = backendNodes.find(n => n.type === 'start');
    if (!startNode) return { xyNodes, xyEdges };

    const getChildren    = (pid: string) => backendNodes.filter(n => n.parentId === pid && n.type !== 'branchItem');
    const getBranchItems = (pid: string) => backendNodes.filter(n => n.parentId === pid && n.type === 'branchItem');

    const mkFlowNode = (bn: BackendNode, x: number, y: number): Node => ({
        id: bn.id, type: 'flowNode', position: { x, y }, data: { backendNode: bn }, draggable: false,
    });

    const mkAddNode = (id: string, parentId: string, x: number, y: number): Node => ({
        id, type: 'addStepNode', position: { x, y }, data: { parentId }, draggable: false,
    });

    const mkEdge = (id: string, source: string, target: string): Edge => ({
        id, source, target, style: edgeStyle,
    });

    // Place addStepNode BETWEEN parent and end node so users can always insert steps.
    function placeEndWithAdd(parentId: string, endNode: BackendNode, x: number, y: number): number {
        const addId = `add-before-${parentId}`;
        xyNodes.push(mkAddNode(addId, parentId, x, y));
        xyEdges.push(mkEdge(`ea-${parentId}-${addId}`, parentId, addId));
        const endY = y + V_GAP;
        xyNodes.push(mkFlowNode(endNode, x, endY));
        xyEdges.push(mkEdge(`ea-${addId}-${endNode.id}`, addId, endNode.id));
        return endY + V_GAP;
    }

    function placeChain(nodeId: string, x: number, y: number): number {
        const node = backendNodes.find(n => n.id === nodeId);
        if (!node) return y;

        xyNodes.push(mkFlowNode(node, x, y));

        const children = getChildren(node.id);
        const nextY    = y + V_GAP;

        // Terminal nodes end the branch — no addStepNode after them.
        if (TERMINAL_TYPES.has(node.type)) return nextY;

        // Branches: spread branchItems horizontally.
        if (node.type === 'branches') {
            const items      = getBranchItems(node.id);
            const totalWidth = (items.length - 1) * H_GAP;
            const startX     = x - totalWidth / 2;
            let maxY         = nextY;

            items.forEach((item, idx) => {
                const itemX = startX + idx * H_GAP;
                const itemY = nextY;

                xyNodes.push(mkFlowNode(item, itemX, itemY));
                xyEdges.push(mkEdge(`e-${node.id}-${item.id}`, node.id, item.id));

                const itemChildren = getChildren(item.id);
                let colY = itemY + V_GAP;

                if (!itemChildren.length) {
                    xyNodes.push(mkAddNode(`add-${item.id}`, item.id, itemX, colY));
                    colY += V_GAP;
                } else {
                    const firstItemChild = itemChildren[0];
                    if (TERMINAL_TYPES.has(firstItemChild.type)) {
                        colY = placeEndWithAdd(item.id, firstItemChild, itemX, colY);
                    } else {
                        xyEdges.push(mkEdge(`e-${item.id}-${firstItemChild.id}`, item.id, firstItemChild.id));
                        colY = placeChain(firstItemChild.id, itemX, colY);
                    }
                }
                maxY = Math.max(maxY, colY);
            });

            return maxY;
        }

        // No children: show "+" button.
        if (!children.length) {
            xyNodes.push(mkAddNode(`add-${node.id}`, node.id, x, nextY));
            return nextY + V_GAP;
        }

        const firstChild = children[0];

        // Insert addStepNode before any terminal child.
        if (TERMINAL_TYPES.has(firstChild.type)) {
            return placeEndWithAdd(node.id, firstChild, x, nextY);
        }

        xyEdges.push(mkEdge(`e-${node.id}-${firstChild.id}`, node.id, firstChild.id));
        return placeChain(firstChild.id, x, nextY);
    }

    placeChain(startNode.id, 300, 0);
    return { xyNodes, xyEdges };
}

// ─── Custom node: FlowNode ────────────────────────────────────────────────────

const handleStyle = { background: 'transparent', border: 'none', width: 8, height: 8 };

function FlowNode({ data, selected }: NodeProps) {
    const node  = (data as any).backendNode as BackendNode;
    const issue = (data as any).issue as FlowIssue | undefined;
    const color = NODE_COLORS[node.type] || '#64748b';
    const icon  = NODE_ICONS[node.type]  || 'fas fa-circle';
    const label = node.label || NODE_LABELS[node.type] || node.type;
    const [hover, setHover] = useState(false);

    // branchItems are oval pills
    if (node.type === 'branchItem') {
        return (
            <div style={{
                background:    selected ? '#eff6ff' : '#fff',
                border:        selected ? '2px solid #3b82f6' : '1.5px solid #cbd5e1',
                borderRadius:  30,
                padding:       '6px 18px',
                fontSize:      13,
                fontWeight:    500,
                color:         '#374151',
                userSelect:    'none',
                cursor:        'pointer',
                whiteSpace:    'nowrap',
                boxShadow:     '0 1px 3px rgba(0,0,0,.06)',
            }}>
                <Handle type="target" position={Position.Top}    style={handleStyle} />
                {label}
                <Handle type="source" position={Position.Bottom} style={handleStyle} />
            </div>
        );
    }

    // start node: compact box — gray icon square + label (bedesk-style)
    if (node.type === 'start') {
        return (
            <div style={{
                background:   '#fff',
                border:       selected ? '1.5px solid #cbd5e1' : '1.5px solid #e2e8f0',
                borderRadius: 12,
                padding:      10,
                width:        NODE_WIDTH,
                display:      'flex',
                alignItems:   'center',
                gap:          10,
                userSelect:   'none',
                cursor:       'pointer',
                boxShadow:    selected ? '0 0 0 3px rgba(98,116,142,.18)' : '0 1px 4px rgba(0,0,0,.08)',
            }}>
                <Handle type="target" position={Position.Top}    style={handleStyle} />
                <div style={{ width: 34, height: 34, borderRadius: 8, background: CAT_GRAY, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <i className="fas fa-play" style={{ color: '#fff', fontSize: 13 }} />
                </div>
                <span style={{ fontSize: 13, fontWeight: 600, color: '#1e293b' }}>{label}</span>
                <Handle type="source" position={Position.Bottom} style={handleStyle} />
            </div>
        );
    }

    // All other nodes — bedesk-style: content on top, footer with a colored
    // icon square + the type name in muted gray, separated by a top border.
    const issueColor = issue?.level === 'error' ? '#ef4444' : '#f59e0b';
    const border = issue
        ? `1.5px solid ${issueColor}`
        : (selected ? '1.5px solid #cbd5e1' : '1.5px solid #e2e8f0');

    return (
        <div
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                background:   '#fff',
                border,
                borderRadius: 12,
                width:        NODE_WIDTH,
                boxShadow:    selected ? '0 0 0 3px rgba(98,116,142,.18)' : '0 1px 4px rgba(0,0,0,.08)',
                cursor:       'pointer',
                userSelect:   'none',
                position:     'relative',
                display:      'flex',
                flexDirection: 'column',
                overflow:     'hidden',
            }}>
            <Handle type="target" position={Position.Top}    style={handleStyle} />

            {/* Issue badge */}
            {issue && (
                <div title={issue.message} style={{
                    position: 'absolute', top: -8, right: -8, width: 20, height: 20, borderRadius: '50%',
                    background: issueColor, color: '#fff', fontSize: 11, zIndex: 2,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    boxShadow: '0 1px 3px rgba(0,0,0,.2)',
                }}>
                    <i className="fas fa-exclamation" />
                </div>
            )}

            {/* Hover actions: duplicate / delete */}
            {hover && (
                <div style={{ position: 'absolute', top: -12, left: '50%', transform: 'translateX(-50%)', display: 'flex', gap: 4, zIndex: 2 }}>
                    <button title="Duplicar" onClick={e => { e.stopPropagation(); window.__chatflowDuplicateNode?.(node.id); }}
                        style={nodeActionBtnStyle}>
                        <i className="fas fa-copy" />
                    </button>
                    <button title="Eliminar" onClick={e => { e.stopPropagation(); window.__chatflowDeleteNode?.(node.id); }}
                        style={nodeActionBtnStyle}>
                        <i className="fas fa-trash" />
                    </button>
                </div>
            )}

            {/* Content body */}
            <div style={{
                padding: '11px 12px', fontSize: 13, color: '#1e293b', fontWeight: 500,
                display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical',
                overflow: 'hidden', wordBreak: 'break-word', lineHeight: 1.35, minHeight: 20,
            }}>
                {label}
            </div>

            {/* Footer: colored icon square + type name */}
            <div style={{ display: 'flex', alignItems: 'center', height: 30, borderTop: '1px solid #f1f5f9' }}>
                <div style={{ width: 30, height: '100%', background: color, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <i className={icon} style={{ color: '#fff', fontSize: 11 }} />
                </div>
                <span style={{ marginLeft: 8, fontSize: 11.5, color: '#94a3b8', fontWeight: 500 }}>
                    {NODE_LABELS[node.type] || node.type}
                </span>
            </div>

            <Handle type="source" position={Position.Bottom} style={handleStyle} />
        </div>
    );
}

const nodeActionBtnStyle: React.CSSProperties = {
    width: 24, height: 24, borderRadius: 6, border: '1px solid #e2e8f0',
    background: '#fff', color: '#64748b', fontSize: 11, cursor: 'pointer',
    display: 'flex', alignItems: 'center', justifyContent: 'center',
    boxShadow: '0 1px 3px rgba(0,0,0,.12)',
};

// ─── Custom node: AddStepNode ─────────────────────────────────────────────────

function AddStepNode({ data }: NodeProps) {
    const parentId = (data as any).parentId as string;
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        const handler = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as HTMLElement)) setOpen(false);
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [open]);

    const handleAdd = (type: string) => {
        setOpen(false);
        window.__chatflowAddNode?.(type, parentId);
    };

    return (
        <div ref={ref} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', position: 'relative' }}>
            <Handle type="target" position={Position.Top}    style={handleStyle} />
            <button
                onClick={e => { e.stopPropagation(); setOpen(o => !o); }}
                style={{
                    width: 28, height: 28, borderRadius: '50%',
                    border: '2px solid #90bb13',
                    background: open ? '#90bb13' : '#fff',
                    color:      open ? '#fff'    : '#90bb13',
                    fontSize: 18, lineHeight: 1, cursor: 'pointer',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    boxShadow: 'none',
                    transition: 'background .15s, color .15s',
                    padding: 0,
                }}
                title="Agregar paso"
            >
                +
            </button>
            <Handle type="source" position={Position.Bottom} style={handleStyle} />

            {open && (
                <div style={{
                    position: 'absolute', top: 38, left: '50%', transform: 'translateX(-50%)',
                    background: '#fff', border: '1px solid #e2e8f0', borderRadius: 10,
                    boxShadow: '0 8px 24px rgba(0,0,0,.14)', zIndex: 9999,
                    minWidth: 210, padding: '6px 0',
                }}>
                    <div style={{ padding: '4px 12px 6px', fontSize: 11, fontWeight: 700, color: '#94a3b8', letterSpacing: '.06em', textTransform: 'uppercase' }}>
                        Agregar paso
                    </div>
                    {ADDABLE_TYPES.map(t => (
                        <button
                            key={t}
                            onClick={() => handleAdd(t)}
                            style={{
                                display: 'flex', alignItems: 'center', gap: 10,
                                width: '100%', padding: '7px 12px',
                                background: 'none', border: 'none',
                                cursor: 'pointer', textAlign: 'left',
                                fontSize: 13, color: '#334155',
                            }}
                            onMouseEnter={e => (e.currentTarget.style.background = '#f8fafc')}
                            onMouseLeave={e => (e.currentTarget.style.background = 'none')}
                        >
                            <span style={{ width: 24, height: 24, borderRadius: 6, background: NODE_COLORS[t], display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                <i className={NODE_ICONS[t]} style={{ color: '#fff', fontSize: 11 }} />
                            </span>
                            {NODE_LABELS[t]}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

const nodeTypes = { flowNode: FlowNode, addStepNode: AddStepNode };

// ─── Properties Panel ─────────────────────────────────────────────────────────

interface PropsPanelProps {
    node:     BackendNode;
    allNodes: BackendNode[];
    agents:   AssignOption[];
    groups:   AssignOption[];
    onUpdate: (updated: BackendNode) => void;
    onDelete: (id: string) => void;
    onClose:  () => void;
}

function NodePropertiesPanel({ node, allNodes, agents, groups, onUpdate, onDelete, onClose }: PropsPanelProps) {
    const [draft, setDraft] = useState<BackendNode>(() => structuredClone(node));

    useEffect(() => { setDraft(structuredClone(node)); }, [node.id]);

    const setData = (patch: Record<string, any>) =>
        setDraft(d => ({ ...d, data: { ...d.data, ...patch } }));

    const color = NODE_COLORS[node.type] || '#64748b';
    const icon  = NODE_ICONS[node.type]  || 'fas fa-circle';

    return (
        <div style={{ width: 340, height: '100%', background: '#fff', borderLeft: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', fontFamily: 'inherit' }}>

            {/* Header */}
            <div style={{ padding: '14px 16px', borderBottom: '1px solid #e2e8f0', display: 'flex', alignItems: 'center', gap: 10 }}>
                <div style={{ width: 32, height: 32, borderRadius: 8, background: color, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                    <i className={icon} style={{ color: '#fff', fontSize: 13 }} />
                </div>
                <div style={{ flex: 1, fontWeight: 600, fontSize: 14, color: '#1e293b' }}>
                    {NODE_LABELS[node.type] || node.type}
                </div>
                <button onClick={onClose} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', fontSize: 16, padding: 4 }}>
                    <i className="fas fa-times" />
                </button>
            </div>

            {/* Body */}
            <div style={{ flex: 1, overflowY: 'auto', padding: 16 }}>
                <div style={{ marginBottom: 14 }}>
                    <label style={labelStyle}>Nombre del paso</label>
                    <input
                        type="text"
                        className="form-control form-control-sm"
                        value={draft.label}
                        onChange={e => setDraft(d => ({ ...d, label: e.target.value }))}
                    />
                </div>
                <TypeFields draft={draft} allNodes={allNodes} agents={agents} groups={groups} setData={setData} setDraft={setDraft} />

                {/* Live preview */}
                {nodeMessageText(draft) !== null && String(nodeMessageText(draft)).trim() !== '' && (
                    <div style={{ marginTop: 18 }}>
                        <label style={labelStyle}>Vista previa</label>
                        <div style={{ background: '#e6ddd4', borderRadius: 10, padding: 10 }}>
                            <div style={{
                                background: '#d9fdd3', borderRadius: 8, padding: '8px 10px', fontSize: 12.5,
                                color: '#111827', whiteSpace: 'pre-wrap', wordBreak: 'break-word',
                                boxShadow: '0 1px 1px rgba(0,0,0,.08)', maxWidth: '90%',
                            }}>
                                {highlightVars(String(nodeMessageText(draft)))}
                            </div>
                        </div>
                        <p style={hintStyle}>Así se ve en un canal de texto (WhatsApp, Instagram…). En web el cliente lo recibe igual.</p>
                    </div>
                )}

                {/* Available variables */}
                <div style={{ marginTop: 18 }}>
                    <label style={labelStyle}>Variables disponibles</label>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                        {collectFlowVariables(allNodes).map(v => (
                            <button key={v} type="button" title="Copiar"
                                onClick={() => {
                                    navigator.clipboard?.writeText(`{{${v}}}`);
                                    (window as any).toastr?.info(`{{${v}}} copiado`, '', { timeOut: 1000 });
                                }}
                                style={{
                                    border: '1px solid #e2e8f0', borderRadius: 20, padding: '2px 9px',
                                    background: '#f8fafc', color: '#475569', fontSize: 11, cursor: 'pointer',
                                    fontFamily: 'monospace',
                                }}>
                                {`{{${v}}}`}
                            </button>
                        ))}
                    </div>
                    <p style={hintStyle}>Haz clic para copiar y pégala en cualquier campo de texto.</p>
                </div>
            </div>

            {/* Footer */}
            <div style={{ padding: '12px 16px', borderTop: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', gap: 8 }}>
                <button
                    onClick={() => onUpdate(draft)}
                    style={{ background: '#90bb13', color: '#fff', border: 'none', borderRadius: 6, padding: '8px 0', fontWeight: 600, cursor: 'pointer', width: '100%', fontSize: 13 }}
                >
                    Guardar
                </button>
                <button onClick={onClose} style={{ background: '#f1f5f9', color: '#475569', border: 'none', borderRadius: 6, padding: '8px 0', cursor: 'pointer', width: '100%', fontSize: 13 }}>
                    Cancelar
                </button>
                {node.type !== 'start' && (
                    <button
                        onClick={() => { if (confirm('¿Eliminar este nodo y todos sus pasos siguientes?')) onDelete(node.id); }}
                        style={{ background: 'none', color: '#ef4444', border: '1px solid #fecaca', borderRadius: 6, padding: '6px 0', cursor: 'pointer', width: '100%', fontSize: 12 }}
                    >
                        <i className="fas fa-trash me-1" /> Eliminar nodo
                    </button>
                )}
            </div>
        </div>
    );
}

const labelStyle: React.CSSProperties = {
    display: 'block', fontSize: 12, fontWeight: 600, color: '#64748b',
    marginBottom: 4, textTransform: 'uppercase', letterSpacing: '.04em',
};
const hintStyle: React.CSSProperties = { fontSize: 11, color: '#94a3b8', marginTop: 3 };

interface TypeFieldsProps {
    draft:    BackendNode;
    allNodes: BackendNode[];
    agents:   AssignOption[];
    groups:   AssignOption[];
    setData:  (patch: Record<string, any>) => void;
    setDraft: React.Dispatch<React.SetStateAction<BackendNode>>;
}

function TypeFields({ draft, allNodes, agents, groups, setData }: TypeFieldsProps) {
    const d = draft.data || {};

    switch (draft.type) {

        case 'start':
            return <p style={{ color: '#94a3b8', fontSize: 13 }}>Nodo de inicio del flow. Sin configuración adicional.</p>;

        case 'message':
            return (
                <div>
                    <label style={labelStyle}>Texto del mensaje</label>
                    <textarea className="form-control form-control-sm" rows={5}
                        value={d.text || ''}
                        onChange={e => setData({ text: e.target.value })} />
                    <p style={hintStyle}>Usa {'{{variable}}'} para interpolar variables del contexto.</p>

                    <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 8 }}>
                        <label style={labelStyle}>Plantilla WhatsApp (envío proactivo)</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="nombre_plantilla_aprobada"
                            value={d.whatsapp_template || ''}
                            onChange={e => setData({ whatsapp_template: e.target.value })} />
                        {!!d.whatsapp_template && (
                            <div style={{ marginTop: 8 }}>
                                <label style={labelStyle}>Variables de la plantilla</label>
                                {(d.template_vars || []).map((v: string, i: number) => (
                                    <div key={i} className="input-group input-group-sm mb-1">
                                        <span className="input-group-text">{`{{${i + 1}}}`}</span>
                                        <input type="text" className="form-control" value={v}
                                            placeholder="texto o {{variable}}"
                                            onChange={e => {
                                                const vars = [...(d.template_vars || [])];
                                                vars[i] = e.target.value;
                                                setData({ template_vars: vars });
                                            }} />
                                        <button className="btn btn-outline-secondary"
                                            onClick={() => setData({ template_vars: (d.template_vars || []).filter((_: any, j: number) => j !== i) })}>
                                            <i className="fas fa-times" />
                                        </button>
                                    </div>
                                ))}
                                <button className="btn btn-sm btn-outline-primary mt-1"
                                    onClick={() => setData({ template_vars: [...(d.template_vars || []), ''] })}>
                                    <i className="fas fa-plus me-1" />Variable
                                </button>
                            </div>
                        )}
                        <p style={hintStyle}>Solo para WhatsApp fuera de la ventana de 24h (recordatorios, seguimientos). Dentro de la ventana se envía el texto normal.</p>
                    </div>
                </div>
            );

        case 'send_file':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>URL del archivo</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="https://.../factura.pdf"
                            value={d.file_url || ''}
                            onChange={e => setData({ file_url: e.target.value })} />
                        <p style={hintStyle}>Acepta {'{{variable}}'} (ej. una URL de factura del contexto).</p>
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Tipo de archivo</label>
                        <select className="form-select form-select-sm"
                            value={d.file_type || 'document'}
                            onChange={e => setData({ file_type: e.target.value })}>
                            <option value="document">Documento (PDF, etc.)</option>
                            <option value="image">Imagen</option>
                            <option value="video">Vídeo</option>
                        </select>
                    </div>
                    <label style={labelStyle}>Texto (opcional)</label>
                    <textarea className="form-control form-control-sm" rows={2}
                        placeholder="Mensaje que acompaña al archivo"
                        value={d.caption || ''}
                        onChange={e => setData({ caption: e.target.value })} />
                    <p style={hintStyle}>Se envía como adjunto nativo en WhatsApp/Messenger/Instagram.</p>
                </div>
            );

        case 'document_link':
            return (
                <div>
                    <p style={{ ...hintStyle, marginBottom: 8 }}>
                        Resuelve el expediente de documentos de la conversación (módulo HelpdeskDocument) y deja
                        disponibles estas variables para los mensajes siguientes:
                    </p>
                    <ul style={{ ...hintStyle, paddingLeft: 18, marginBottom: 8 }}>
                        <li><code>{'{{doc_upload_url}}'}</code> — enlace seguro del portal para subir/consultar.</li>
                        <li><code>{'{{doc_missing}}'}</code> — documentos que faltan por entregar.</li>
                    </ul>
                    <p style={hintStyle}>Coloca este nodo antes de un mensaje que use esas variables.</p>
                </div>
            );

        case 'quick_replies':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Pregunta</label>
                        <textarea className="form-control form-control-sm" rows={3}
                            value={d.text || ''}
                            onChange={e => setData({ text: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Opciones</label>
                    {(d.options || []).map((opt: string, i: number) => (
                        <div key={i} className="input-group input-group-sm mb-1">
                            <input type="text" className="form-control" value={opt}
                                onChange={e => {
                                    const opts = [...(d.options || [])];
                                    opts[i] = e.target.value;
                                    setData({ options: opts });
                                }} />
                            <button className="btn btn-outline-secondary"
                                onClick={() => setData({ options: (d.options || []).filter((_: any, j: number) => j !== i) })}>
                                <i className="fas fa-times" />
                            </button>
                        </div>
                    ))}
                    <button className="btn btn-sm btn-outline-primary mt-1"
                        onClick={() => setData({ options: [...(d.options || []), ''] })}>
                        <i className="fas fa-plus me-1" />Agregar opción
                    </button>
                    <div style={{ marginTop: 14 }}>
                        <label style={labelStyle}>Guardar respuesta en variable</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="ej: opcion_elegida"
                            value={d.variable_name || ''}
                            onChange={e => setData({ variable_name: e.target.value })} />
                    </div>
                    <div className="form-check form-switch mt-3">
                        <input className="form-check-input" type="checkbox" id="qr-nlu"
                            checked={!!d.use_nlu}
                            onChange={e => setData({ use_nlu: e.target.checked })} />
                        <label className="form-check-label" htmlFor="qr-nlu" style={{ fontSize: 13 }}>
                            Entender lenguaje natural (IA)
                        </label>
                    </div>
                    <p style={hintStyle}>Si el cliente no responde con un número, la IA interpreta su intención y elige la opción más cercana.</p>
                </div>
            );

        case 'collect_input':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Pregunta</label>
                        <textarea className="form-control form-control-sm" rows={3}
                            value={d.question || ''}
                            onChange={e => setData({ question: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Guardar en variable</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="ej: nombre_cliente"
                            value={d.variable_name || ''}
                            onChange={e => setData({ variable_name: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Validar como</label>
                        <select className="form-select form-select-sm"
                            value={d.validation || 'none'}
                            onChange={e => setData({ validation: e.target.value })}>
                            <option value="none">Sin validación</option>
                            <option value="email">Email</option>
                            <option value="phone">Teléfono</option>
                            <option value="number">Número</option>
                        </select>
                        <p style={hintStyle}>Si no valida, el bot vuelve a preguntar (hasta {d.max_retries || 3} veces, luego transfiere).</p>
                    </div>

                    <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 4 }}>
                        <label style={labelStyle}>Si el cliente no responde…</label>
                        <div className="d-flex gap-2 align-items-center mb-2">
                            <input type="number" min={0} className="form-control form-control-sm" style={{ maxWidth: 90 }}
                                placeholder="min"
                                value={d.timeout_minutes || ''}
                                onChange={e => setData({ timeout_minutes: e.target.value ? parseInt(e.target.value) : null })} />
                            <span style={hintStyle}>minutos de espera</span>
                        </div>
                        {!!d.timeout_minutes && (
                            <>
                                <select className="form-select form-select-sm mb-2"
                                    value={d.timeout_action || 'close'}
                                    onChange={e => setData({ timeout_action: e.target.value })}>
                                    <option value="close">Cerrar la conversación</option>
                                    <option value="retry">Volver a preguntar</option>
                                    <option value="transfer">Pasar a un agente</option>
                                </select>
                                <textarea className="form-control form-control-sm" rows={2}
                                    placeholder="Mensaje al agotarse el tiempo (opcional). Ej: ¿Sigues ahí? 👋"
                                    value={d.timeout_message || ''}
                                    onChange={e => setData({ timeout_message: e.target.value })} />
                                {d.timeout_action === 'retry' && (
                                    <div className="d-flex gap-2 align-items-center mt-2">
                                        <input type="number" min={1} max={5} className="form-control form-control-sm" style={{ maxWidth: 90 }}
                                            value={d.timeout_retries || 1}
                                            onChange={e => setData({ timeout_retries: e.target.value ? parseInt(e.target.value) : 1 })} />
                                        <span style={hintStyle}>reintentos antes de cerrar</span>
                                    </div>
                                )}
                            </>
                        )}
                        <p style={hintStyle}>Útil para evitar conversaciones colgadas a mitad del bot.</p>
                    </div>
                </div>
            );

        case 'identify_customer': {
            const sources = d.sources || ['erp'];
            const toggleSrc = (src: string) => {
                const next = sources.includes(src)
                    ? sources.filter((s: string) => s !== src)
                    : [...sources, src];
                setData({ sources: next });
            };
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Pregunta al cliente</label>
                        <textarea className="form-control form-control-sm" rows={3}
                            value={d.question || 'Para identificarte, escribe tu email, teléfono o documento.'}
                            onChange={e => setData({ question: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Fuentes de verificación</label>
                    {[['erp', 'ERP'], ['ps', 'PrestaShop']].map(([v, l]) => (
                        <div key={v} className="form-check mb-1">
                            <input className="form-check-input" type="checkbox" id={`src-${v}`}
                                checked={sources.includes(v)} onChange={() => toggleSrc(v)} />
                            <label className="form-check-label" htmlFor={`src-${v}`}>{l}</label>
                        </div>
                    ))}
                    <div style={{ marginTop: 10, marginBottom: 8 }}>
                        <label style={labelStyle}>Mensaje si se identifica</label>
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.found_message || '¡Perfecto, {{customer_name}}!'}
                            onChange={e => setData({ found_message: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 8 }}>
                        <label style={labelStyle}>Mensaje si no se encuentra</label>
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.not_found_message || 'No encontramos tu dato. Intenta de nuevo.'}
                            onChange={e => setData({ not_found_message: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 8 }}>
                        <label style={labelStyle}>Intentos máximos</label>
                        <input type="number" className="form-control form-control-sm" min={1} max={10}
                            value={d.max_attempts ?? 3}
                            onChange={e => setData({ max_attempts: parseInt(e.target.value) || 3 })} />
                    </div>
                    <div className="form-check">
                        <input className="form-check-input" type="checkbox" id="transfer-failure"
                            checked={!!d.transfer_on_failure}
                            onChange={e => setData({ transfer_on_failure: e.target.checked })} />
                        <label className="form-check-label" htmlFor="transfer-failure">Transferir a agente si agota intentos</label>
                    </div>
                </div>
            );
        }

        case 'request_documents': {
            const selected = d.doc_types || [];
            const toggle   = (key: string) => {
                const next = selected.includes(key)
                    ? selected.filter((t: string) => t !== key)
                    : [...selected, key];
                setData({ doc_types: next });
            };
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Mensaje de solicitud</label>
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.text || 'Por favor sube los siguientes documentos:'}
                            onChange={e => setData({ text: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Documentos requeridos</label>
                    {Object.entries(DOC_TYPE_LABELS).map(([key, lbl]) => (
                        <div key={key} className="form-check mb-1">
                            <input className="form-check-input" type="checkbox" id={`doc-${key}`}
                                checked={selected.includes(key)} onChange={() => toggle(key)} />
                            <label className="form-check-label" htmlFor={`doc-${key}`}>{lbl}</label>
                        </div>
                    ))}
                    <div style={{ marginTop: 10 }}>
                        <label style={labelStyle}>Guardar lista en variable</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="ej: documentos_subidos"
                            value={d.variable_name || 'uploaded_docs'}
                            onChange={e => setData({ variable_name: e.target.value })} />
                    </div>
                </div>
            );
        }

        case 'branches': {
            const items = allNodes.filter(n => n.parentId === draft.id && n.type === 'branchItem');
            return (
                <div>
                    <label style={labelStyle}>Ramas de condición</label>
                    {items.map(item => (
                        <div key={item.id} style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 6 }}>
                            <input type="text" className="form-control form-control-sm"
                                defaultValue={item.label}
                                onBlur={e => window.__chatflowUpdateBranchName?.(item.id, e.target.value)} />
                            {item.data?.isElse
                                ? <span className="badge bg-secondary flex-shrink-0">else</span>
                                : <button style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: 2, flexShrink: 0 }}
                                    onClick={() => { if (confirm('¿Eliminar esta rama?')) window.__chatflowDeleteNode?.(item.id); }}>
                                    <i className="fas fa-times" />
                                </button>
                            }
                        </div>
                    ))}
                    <button className="btn btn-sm btn-outline-primary mt-1"
                        onClick={() => window.__chatflowAddBranch?.(draft.id)}>
                        <i className="fas fa-plus me-1" />Agregar rama
                    </button>
                </div>
            );
        }

        case 'branchItem': {
            if (d.isElse) {
                return <div className="alert alert-secondary p-2" style={{ fontSize: 12 }}>Rama por defecto (Else). Se ejecuta si ninguna otra condición coincide.</div>;
            }
            const conditions: Condition[] = d.conditions || [];
            const updateCond = (i: number, patch: Partial<Condition>) => {
                setData({ conditions: conditions.map((c, idx) => idx === i ? { ...c, ...patch } : c) });
            };
            return (
                <div>
                    <label style={labelStyle}>Condiciones (todas deben cumplirse)</label>
                    {conditions.map((c, i) => (
                        <div key={i} style={{ display: 'grid', gridTemplateColumns: '1fr 80px 1fr 24px', gap: 4, marginBottom: 6 }}>
                            <input type="text" className="form-control form-control-sm" placeholder="variable"
                                value={c.variable} onChange={e => updateCond(i, { variable: e.target.value })} />
                            <select className="form-select form-select-sm"
                                value={c.operator} onChange={e => updateCond(i, { operator: e.target.value })}>
                                {['=', '!=', '>', '<', 'contains'].map(op => <option key={op}>{op}</option>)}
                            </select>
                            <input type="text" className="form-control form-control-sm" placeholder="valor"
                                value={c.value} onChange={e => updateCond(i, { value: e.target.value })} />
                            <button style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#94a3b8', padding: '0 2px' }}
                                onClick={() => setData({ conditions: conditions.filter((_, j) => j !== i) })}>
                                <i className="fas fa-times" />
                            </button>
                        </div>
                    ))}
                    <button className="btn btn-sm btn-outline-primary mt-1"
                        onClick={() => setData({ conditions: [...conditions, { variable: '', operator: '=', value: '' }] })}>
                        <i className="fas fa-plus me-1" />Agregar condición
                    </button>
                </div>
            );
        }

        case 'action':
            return (
                <div>
                    <label style={labelStyle}>Acción a ejecutar</label>
                    <select className="form-select form-select-sm"
                        value={d.action_type || 'assign_agent'}
                        onChange={e => setData({ action_type: e.target.value })}>
                        <option value="assign_agent">Asignar agente</option>
                        <option value="change_status">Cambiar estado</option>
                        <option value="add_tag">Agregar etiqueta</option>
                    </select>
                </div>
            );

        case 'delay':
            return (
                <div>
                    <label style={labelStyle}>Segundos de espera</label>
                    <input type="number" className="form-control form-control-sm" min={1} max={300}
                        value={d.seconds ?? 5}
                        onChange={e => setData({ seconds: parseInt(e.target.value) || 5 })} />
                    <p style={hintStyle}>Entre 1 y 300 segundos.</p>
                </div>
            );

        case 'ai_response':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Instrucciones para la IA</label>
                        <textarea className="form-control form-control-sm" rows={3}
                            value={d.instructions || ''}
                            placeholder="Eres un asistente de atención al cliente. Responde breve y amable en español."
                            onChange={e => setData({ instructions: e.target.value })} />
                    </div>
                    <div className="form-check form-switch mb-2">
                        <input className="form-check-input" type="checkbox" id="ai-kb"
                            checked={d.use_knowledge_base !== false}
                            onChange={e => setData({ use_knowledge_base: e.target.checked })} />
                        <label className="form-check-label" htmlFor="ai-kb" style={{ fontSize: 13 }}>
                            Usar centro de conocimiento (RAG)
                        </label>
                    </div>
                    <div className="form-check form-switch mb-2">
                        <input className="form-check-input" type="checkbox" id="ai-memory"
                            checked={d.use_memory !== false}
                            onChange={e => setData({ use_memory: e.target.checked })} />
                        <label className="form-check-label" htmlFor="ai-memory" style={{ fontSize: 13 }}>
                            Recordar la conversación (memoria)
                        </label>
                    </div>
                    {d.use_knowledge_base !== false && (
                        <div style={{ marginBottom: 12 }}>
                            <label style={labelStyle}>Artículos a recuperar</label>
                            <input type="number" className="form-control form-control-sm" min={1} max={10}
                                value={d.kb_results ?? 4}
                                onChange={e => setData({ kb_results: parseInt(e.target.value) || 4 })} />
                        </div>
                    )}
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Variable con la pregunta</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="last_input"
                            value={d.question_variable || ''}
                            onChange={e => setData({ question_variable: e.target.value })} />
                        <p style={hintStyle}>De qué variable del contexto sale la pregunta del cliente.</p>
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Guardar respuesta en (opcional)</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="ej: respuesta_ia"
                            value={d.save_to || ''}
                            onChange={e => setData({ save_to: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Mensaje de respaldo</label>
                    <textarea className="form-control form-control-sm" rows={2}
                        value={d.fallback_message || ''}
                        placeholder="Si la IA no puede responder, te paso con un agente."
                        onChange={e => setData({ fallback_message: e.target.value })} />
                </div>
            );

        case 'ai_agent':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Instrucciones del agente</label>
                        <textarea className="form-control form-control-sm" rows={3}
                            value={d.instructions || ''}
                            placeholder="Eres un agente de atención. Usa las herramientas cuando ayuden a resolver."
                            onChange={e => setData({ instructions: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Herramientas disponibles</label>
                    <div className="form-check form-switch mb-1">
                        <input className="form-check-input" type="checkbox" id="ag-order"
                            checked={d.tool_order_lookup !== false}
                            onChange={e => setData({ tool_order_lookup: e.target.checked })} />
                        <label className="form-check-label" htmlFor="ag-order" style={{ fontSize: 13 }}>Consultar pedido (ERP/PrestaShop)</label>
                    </div>
                    <div className="form-check form-switch mb-2">
                        <input className="form-check-input" type="checkbox" id="ag-kb"
                            checked={d.tool_knowledge !== false}
                            onChange={e => setData({ tool_knowledge: e.target.checked })} />
                        <label className="form-check-label" htmlFor="ag-kb" style={{ fontSize: 13 }}>Buscar en el centro de ayuda</label>
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Variable con la pregunta</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="last_input"
                            value={d.question_variable || ''}
                            onChange={e => setData({ question_variable: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Mensaje de respaldo</label>
                    <textarea className="form-control form-control-sm" rows={2}
                        value={d.fallback_message || ''}
                        placeholder="Si no puede resolver, te paso con un agente."
                        onChange={e => setData({ fallback_message: e.target.value })} />
                    <p style={hintStyle}>El agente decide solo qué herramienta usar (consultar pedido, buscar ayuda), responde o escala a un humano.</p>
                </div>
            );

        case 'order_lookup':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Variable con el nº de pedido</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="numero_pedido"
                            value={d.order_variable || ''}
                            onChange={e => setData({ order_variable: e.target.value })} />
                        <p style={hintStyle}>El cliente debe haberse identificado y dado el nº de pedido antes.</p>
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Fuente</label>
                        <select className="form-select form-select-sm"
                            value={d.source || 'auto'}
                            onChange={e => setData({ source: e.target.value })}>
                            <option value="auto">Automática (ERP y PrestaShop)</option>
                            <option value="erp">Solo ERP</option>
                            <option value="ps">Solo PrestaShop</option>
                        </select>
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Mensaje si se encuentra</label>
                        <textarea className="form-control form-control-sm" rows={3}
                            value={d.found_message || ''}
                            placeholder="Déjalo vacío para usar el formato automático. Variables: {{order_status}}, {{order_total}}, {{order_tracking}}"
                            onChange={e => setData({ found_message: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Mensaje si NO se encuentra</label>
                    <textarea className="form-control form-control-sm" rows={2}
                        value={d.not_found_message || ''}
                        placeholder="No he encontrado ese pedido asociado a tu cuenta."
                        onChange={e => setData({ not_found_message: e.target.value })} />
                    <p style={hintStyle}>Guarda en contexto: order_found, order_status, order_total, order_tracking.</p>
                </div>
            );

        case 'http_request':
            return (
                <div>
                    <div className="d-flex gap-2 mb-2">
                        <select className="form-select form-select-sm" style={{ maxWidth: 110 }}
                            value={d.method || 'GET'}
                            onChange={e => setData({ method: e.target.value })}>
                            <option>GET</option>
                            <option>POST</option>
                            <option>PUT</option>
                            <option>PATCH</option>
                            <option>DELETE</option>
                        </select>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="https://api.ejemplo.com/recurso"
                            value={d.url || ''}
                            onChange={e => setData({ url: e.target.value })} />
                    </div>
                    <p style={hintStyle}>Puedes usar {'{{variables}}'} en la URL, headers y body.</p>

                    <label style={{ ...labelStyle, marginTop: 10 }}>Cabeceras</label>
                    {(d.headers || []).map((h: any, i: number) => (
                        <div key={i} className="input-group input-group-sm mb-1">
                            <input type="text" className="form-control" placeholder="Nombre" value={h.key || ''}
                                onChange={e => {
                                    const headers = [...(d.headers || [])];
                                    headers[i] = { ...headers[i], key: e.target.value };
                                    setData({ headers });
                                }} />
                            <input type="text" className="form-control" placeholder="Valor" value={h.value || ''}
                                onChange={e => {
                                    const headers = [...(d.headers || [])];
                                    headers[i] = { ...headers[i], value: e.target.value };
                                    setData({ headers });
                                }} />
                            <button className="btn btn-outline-secondary"
                                onClick={() => setData({ headers: (d.headers || []).filter((_: any, j: number) => j !== i) })}>
                                <i className="fas fa-times" />
                            </button>
                        </div>
                    ))}
                    <button className="btn btn-sm btn-outline-primary mb-2"
                        onClick={() => setData({ headers: [...(d.headers || []), { key: '', value: '' }] })}>
                        <i className="fas fa-plus me-1" />Cabecera
                    </button>

                    {['POST', 'PUT', 'PATCH'].includes(d.method || 'GET') && (
                        <div style={{ marginBottom: 10 }}>
                            <label style={labelStyle}>Body (JSON)</label>
                            <textarea className="form-control form-control-sm" rows={3}
                                style={{ fontFamily: 'monospace', fontSize: 12 }}
                                value={typeof d.body === 'string' ? d.body : ''}
                                placeholder='{"email": "{{customer_email}}"}'
                                onChange={e => setData({ body: e.target.value })} />
                        </div>
                    )}

                    <div style={{ marginBottom: 10 }}>
                        <label style={labelStyle}>Extraer del JSON (ruta)</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="ej: data.estado o results.0.id"
                            value={d.response_path || ''}
                            onChange={e => setData({ response_path: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 10 }}>
                        <label style={labelStyle}>Guardar en variable</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="http_response"
                            value={d.save_to || ''}
                            onChange={e => setData({ save_to: e.target.value })} />
                        <p style={hintStyle}>También guarda {'{{save_to}}_ok'} y {'{{save_to}}_status'}.</p>
                    </div>
                    <div className="form-check form-switch mb-2">
                        <input className="form-check-input" type="checkbox" id="http-msg"
                            checked={!!d.show_message}
                            onChange={e => setData({ show_message: e.target.checked })} />
                        <label className="form-check-label" htmlFor="http-msg" style={{ fontSize: 13 }}>
                            Enviar un mensaje con el resultado
                        </label>
                    </div>
                    {d.show_message && (
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.message_template || ''}
                            placeholder="Tu pedido está: {{http_response}}"
                            onChange={e => setData({ message_template: e.target.value })} />
                    )}
                </div>
            );

        case 'csat':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Pregunta de valoración</label>
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.question || ''}
                            placeholder="¿Cómo valorarías nuestra atención?"
                            onChange={e => setData({ question: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Escala</label>
                        <select className="form-select form-select-sm"
                            value={d.scale || '1-5'}
                            onChange={e => setData({ scale: e.target.value })}>
                            <option value="1-5">Estrellas 1 a 5</option>
                            <option value="1-10">Numérica 1 a 10</option>
                            <option value="thumbs">Pulgar arriba / abajo</option>
                        </select>
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Mensaje de agradecimiento</label>
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.thanks_message || ''}
                            placeholder="¡Gracias por tu valoración!"
                            onChange={e => setData({ thanks_message: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Guardar nota en</label>
                    <input type="text" className="form-control form-control-sm"
                        placeholder="csat_score"
                        value={d.variable_name || ''}
                        onChange={e => setData({ variable_name: e.target.value })} />
                    <p style={hintStyle}>El cliente responde con el número. Funciona en todos los canales (WhatsApp, Messenger, Instagram, web).</p>
                </div>
            );

        case 'business_hours': {
            const DAYS = [
                { n: 1, label: 'Lun' }, { n: 2, label: 'Mar' }, { n: 3, label: 'Mié' },
                { n: 4, label: 'Jue' }, { n: 5, label: 'Vie' }, { n: 6, label: 'Sáb' }, { n: 7, label: 'Dom' },
            ];
            const activeDays: number[] = d.days || [1, 2, 3, 4, 5];
            return (
                <div>
                    <label style={labelStyle}>Días activos</label>
                    <div className="d-flex flex-wrap gap-1 mb-3">
                        {DAYS.map(day => {
                            const on = activeDays.includes(day.n);
                            return (
                                <button key={day.n} type="button"
                                    className={`btn btn-sm ${on ? 'btn-primary' : 'btn-outline-secondary'}`}
                                    onClick={() => {
                                        const next = on ? activeDays.filter(x => x !== day.n) : [...activeDays, day.n].sort();
                                        setData({ days: next });
                                    }}>
                                    {day.label}
                                </button>
                            );
                        })}
                    </div>
                    <div className="d-flex gap-2 mb-3">
                        <div className="flex-fill">
                            <label style={labelStyle}>Desde</label>
                            <input type="time" className="form-control form-control-sm"
                                value={d.start_time || '09:00'}
                                onChange={e => setData({ start_time: e.target.value })} />
                        </div>
                        <div className="flex-fill">
                            <label style={labelStyle}>Hasta</label>
                            <input type="time" className="form-control form-control-sm"
                                value={d.end_time || '18:00'}
                                onChange={e => setData({ end_time: e.target.value })} />
                        </div>
                    </div>
                    <label style={labelStyle}>Zona horaria</label>
                    <input type="text" className="form-control form-control-sm"
                        placeholder="Europe/Madrid"
                        value={d.timezone || ''}
                        onChange={e => setData({ timezone: e.target.value })} />
                    <p style={hintStyle}>Guarda <code>within_business_hours</code> (sí/no) en el contexto. Pon un nodo <strong>Condición</strong> después para ramificar dentro/fuera de horario.</p>
                </div>
            );
        }

        case 'rich_message':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>URL de la imagen</label>
                        <input type="text" className="form-control form-control-sm"
                            placeholder="https://.../imagen.jpg"
                            value={d.image_url || ''}
                            onChange={e => setData({ image_url: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Título</label>
                        <input type="text" className="form-control form-control-sm"
                            value={d.title || ''}
                            onChange={e => setData({ title: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Texto</label>
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.subtitle || ''}
                            onChange={e => setData({ subtitle: e.target.value })} />
                    </div>
                    <label style={labelStyle}>Opciones (botones)</label>
                    {(d.options || []).map((opt: string, i: number) => (
                        <div key={i} className="input-group input-group-sm mb-1">
                            <span className="input-group-text">{i + 1}</span>
                            <input type="text" className="form-control" value={opt}
                                onChange={e => {
                                    const options = [...(d.options || [])];
                                    options[i] = e.target.value;
                                    setData({ options });
                                }} />
                            <button className="btn btn-outline-secondary"
                                onClick={() => setData({ options: (d.options || []).filter((_: any, j: number) => j !== i) })}>
                                <i className="fas fa-times" />
                            </button>
                        </div>
                    ))}
                    <button className="btn btn-sm btn-outline-primary mt-1"
                        onClick={() => setData({ options: [...(d.options || []), ''] })}>
                        <i className="fas fa-plus me-1" />Opción
                    </button>
                    <p style={{ ...hintStyle, marginTop: 8 }}>Las opciones se muestran como lista numerada (1, 2, 3…) en todos los canales. Sin opciones, la tarjeta solo informa y continúa.</p>

                    <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 12 }}>
                        <label style={labelStyle}>Tarjetas (carrusel de producto)</label>
                        {(d.cards || []).map((card: any, i: number) => (
                            <div key={i} style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: 8, marginBottom: 8 }}>
                                <div className="d-flex justify-content-between align-items-center mb-1">
                                    <strong style={{ fontSize: 12 }}>Tarjeta {i + 1}</strong>
                                    <button className="btn btn-sm btn-link text-danger p-0"
                                        onClick={() => setData({ cards: (d.cards || []).filter((_: any, j: number) => j !== i) })}>
                                        <i className="fas fa-times" />
                                    </button>
                                </div>
                                {[
                                    { key: 'title', ph: 'Título' },
                                    { key: 'subtitle', ph: 'Precio / subtítulo' },
                                    { key: 'image_url', ph: 'URL de la imagen' },
                                    { key: 'url', ph: 'Enlace (botón Ver)' },
                                ].map(f => (
                                    <input key={f.key} type="text" className="form-control form-control-sm mb-1" placeholder={f.ph}
                                        value={card[f.key] || ''}
                                        onChange={e => {
                                            const cards = [...(d.cards || [])];
                                            cards[i] = { ...cards[i], [f.key]: e.target.value };
                                            setData({ cards });
                                        }} />
                                ))}
                            </div>
                        ))}
                        <button className="btn btn-sm btn-outline-primary"
                            onClick={() => setData({ cards: [...(d.cards || []), {}] })}>
                            <i className="fas fa-plus me-1" />Tarjeta
                        </button>
                        <p style={{ ...hintStyle, marginTop: 8 }}>Con 2 o más tarjetas se envía como carrusel: nativo en Messenger, imágenes con texto en WhatsApp/Instagram, y lista numerada en web.</p>
                    </div>
                </div>
            );

        case 'end':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Acción al finalizar</label>
                        <select className="form-select form-select-sm"
                            value={d.action || 'close'}
                            onChange={e => setData({ action: e.target.value })}>
                            <option value="close">Cerrar conversación</option>
                            <option value="transfer_to_agent">Transferir a agente</option>
                        </select>
                    </div>
                    <label style={labelStyle}>Mensaje de despedida</label>
                    <textarea className="form-control form-control-sm" rows={3}
                        value={d.farewell || ''}
                        placeholder="Opcional: mensaje final al cliente"
                        onChange={e => setData({ farewell: e.target.value })} />
                </div>
            );

        case 'transfer':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Mensaje de transferencia</label>
                        <textarea className="form-control form-control-sm" rows={2}
                            value={d.message || 'Un momento, te transfiero con un agente.'}
                            onChange={e => setData({ message: e.target.value })} />
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Asignar a un grupo</label>
                        <select className="form-select form-select-sm"
                            value={d.group_id || ''}
                            onChange={e => setData({ group_id: e.target.value ? parseInt(e.target.value) : null })}>
                            <option value="">Cola general (sin grupo)</option>
                            {groups.map(g => <option key={g.id} value={g.id}>{g.name}</option>)}
                        </select>
                    </div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Asignar a un agente</label>
                        <select className="form-select form-select-sm"
                            value={d.assignee_id || ''}
                            onChange={e => setData({ assignee_id: e.target.value ? parseInt(e.target.value) : null })}>
                            <option value="">Sin agente específico</option>
                            {agents.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                        </select>
                    </div>
                    <p style={hintStyle}>Puedes asignar a un grupo, a un agente concreto, o dejar al cliente en la cola general.</p>
                </div>
            );

        case 'close':
            return (
                <div>
                    <label style={labelStyle}>Mensaje de despedida</label>
                    <textarea className="form-control form-control-sm" rows={3}
                        value={d.farewell || ''}
                        placeholder="Opcional: mensaje final al cliente"
                        onChange={e => setData({ farewell: e.target.value })} />
                    <p style={hintStyle}>La conversación se cerrará automáticamente.</p>
                </div>
            );

        case 'go_to_step': {
            const validTargets = allNodes.filter(n =>
                n.id !== draft.id && n.type !== 'branchItem'
            );
            return (
                <div>
                    <label style={labelStyle}>Ir al paso</label>
                    <select className="form-select form-select-sm"
                        value={d.target_node_id || ''}
                        onChange={e => {
                            const target = allNodes.find(n => n.id === e.target.value);
                            setData({
                                target_node_id: e.target.value,
                                target_label:   target?.label || '',
                            });
                        }}>
                        <option value="">— Seleccionar paso —</option>
                        {validTargets.map(n => (
                            <option key={n.id} value={n.id}>
                                {n.label || NODE_LABELS[n.type] || n.type}
                            </option>
                        ))}
                    </select>
                    <p style={hintStyle}>El flow saltará a ese paso (útil para reintentos o bucles).</p>
                </div>
            );
        }

        case 'add_tag':
            return (
                <div>
                    <label style={labelStyle}>Etiquetas a agregar</label>
                    {(d.tags || []).map((tag: string, i: number) => (
                        <div key={i} className="input-group input-group-sm mb-1">
                            <input type="text" className="form-control" value={tag}
                                onChange={e => {
                                    const tags = [...(d.tags || [])];
                                    tags[i] = e.target.value;
                                    setData({ tags });
                                }} />
                            <button className="btn btn-outline-secondary"
                                onClick={() => setData({ tags: (d.tags || []).filter((_: any, j: number) => j !== i) })}>
                                <i className="fas fa-times" />
                            </button>
                        </div>
                    ))}
                    <button className="btn btn-sm btn-outline-primary mt-1"
                        onClick={() => setData({ tags: [...(d.tags || []), ''] })}>
                        <i className="fas fa-plus me-1" />Agregar etiqueta
                    </button>
                    <p style={{ ...hintStyle, marginTop: 8 }}>Las etiquetas se agregan a la conversación y al cliente.</p>
                </div>
            );

        case 'set_attribute':
            return (
                <div>
                    <div style={{ marginBottom: 12 }}>
                        <label style={labelStyle}>Atributo</label>
                        <select className="form-select form-select-sm"
                            value={d.attribute || 'priority'}
                            onChange={e => setData({ attribute: e.target.value })}>
                            <option value="priority">Prioridad de la conversación</option>
                            <option value="status">Estado de la conversación</option>
                            <option value="assignee">Asignar agente</option>
                            <option value="custom">Campo personalizado</option>
                        </select>
                    </div>
                    {d.attribute === 'custom' && (
                        <div style={{ marginBottom: 8 }}>
                            <label style={labelStyle}>Nombre del campo</label>
                            <input type="text" className="form-control form-control-sm mb-2"
                                placeholder="ej: tipo_cliente"
                                value={d.custom_key || ''}
                                onChange={e => setData({ custom_key: e.target.value })} />
                        </div>
                    )}
                    <label style={labelStyle}>Valor</label>
                    <input type="text" className="form-control form-control-sm"
                        placeholder="Valor a establecer"
                        value={d.value || ''}
                        onChange={e => setData({ value: e.target.value })} />
                    <p style={hintStyle}>Usa {'{{variable}}'} para usar un valor del contexto.</p>
                </div>
            );

        default:
            return null;
    }
}

// ─── Main Editor ──────────────────────────────────────────────────────────────

export default function ChatFlowEditor({
    chatFlowName, chatFlowStatus,
    nodes: initialNodes, settings: initialSettings, agents = [], groups = [],
    saveUrl, publishUrl, indexUrl, csrfToken,
}: ChatFlowEditorProps) {
    const [backendNodes, setBackendNodes] = useState<BackendNode[]>(() => migrateNodes(initialNodes));
    const [flowName,     setFlowName]     = useState(chatFlowName);
    const [flowSettings, setFlowSettings] = useState<Record<string, any>>(() => initialSettings || {});
    const [showSettings, setShowSettings] = useState(false);
    const [selectedId,   setSelectedId]   = useState<string | null>(null);
    const [saving,       setSaving]        = useState(false);
    const [dirty,        setDirty]         = useState(false);
    const [showIssues,   setShowIssues]    = useState(false);
    const [nodes,        setNodes,         onNodesChange] = useNodesState<Node>([]);
    const [edges,        setEdges,         onEdgesChange] = useEdgesState<Edge>([]);

    const validation = React.useMemo(() => validateFlow(backendNodes), [backendNodes]);

    // Recompute layout whenever backendNodes (or their issues) change.
    useEffect(() => {
        const { xyNodes, xyEdges } = computeLayout(backendNodes);
        const marked = xyNodes.map(n =>
            n.type === 'flowNode'
                ? { ...n, data: { ...n.data, issue: validation.byNode.get(n.id) } }
                : n
        );
        setNodes(marked);
        setEdges(xyEdges);
        window.__chatflowNodes = backendNodes; // expose to jQuery test panel
    }, [backendNodes, validation]);

    // Mark dirty on any change after first render.
    const firstRender = useRef(true);
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        setDirty(true);
    }, [backendNodes, flowName, flowSettings]);

    // Warn before leaving with unsaved changes.
    useEffect(() => {
        const handler = (e: BeforeUnloadEvent) => {
            if (dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, [dirty]);

    // ── Undo / redo history ─────────────────────────────────────────────────────
    const history = useRef<BackendNode[][]>([]);
    const historyIndex = useRef(-1);
    const isUndoRedo = useRef(false);

    useEffect(() => {
        if (isUndoRedo.current) { isUndoRedo.current = false; return; }
        history.current = history.current.slice(0, historyIndex.current + 1);
        history.current.push(backendNodes);
        if (history.current.length > 60) history.current.shift();
        historyIndex.current = history.current.length - 1;
    }, [backendNodes]);

    const undo = useCallback(() => {
        if (historyIndex.current <= 0) return;
        historyIndex.current--;
        isUndoRedo.current = true;
        setBackendNodes(history.current[historyIndex.current]);
        setSelectedId(null);
    }, []);

    const redo = useCallback(() => {
        if (historyIndex.current >= history.current.length - 1) return;
        historyIndex.current++;
        isUndoRedo.current = true;
        setBackendNodes(history.current[historyIndex.current]);
        setSelectedId(null);
    }, []);

    // ── Clipboard (copy/paste) + ReactFlow instance for search ──────────────────
    const clipboard = useRef<BackendNode | null>(null);
    const rfInstance = useRef<any>(null);
    const [search, setSearch] = useState('');

    const copyNode = useCallback((id: string) => {
        const node = backendNodes.find(n => n.id === id);
        if (node && node.type !== 'start' && node.type !== 'branchItem') {
            clipboard.current = node;
            (window as any).toastr?.info('Nodo copiado', '', { timeOut: 900 });
        }
    }, [backendNodes]);

    const pasteNode = useCallback((parentId: string | null) => {
        const src = clipboard.current;
        if (!src) return;
        const target = parentId ?? selectedId;
        if (!target) return;
        setBackendNodes(prev => {
            const existingChild = prev.find(n => n.parentId === target && n.type !== 'branchItem');
            const newId = nanoid();
            let next = prev.map(n => n.id === existingChild?.id ? { ...n, parentId: newId } : n);
            next = [...next, { id: newId, type: src.type, parentId: target, label: `${src.label} (copia)`, data: structuredClone(src.data || {}) }];
            return next;
        });
    }, [selectedId]);

    const focusNode = useCallback((nodeId: string) => {
        const xy = nodes.find(n => n.id === nodeId);
        if (xy && rfInstance.current) {
            rfInstance.current.setCenter(xy.position.x + NODE_WIDTH / 2, xy.position.y + 40, { zoom: 1, duration: 400 });
        }
    }, [nodes]);

    const runSearch = useCallback((term: string) => {
        setSearch(term);
        const t = term.trim().toLowerCase();
        if (!t) return;
        const match = backendNodes.find(n => (n.label || NODE_LABELS[n.type] || '').toLowerCase().includes(t));
        if (match) { setSelectedId(match.id); focusNode(match.id); }
    }, [backendNodes, focusNode]);

    // ── Operations ────────────────────────────────────────────────────────────

    const addNode = useCallback((type: string, parentId: string) => {
        const newId = nanoid();
        setBackendNodes(prev => {
            // Reparent existing non-branchItem child so new node inserts in between.
            const existingChild = prev.find(n => n.parentId === parentId && n.type !== 'branchItem');
            let next = prev.map(n =>
                n.id === existingChild?.id ? { ...n, parentId: newId } : n
            );
            next = [...next, { id: newId, type, parentId, label: NODE_LABELS[type] || type, data: {} }];
            if (type === 'branches') {
                next.push(
                    { id: nanoid(), type: 'branchItem', parentId: newId, label: 'Si',   data: { name: 'Si',   isElse: false, conditions: [] } },
                    { id: nanoid(), type: 'branchItem', parentId: newId, label: 'Else', data: { name: 'Else', isElse: true,  conditions: [] } },
                );
            }
            return next;
        });
        setSelectedId(newId);
    }, []);

    const deleteNode = useCallback((id: string) => {
        setBackendNodes(prev => {
            const toRemove = new Set<string>([id]);
            let changed = true;
            while (changed) {
                changed = false;
                prev.forEach(n => {
                    if (n.parentId && toRemove.has(n.parentId) && !toRemove.has(n.id)) {
                        toRemove.add(n.id);
                        changed = true;
                    }
                });
            }
            return prev.filter(n => !toRemove.has(n.id));
        });
        setSelectedId(null);
    }, []);

    const duplicateNode = useCallback((id: string) => {
        setBackendNodes(prev => {
            const original = prev.find(n => n.id === id);
            if (!original || original.type === 'start') return prev;
            const newId = nanoid();
            // Insert the copy as a sibling: reparent the original's child to the copy.
            const existingChild = prev.find(n => n.parentId === id && n.type !== 'branchItem');
            let next = prev.map(n => n.id === existingChild?.id ? { ...n, parentId: newId } : n);
            next = [...next, {
                id: newId,
                type: original.type,
                parentId: id,
                label: `${original.label} (copia)`,
                data: structuredClone(original.data || {}),
            }];
            return next;
        });
    }, []);

    const addBranch = useCallback((branchesId: string) => {
        setBackendNodes(prev => [
            ...prev,
            { id: nanoid(), type: 'branchItem', parentId: branchesId, label: 'Nueva rama', data: { name: 'Nueva rama', isElse: false, conditions: [] } },
        ]);
    }, []);

    const updateBranchName = useCallback((itemId: string, name: string) => {
        setBackendNodes(prev => prev.map(n =>
            n.id === itemId ? { ...n, label: name, data: { ...n.data, name } } : n
        ));
    }, []);

    // Wire globals for sub-components.
    useEffect(() => {
        window.__chatflowAddNode          = addNode;
        window.__chatflowDeleteNode       = deleteNode;
        window.__chatflowDuplicateNode    = duplicateNode;
        window.__chatflowAddBranch        = addBranch;
        window.__chatflowUpdateBranchName = updateBranchName;
    }, [addNode, deleteNode, duplicateNode, addBranch, updateBranchName]);

    // ── Node click ────────────────────────────────────────────────────────────

    const selectedNode = selectedId ? (backendNodes.find(n => n.id === selectedId) ?? null) : null;

    const onNodeClick = useCallback((_: React.MouseEvent, node: Node) => {
        if (node.type === 'addStepNode') return;
        setSelectedId(node.id);
    }, []);

    const handleUpdateNode = useCallback((updated: BackendNode) => {
        setBackendNodes(prev => prev.map(n => n.id === updated.id ? updated : n));
        // Only local state changed — nothing is persisted until "Guardar".
        (window as any).toastr?.info('Cambios aplicados (recuerda Guardar)', '', { timeOut: 1200 });
    }, []);

    // ── Save / Publish ────────────────────────────────────────────────────────

    // Turn a Laravel AJAX error into a readable message: 422 validation errors
    // (responseJSON.errors) joined per field, else the server message, else fallback.
    const extractAjaxError = (e: any, fallback: string): string => {
        const data = e?.response?.data;
        if (data?.errors) {
            return Object.values(data.errors as Record<string, string[]>).flat().join(' ');
        }
        return data?.message || fallback;
    };

    const handleSave = async (): Promise<boolean> => {
        if (!flowName.trim()) {
            (window as any).toastr?.warning('Ingresa un nombre para el flow.');
            return false;
        }
        setSaving(true);
        try {
            await axios.put(saveUrl, { name: flowName, nodes: JSON.stringify(backendNodes), trigger_conditions: flowSettings }, {
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            });
            setDirty(false);
            (window as any).toastr?.success('Flow guardado correctamente');
            return true;
        } catch (e: any) {
            (window as any).toastr?.error(extractAjaxError(e, 'Error al guardar el flow'));
            return false;
        } finally {
            setSaving(false);
        }
    };

    const handlePublish = async () => {
        if (validation.errors.length > 0) {
            setShowIssues(true);
            (window as any).toastr?.error('Corrige los errores antes de publicar.');
            return;
        }
        // Persist the canvas first: the server snapshots the SAVED nodes, so
        // publishing without saving would activate stale content.
        if (!await handleSave()) {
            return;
        }
        setSaving(true);
        try {
            await axios.post(publishUrl, {}, { headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
            setDirty(false);
            (window as any).toastr?.success('Flow publicado');
        } catch (e: any) {
            (window as any).toastr?.error(extractAjaxError(e, 'Error al publicar'));
        } finally {
            setSaving(false);
        }
    };

    // Keyboard shortcuts: Ctrl+S save, Ctrl+Z/Y undo/redo, Ctrl+C/V copy/paste,
    // Ctrl+D duplicate, Supr delete.
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            const target = e.target as HTMLElement;
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable;
            const mod = e.ctrlKey || e.metaKey;
            const key = e.key.toLowerCase();

            if (mod && key === 's') { e.preventDefault(); handleSave(); return; }
            if (mod && key === 'z' && !e.shiftKey) { e.preventDefault(); undo(); return; }
            if (mod && (key === 'y' || (key === 'z' && e.shiftKey))) { e.preventDefault(); redo(); return; }
            if (typing) return;
            if ((e.key === 'Delete' || e.key === 'Backspace') && selectedId) {
                const n = backendNodes.find(x => x.id === selectedId);
                if (n && n.type !== 'start') { e.preventDefault(); deleteNode(selectedId); }
            }
            if (mod && key === 'd' && selectedId) { e.preventDefault(); duplicateNode(selectedId); }
            if (mod && key === 'c' && selectedId) { copyNode(selectedId); }
            if (mod && key === 'v' && selectedId && clipboard.current) { e.preventDefault(); pasteNode(null); }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [selectedId, backendNodes, deleteNode, duplicateNode, undo, redo, copyNode, pasteNode]);

    const isActive = chatFlowStatus === 'active';

    return (
        <div style={{ position: 'relative', width: '100%', height: '100%', display: 'flex' }}>

            {/* ── Canvas ── */}
            <div style={{ flex: 1, position: 'relative' }}>
                <ReactFlow
                    nodes={nodes}
                    edges={edges}
                    onNodesChange={onNodesChange}
                    onEdgesChange={onEdgesChange}
                    onNodeClick={onNodeClick}
                    onPaneClick={() => setSelectedId(null)}
                    onInit={inst => { rfInstance.current = inst; }}
                    nodeTypes={nodeTypes}
                    fitView
                    fitViewOptions={{ padding: 0.25 }}
                    nodesDraggable={false}
                    nodesConnectable={false}
                    elementsSelectable
                    proOptions={{ hideAttribution: true }}
                >
                    <Background variant={BackgroundVariant.Dots} color="#d1d5db" gap={20} size={1.5} style={{ background: '#f5f5f5' }} />
                    <Controls />
                    <MiniMap nodeStrokeWidth={2} style={{ background: '#fff', border: '1px solid #e2e8f0' }} />

                    {/* Top toolbar */}
                    <Panel position="top-center" style={{ background: 'transparent', pointerEvents: 'none', width: '100%', margin: 0, padding: 0 }}>
                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 8,
                            background: '#fff', borderBottom: '1px solid #e2e8f0',
                            padding: '8px 16px', pointerEvents: 'auto',
                            boxShadow: '0 1px 4px rgba(0,0,0,.06)',
                        }}>
                            <input
                                type="text"
                                value={flowName}
                                onChange={e => setFlowName(e.target.value)}
                                style={{ border: '1px solid #e2e8f0', borderRadius: 6, padding: '5px 10px', fontSize: 13, width: 250, outline: 'none' }}
                                placeholder="Nombre del flow..."
                            />
                            <div style={{ position: 'relative', display: 'inline-flex', alignItems: 'center' }}>
                                <i className="fas fa-search" style={{ position: 'absolute', left: 9, color: '#94a3b8', fontSize: 11 }} />
                                <input
                                    type="search"
                                    value={search}
                                    onChange={e => runSearch(e.target.value)}
                                    placeholder="Buscar nodo…"
                                    style={{ border: '1px solid #e2e8f0', borderRadius: 6, padding: '5px 8px 5px 26px', fontSize: 12, width: 150, outline: 'none' }}
                                />
                            </div>
                            <button onClick={undo} title="Deshacer (Ctrl+Z)" style={iconBtnStyle}>
                                <i className="fas fa-rotate-left" />
                            </button>
                            <button onClick={redo} title="Rehacer (Ctrl+Y)" style={iconBtnStyle}>
                                <i className="fas fa-rotate-right" />
                            </button>
                            <button onClick={() => setShowSettings(s => !s)} title="Ajustes del flow" style={iconBtnStyle}>
                                <i className="fas fa-gear" />
                            </button>
                            <button onClick={handleSave} disabled={saving} style={btnStyle('#475569')}>
                                <i className="fas fa-save" style={{ marginRight: 5 }} />
                                {saving ? 'Guardando…' : 'Guardar'}
                            </button>
                            {dirty && (
                                <span title="Cambios sin guardar" style={{ fontSize: 11, color: '#f59e0b', fontWeight: 600, display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                                    <span style={{ width: 7, height: 7, borderRadius: '50%', background: '#f59e0b', display: 'inline-block' }} />
                                    Sin guardar
                                </span>
                            )}
                            {(validation.errors.length + validation.warnings.length) > 0 && (
                                <button onClick={() => setShowIssues(s => !s)}
                                    style={btnStyle(validation.errors.length ? '#ef4444' : '#f59e0b')}
                                    title="Ver problemas del flow">
                                    <i className="fas fa-triangle-exclamation" style={{ marginRight: 5 }} />
                                    {validation.errors.length + validation.warnings.length}
                                </button>
                            )}
                            {!isActive && (
                                <button onClick={handlePublish} disabled={saving} style={btnStyle('#90bb13')}>
                                    <i className="fas fa-rocket" style={{ marginRight: 5 }} />Publicar
                                </button>
                            )}
                            <button
                                onClick={() => (window as any).__chatflowOpenTestPanel?.()}
                                style={{ ...btnStyle('#4e6ef5'), marginLeft: 'auto' }}
                            >
                                <i className="fas fa-flask" style={{ marginRight: 5 }} />Probar flow
                            </button>
                            <a href={indexUrl} style={{ ...btnStyle('#64748b'), textDecoration: 'none' }}
                                onClick={e => {
                                    if (dirty && !window.confirm('Tienes cambios sin guardar. ¿Salir de todos modos?')) {
                                        e.preventDefault();
                                    }
                                }}>
                                <i className="fas fa-arrow-left" style={{ marginRight: 5 }} />Volver
                            </a>
                        </div>
                    </Panel>

                    {/* Flow settings panel */}
                    {showSettings && (
                        <Panel position="top-right" style={{ margin: 12 }}>
                            <div style={{ background: '#fff', border: '1px solid #e2e8f0', borderRadius: 10, boxShadow: '0 4px 16px rgba(0,0,0,.12)', width: 300, padding: 16 }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                                    <strong style={{ fontSize: 13 }}>Ajustes del flow</strong>
                                    <button onClick={() => setShowSettings(false)} style={{ border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8' }}><i className="fas fa-times" /></button>
                                </div>
                                <SettingToggle label="Responder en el idioma del cliente"
                                    hint="Detecta el idioma y traduce/responde en él (WhatsApp, Instagram…)."
                                    checked={!!flowSettings.multilingual}
                                    onChange={v => setFlowSettings(s => ({ ...s, multilingual: v }))} />
                                <SettingToggle label="Escalar si el cliente se frustra"
                                    hint="Analiza el sentimiento y transfiere a un agente si detecta enfado."
                                    checked={!!flowSettings.sentiment_escalation}
                                    onChange={v => setFlowSettings(s => ({ ...s, sentiment_escalation: v }))} />
                                <SettingToggle label="Permitir 'hablar con un agente'"
                                    hint="Palabra clave (agente, humano…) que transfiere desde cualquier punto."
                                    checked={flowSettings.escape_enabled !== false}
                                    onChange={v => setFlowSettings(s => ({ ...s, escape_enabled: v }))} />
                                <SettingToggle label="Resumen IA al transferir"
                                    hint="Al pasar a un agente, deja una nota interna con el resumen de la conversación."
                                    checked={!!flowSettings.handoff_summary}
                                    onChange={v => setFlowSettings(s => ({ ...s, handoff_summary: v }))} />

                                <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 4 }}>
                                    <label style={{ ...labelStyle, marginBottom: 4 }}>A/B testing</label>
                                    <div className="d-flex gap-2">
                                        <input type="number" className="form-control form-control-sm" placeholder="ID flow variante B"
                                            value={flowSettings.ab_variant_id || ''}
                                            onChange={e => setFlowSettings(s => ({ ...s, ab_variant_id: e.target.value ? parseInt(e.target.value) : null }))} />
                                        <input type="number" min={1} max={99} className="form-control form-control-sm" style={{ maxWidth: 90 }} placeholder="% B"
                                            value={flowSettings.ab_split || ''}
                                            onChange={e => setFlowSettings(s => ({ ...s, ab_split: e.target.value ? parseInt(e.target.value) : null }))} />
                                    </div>
                                    <p style={hintStyle}>Envía un % de conversaciones al flow variante para comparar resultados.</p>
                                </div>

                                <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 4 }}>
                                    <label style={{ ...labelStyle, marginBottom: 4 }}>Disparador por evento (outbound)</label>
                                    <select className="form-select form-select-sm"
                                        value={flowSettings.business_event || ''}
                                        onChange={e => setFlowSettings(s => ({ ...s, business_event: e.target.value || null }))}>
                                        <option value="">Ninguno (no proactivo)</option>
                                        <option value="abandoned_cart">Carrito abandonado (PrestaShop)</option>
                                        <option value="order_status">Cambio de estado de pedido (PrestaShop)</option>
                                        <option value="order_ready">Pedido listo (ERP)</option>
                                    </select>
                                    <p style={hintStyle}>El flow se lanza automáticamente por WhatsApp cuando ocurre el evento. Empieza con un nodo de mensaje (usa plantilla WhatsApp para enviar fuera de la ventana de 24h).</p>
                                </div>

                                <p style={{ ...hintStyle, marginTop: 10 }}>Recuerda <strong>Guardar</strong> para aplicar los cambios.</p>
                            </div>
                        </Panel>
                    )}

                    {/* Issues panel */}
                    {showIssues && (validation.errors.length + validation.warnings.length) > 0 && (
                        <Panel position="bottom-left" style={{ margin: 12 }}>
                            <div style={{
                                background: '#fff', border: '1px solid #e2e8f0', borderRadius: 10,
                                boxShadow: '0 4px 16px rgba(0,0,0,.12)', width: 320, maxHeight: 280, overflow: 'auto',
                            }}>
                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', borderBottom: '1px solid #f1f5f9' }}>
                                    <strong style={{ fontSize: 13 }}>Problemas del flow</strong>
                                    <button onClick={() => setShowIssues(false)} style={{ border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8' }}>
                                        <i className="fas fa-times" />
                                    </button>
                                </div>
                                <div style={{ padding: '6px 0' }}>
                                    {[...validation.errors, ...validation.warnings].map((issue, i) => (
                                        <div key={i}
                                            onClick={() => issue.nodeId && setSelectedId(issue.nodeId)}
                                            style={{
                                                display: 'flex', alignItems: 'flex-start', gap: 8, padding: '7px 14px',
                                                cursor: issue.nodeId ? 'pointer' : 'default', fontSize: 12.5, color: '#334155',
                                            }}
                                            onMouseEnter={e => (e.currentTarget.style.background = '#f8fafc')}
                                            onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}>
                                            <i className={`fas fa-${issue.level === 'error' ? 'circle-exclamation' : 'triangle-exclamation'}`}
                                                style={{ color: issue.level === 'error' ? '#ef4444' : '#f59e0b', marginTop: 2 }} />
                                            <span>{issue.message}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </Panel>
                    )}
                </ReactFlow>
            </div>

            {/* ── Properties panel ── */}
            {selectedNode && (
                <NodePropertiesPanel
                    node={selectedNode}
                    allNodes={backendNodes}
                    agents={agents}
                    groups={groups}
                    onUpdate={handleUpdateNode}
                    onDelete={deleteNode}
                    onClose={() => setSelectedId(null)}
                />
            )}
        </div>
    );
}

function btnStyle(bg: string): React.CSSProperties {
    return {
        background: bg, color: '#fff', border: 'none', borderRadius: 6,
        padding: '6px 12px', fontSize: 12, fontWeight: 600, cursor: 'pointer',
        display: 'inline-flex', alignItems: 'center', whiteSpace: 'nowrap',
    };
}

const iconBtnStyle: React.CSSProperties = {
    background: '#fff', color: '#475569', border: '1px solid #e2e8f0', borderRadius: 6,
    width: 30, height: 30, fontSize: 12, cursor: 'pointer',
    display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
};

function SettingToggle({ label, hint, checked, onChange }: { label: string; hint: string; checked: boolean; onChange: (v: boolean) => void }) {
    return (
        <div style={{ marginBottom: 12 }}>
            <label style={{ display: 'flex', alignItems: 'flex-start', gap: 8, cursor: 'pointer' }}>
                <input type="checkbox" checked={checked} onChange={e => onChange(e.target.checked)} style={{ marginTop: 3 }} />
                <span>
                    <span style={{ fontSize: 13, fontWeight: 500, color: '#1e293b' }}>{label}</span>
                    <span style={{ display: 'block', fontSize: 11, color: '#94a3b8', marginTop: 2 }}>{hint}</span>
                </span>
            </label>
        </div>
    );
}

// ─── Global type augmentation ─────────────────────────────────────────────────

declare global {
    interface Window {
        __chatflowNodes?:             BackendNode[];
        __chatflowAddNode?:           (type: string, parentId: string) => void;
        __chatflowDeleteNode?:        (id: string) => void;
        __chatflowDuplicateNode?:     (id: string) => void;
        __chatflowAddBranch?:         (branchesId: string) => void;
        __chatflowUpdateBranchName?:  (itemId: string, name: string) => void;
    }
}
