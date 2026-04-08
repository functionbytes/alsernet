import React, { useCallback, useState, useEffect } from 'react';
import {
    ReactFlow,
    Node,
    Edge,
    addEdge,
    Connection,
    useNodesState,
    useEdgesState,
    Background,
    Controls,
    MiniMap,
    Panel,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

// Custom node types
import InputNode from './nodes/InputNode';
import PromptNode from './nodes/PromptNode';
import ConditionNode from './nodes/ConditionNode';
import ActionNode from './nodes/ActionNode';
import OutputNode from './nodes/OutputNode';

// Node type configuration
const nodeTypes = {
    input: InputNode,
    prompt: PromptNode,
    condition: ConditionNode,
    action: ActionNode,
    output: OutputNode,
};

interface FlowEditorProps {
    flowId: number;
    flow: {
        id: number;
        name: string;
        description?: string;
        trigger: string;
        status: string;
        nodes: Node[];
        edges: Edge[];
    };
}

export function FlowEditor({ flowId, flow }: FlowEditorProps) {
    const queryClient = useQueryClient();
    const [nodes, setNodes, onNodesChange] = useNodesState(flow.nodes || []);
    const [edges, setEdges, onEdgesChange] = useEdgesState(flow.edges || []);
    const [selectedNode, setSelectedNode] = useState<Node | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    // Load flow data
    const { data: flowData } = useQuery({
        queryKey: ['flow', flowId],
        queryFn: () => axios.get(`/manager/helpdesk/ai-agent/flows/${flowId}`).then(r => r.data),
        initialData: flow,
    });

    // Save flow structure mutation
    const saveFlowMutation = useMutation({
        mutationFn: (data: { nodes: Node[]; edges: Edge[] }) =>
            axios.put(`/manager/helpdesk/ai-agent/flows/${flowId}/structure`, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['flow', flowId] });
            showNotification('Flujo guardado correctamente', 'success');
        },
        onError: (error: any) => {
            showNotification('Error al guardar: ' + error.response?.data?.message, 'error');
        },
    });

    // Handle node connection
    const onConnect = useCallback(
        (connection: Connection) => {
            setEdges((eds) => addEdge(connection, eds));
        },
        [setEdges]
    );

    // Add new node
    const addNode = (type: string) => {
        const newNodeId = `${type}-${Date.now()}`;
        const newNode: Node = {
            id: newNodeId,
            data: {
                label: `Nuevo ${getNodeLabel(type)}`,
                type,
            },
            position: {
                x: Math.random() * 250,
                y: Math.random() * 250,
            },
            type,
        };

        setNodes((nds) => [...nds, newNode]);
    };

    // Save flow
    const handleSaveFlow = async () => {
        setIsSaving(true);
        await saveFlowMutation.mutateAsync({
            nodes,
            edges,
        });
        setIsSaving(false);
    };

    // Delete selected node
    const deleteSelectedNode = () => {
        if (!selectedNode) return;

        setNodes((nds) => nds.filter((n) => n.id !== selectedNode.id));
        setEdges((eds) =>
            eds.filter((e) => e.source !== selectedNode.id && e.target !== selectedNode.id)
        );
        setSelectedNode(null);
    };

    // Auto-layout nodes
    const autoLayoutNodes = () => {
        const layoutedNodes = layoutNodes(nodes, edges);
        setNodes(layoutedNodes);
    };

    return (
        <div className="flow-editor" style={{ width: '100%', height: '100vh' }}>
            <ReactFlow
                nodes={nodes}
                edges={edges}
                onNodesChange={onNodesChange}
                onEdgesChange={onEdgesChange}
                onConnect={onConnect}
                onNodeClick={(_, node) => setSelectedNode(node)}
                nodeTypes={nodeTypes}
                fitView
            >
                <Background />
                <Controls />
                <MiniMap />

                {/* Top Toolbar */}
                <Panel position="top-left" className="flow-toolbar">
                    <div className="btn-group" role="group">
                        <button
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => addNode('input')}
                            title="Agregar nodo de entrada"
                        >
                            <i className="fa fa-arrow-down"></i> Entrada
                        </button>
                        <button
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => addNode('prompt')}
                            title="Agregar nodo de prompt"
                        >
                            <i className="fa fa-message"></i> Prompt
                        </button>
                        <button
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => addNode('condition')}
                            title="Agregar nodo de condición"
                        >
                            <i className="fa fa-code-branch"></i> Condición
                        </button>
                        <button
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => addNode('action')}
                            title="Agregar nodo de acción"
                        >
                            <i className="fa fa-bolt"></i> Acción
                        </button>
                        <button
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => addNode('output')}
                            title="Agregar nodo de salida"
                        >
                            <i className="fa fa-arrow-up"></i> Salida
                        </button>
                    </div>
                </Panel>

                {/* Right Sidebar - Node Properties */}
                {selectedNode && (
                    <Panel position="right-top" className="flow-properties">
                        <div className="card" style={{ width: '300px' }}>
                            <div className="card-header">
                                <h6 className="mb-0">Propiedades del Nodo</h6>
                            </div>
                            <div className="card-body">
                                <div className="mb-3">
                                    <label className="form-label small">Tipo</label>
                                    <div className="form-control-plaintext small">
                                        {getNodeLabel(selectedNode.type || 'input')}
                                    </div>
                                </div>

                                <div className="mb-3">
                                    <label className="form-label small">Etiqueta</label>
                                    <input
                                        type="text"
                                        className="form-control form-control-sm"
                                        value={selectedNode.data?.label || ''}
                                        onChange={(e) => {
                                            setNodes((nds) =>
                                                nds.map((n) =>
                                                    n.id === selectedNode.id
                                                        ? {
                                                              ...n,
                                                              data: {
                                                                  ...n.data,
                                                                  label: e.target.value,
                                                              },
                                                          }
                                                        : n
                                                )
                                            );
                                            setSelectedNode({
                                                ...selectedNode,
                                                data: {
                                                    ...selectedNode.data,
                                                    label: e.target.value,
                                                },
                                            });
                                        }}
                                    />
                                </div>

                                {/* Node-specific properties */}
                                {selectedNode.type === 'prompt' && (
                                    <div className="mb-3">
                                        <label className="form-label small">Instrucciones</label>
                                        <textarea
                                            className="form-control form-control-sm"
                                            rows={4}
                                            placeholder="Instrucciones para el LLM..."
                                            value={selectedNode.data?.instructions || ''}
                                            onChange={(e) => {
                                                setNodes((nds) =>
                                                    nds.map((n) =>
                                                        n.id === selectedNode.id
                                                            ? {
                                                                  ...n,
                                                                  data: {
                                                                      ...n.data,
                                                                      instructions: e.target.value,
                                                                  },
                                                              }
                                                            : n
                                                    )
                                                );
                                            }}
                                        />
                                    </div>
                                )}

                                {selectedNode.type === 'condition' && (
                                    <div className="mb-3">
                                        <label className="form-label small">Condición</label>
                                        <input
                                            type="text"
                                            className="form-control form-control-sm"
                                            placeholder="Ej: response.includes('sí')"
                                            value={selectedNode.data?.condition || ''}
                                            onChange={(e) => {
                                                setNodes((nds) =>
                                                    nds.map((n) =>
                                                        n.id === selectedNode.id
                                                            ? {
                                                                  ...n,
                                                                  data: {
                                                                      ...n.data,
                                                                      condition: e.target.value,
                                                                  },
                                                              }
                                                            : n
                                                    )
                                                );
                                            }}
                                        />
                                    </div>
                                )}

                                {selectedNode.type === 'action' && (
                                    <div className="mb-3">
                                        <label className="form-label small">Acción</label>
                                        <select
                                            className="form-select form-select-sm"
                                            value={selectedNode.data?.action || 'log'}
                                            onChange={(e) => {
                                                setNodes((nds) =>
                                                    nds.map((n) =>
                                                        n.id === selectedNode.id
                                                            ? {
                                                                  ...n,
                                                                  data: {
                                                                      ...n.data,
                                                                      action: e.target.value,
                                                                  },
                                                              }
                                                            : n
                                                    )
                                                );
                                            }}
                                        >
                                            <option value="log">Registrar</option>
                                            <option value="email">Enviar Email</option>
                                            <option value="save">Guardar Datos</option>
                                            <option value="webhook">Llamar Webhook</option>
                                        </select>
                                    </div>
                                )}

                                <div className="d-grid gap-2">
                                    <button
                                        className="btn btn-sm btn-danger"
                                        onClick={deleteSelectedNode}
                                    >
                                        <i className="fa fa-trash"></i> Eliminar Nodo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Panel>
                )}

                {/* Bottom Toolbar - Actions */}
                <Panel position="bottom-center" className="flow-actions">
                    <div className="btn-group" role="group">
                        <button
                            className="btn btn-sm btn-outline-secondary"
                            onClick={autoLayoutNodes}
                            title="Organizar automáticamente"
                        >
                            <i className="fa fa-bars"></i> Organizar
                        </button>
                        <button
                            className="btn btn-sm btn-primary"
                            onClick={handleSaveFlow}
                            disabled={isSaving}
                            title="Guardar cambios"
                        >
                            {isSaving ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2"></span>
                                    Guardando...
                                </>
                            ) : (
                                <>
                                    <i className="fa fa-check"></i> Guardar
                                </>
                            )}
                        </button>
                    </div>
                </Panel>
            </ReactFlow>

            <style>{`
                .flow-editor {
                    position: relative;
                    background: #fafafa;
                }

                .flow-toolbar,
                .flow-actions,
                .flow-properties {
                    background: white;
                    padding: 10px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }

                .flow-toolbar .btn-group {
                    display: flex;
                    gap: 5px;
                }

                .flow-actions .btn-group {
                    display: flex;
                    gap: 5px;
                }

                .react-flow__node {
                    border-radius: 8px;
                    border: 2px solid #ccc;
                    background: white;
                    padding: 15px;
                    min-width: 150px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .react-flow__node.selected {
                    border-color: #007bff;
                    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
                }

                .react-flow__node.input {
                    background: #e7f3ff;
                    border-color: #0066cc;
                }

                .react-flow__node.prompt {
                    background: #fff3e7;
                    border-color: #ff9800;
                }

                .react-flow__node.condition {
                    background: #f3e5f5;
                    border-color: #9c27b0;
                }

                .react-flow__node.action {
                    background: #e8f5e9;
                    border-color: #4caf50;
                }

                .react-flow__node.output {
                    background: #fce4ec;
                    border-color: #e91e63;
                }

                .react-flow__edge-path {
                    stroke: #999;
                    stroke-width: 2;
                }

                .react-flow__handle {
                    background: #555;
                }

                .react-flow__handle.connect-above {
                    border-top: 5px solid white;
                }

                .react-flow__handle.connect-below {
                    border-bottom: 5px solid white;
                }
            `}</style>
        </div>
    );
}

// Helper functions

function getNodeLabel(type: string): string {
    const labels: Record<string, string> = {
        input: 'Entrada',
        prompt: 'Prompt',
        condition: 'Condición',
        action: 'Acción',
        output: 'Salida',
    };
    return labels[type] || type;
}

function layoutNodes(nodes: Node[], edges: Edge[]): Node[] {
    // Simple hierarchical layout
    const levels: Record<string, number> = {};
    const visited = new Set<string>();

    function assignLevel(nodeId: string, level: number) {
        if (visited.has(nodeId)) return;
        visited.add(nodeId);
        levels[nodeId] = Math.max(levels[nodeId] ?? 0, level);

        // Find children
        edges
            .filter((e) => e.source === nodeId)
            .forEach((e) => assignLevel(e.target, level + 1));
    }

    // Start from input nodes
    nodes.forEach((node) => {
        if (node.type === 'input') {
            assignLevel(node.id, 0);
        }
    });

    // Assign remaining nodes
    nodes.forEach((node) => {
        if (!visited.has(node.id)) {
            assignLevel(node.id, 0);
        }
    });

    // Position nodes
    const levelCounts: Record<number, number> = {};
    return nodes.map((node) => {
        const level = levels[node.id] ?? 0;
        const count = (levelCounts[level] ?? 0) + 1;
        levelCounts[level] = count;

        return {
            ...node,
            position: {
                x: level * 250,
                y: count * 100,
            },
        };
    });
}

function showNotification(message: string, type: 'success' | 'error' = 'success') {
    // Use browser notification or toast
    // This is a placeholder - you can integrate with a toast library
    if (type === 'success') {
        console.log('✓', message);
    } else {
        console.error('✗', message);
    }
}
