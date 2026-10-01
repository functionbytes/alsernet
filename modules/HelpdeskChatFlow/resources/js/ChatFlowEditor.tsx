import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
    Background,
    BackgroundVariant,
    Controls,
    Edge,
    MiniMap,
    Node,
    ReactFlow,
    useEdgesState,
    useNodesState,
} from '@xyflow/react';
import axios from 'axios';

import { BackendNode, ChatFlowEditorProps } from './editor/types';
import { NODE_LABELS, NODE_WIDTH } from './editor/constants';
import { computeLayout, migrateNodes, nanoid, validateFlow } from './editor/utils';
import { useNodeOperations } from './editor/hooks/useNodeOperations';
import { xyNodeTypes } from './editor/components/xyNodeTypes';
import Toolbar from './editor/components/Toolbar';
import FlowSettingsPanel from './editor/components/FlowSettingsPanel';
import IssuesPanel from './editor/components/IssuesPanel';
import NodePropertiesPanel from './editor/components/NodePropertiesPanel';

// ─── Main Editor ──────────────────────────────────────────────────────────────

export default function ChatFlowEditor({
    chatFlowId, chatFlowName, chatFlowStatus, chatFlowTriggerType, proceduresUrl, actionsCatalogUrl,
    nodes: initialNodes, settings: initialSettings, agents = [], groups = [],
    saveUrl, publishUrl, indexUrl, csrfToken,
}: ChatFlowEditorProps) {
    const [backendNodes, setBackendNodes] = useState<BackendNode[]>(() => migrateNodes(initialNodes));
    const [flowName,     setFlowName]     = useState(chatFlowName);
    const [triggerType,  setTriggerType]  = useState(chatFlowTriggerType || 'conversation_start');
    const [flowSettings, setFlowSettings] = useState<Record<string, any>>(() => initialSettings || {});
    const [showSettings, setShowSettings] = useState(false);
    const [selectedId,   setSelectedId]   = useState<string | null>(null);
    const [saving,       setSaving]        = useState(false);
    const [dirty,        setDirty]         = useState(false);
    const [showIssues,   setShowIssues]    = useState(false);
    const [nodes,        setNodes,         onNodesChange] = useNodesState<Node>([]);
    const [edges,        setEdges,         onEdgesChange] = useEdgesState<Edge>([]);

    // Endpoints the node config panels read (procedures list, AI action catalog).
    window.__chatflowUrls = { flowId: chatFlowId, procedures: proceduresUrl, actionsCatalog: actionsCatalogUrl };

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
    }, [backendNodes, flowName, flowSettings, triggerType]);

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

    const { deleteNode, duplicateNode } = useNodeOperations(setBackendNodes, setSelectedId);

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
            // PUT real por AJAX da 405 en el Docker de este proyecto (gotcha conocido).
            // El body va como JSON, asi que un campo _method no lo lee Laravel (solo
            // mira form/multipart o query string) y hay que spoofear con la cabecera,
            // igual que en public/vendor/helpdesk/*.js.
            await axios.post(saveUrl, { name: flowName, nodes: JSON.stringify(backendNodes), trigger_type: triggerType, trigger_conditions: flowSettings }, {
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-HTTP-Method-Override': 'PUT' },
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
        const headers = { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' };
        try {
            try {
                await axios.post(publishUrl, {}, { headers });
            } catch (e: any) {
                // Failing regression scenarios block publishing; let the user
                // knowingly override it (the server logs the override).
                const failing = e?.response?.status === 422 ? e.response.data?.failing_tests : null;
                if (!failing?.length || !window.confirm(`${e.response.data.message}\n\n¿Publicar igualmente?`)) {
                    throw e;
                }
                await axios.post(publishUrl, { skip_tests: 1 }, { headers });
            }
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
    const issuesCount = validation.errors.length + validation.warnings.length;

    const handleNavigateAway = (e: React.MouseEvent) => {
        if (dirty && !window.confirm('Tienes cambios sin guardar. ¿Salir de todos modos?')) {
            e.preventDefault();
        }
    };

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
                    nodeTypes={xyNodeTypes}
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

                    <Toolbar
                        flowName={flowName}
                        onFlowNameChange={setFlowName}
                        search={search}
                        onSearchChange={runSearch}
                        onUndo={undo}
                        onRedo={redo}
                        onToggleSettings={() => setShowSettings(s => !s)}
                        onSave={handleSave}
                        saving={saving}
                        dirty={dirty}
                        issuesCount={issuesCount}
                        hasErrors={validation.errors.length > 0}
                        onToggleIssues={() => setShowIssues(s => !s)}
                        isActive={isActive}
                        onPublish={handlePublish}
                        onOpenTestPanel={() => (window as any).__chatflowOpenTestPanel?.()}
                        indexUrl={indexUrl}
                        onNavigateAway={handleNavigateAway}
                    />

                    {showSettings && (
                        <FlowSettingsPanel
                            flowSettings={flowSettings}
                            setFlowSettings={setFlowSettings}
                            triggerType={triggerType}
                            setTriggerType={setTriggerType}
                            onClose={() => setShowSettings(false)}
                        />
                    )}

                    {showIssues && issuesCount > 0 && (
                        <IssuesPanel
                            errors={validation.errors}
                            warnings={validation.warnings}
                            onClose={() => setShowIssues(false)}
                            onSelectNode={setSelectedId}
                        />
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
