import React from 'react';
import { Node, Edge } from '@xyflow/react';
import { BackendNode, FlowIssue, FlowValidation } from './types';
import {
    DOC_TYPE_LABELS, TERMINAL_TYPES, VALIDATE_TERMINAL, VALIDATE_WAIT,
    NODE_LABELS, V_GAP, H_GAP, edgeStyle,
} from './constants';

// ─── Helpers ──────────────────────────────────────────────────────────────────

export function nanoid(): string {
    return Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
}

export function getPreviewText(node: BackendNode): string {
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
export function collectFlowVariables(nodes: BackendNode[]): string[] {
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
export function nodeMessageText(node: BackendNode): string | null {
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
export function highlightVars(text: string): React.ReactNode {
    return text.split(/(\{\{\w+\}\})/g).map((part, i) =>
        /^\{\{\w+\}\}$/.test(part)
            ? <span key={i} style={{ background: '#fef08a', borderRadius: 3, padding: '0 2px', color: '#854d0e' }}>{part}</span>
            : <React.Fragment key={i}>{part}</React.Fragment>
    );
}

export function migrateNodes(raw: any[]): BackendNode[] {
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

export function validateFlow(nodes: BackendNode[]): FlowValidation {
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

export function computeLayout(backendNodes: BackendNode[]): { xyNodes: Node[]; xyEdges: Edge[] } {
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
