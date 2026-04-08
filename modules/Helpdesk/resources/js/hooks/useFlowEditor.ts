import { useCallback, useState } from 'react';
import { Node, Edge, Connection, addEdge } from '@xyflow/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

interface UseFlowEditorProps {
    flowId: number;
    initialNodes: Node[];
    initialEdges: Edge[];
}

export function useFlowEditor({ flowId, initialNodes, initialEdges }: UseFlowEditorProps) {
    const queryClient = useQueryClient();
    const [selectedNode, setSelectedNode] = useState<Node | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    // Save flow structure mutation
    const saveFlowMutation = useMutation({
        mutationFn: (data: { nodes: Node[]; edges: Edge[] }) =>
            axios.put(`/manager/helpdesk/ai-agent/flows/${flowId}/structure`, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['flow', flowId] });
            showNotification('Flujo guardado correctamente', 'success');
            setIsSaving(false);
        },
        onError: (error: any) => {
            showNotification(
                'Error al guardar: ' + (error.response?.data?.message || error.message),
                'error'
            );
            setIsSaving(false);
        },
    });

    // Handle new connections
    const onConnect = useCallback((connection: Connection, setEdges: any) => {
        setEdges((eds: Edge[]) => addEdge(connection, eds));
    }, []);

    // Add new node
    const addNode = useCallback((type: string, setNodes: any) => {
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

        setNodes((nds: Node[]) => [...nds, newNode]);
    }, []);

    // Delete node
    const deleteNode = useCallback(
        (nodeId: string, setNodes: any, setEdges: any) => {
            setNodes((nds: Node[]) => nds.filter((n) => n.id !== nodeId));
            setEdges((eds: Edge[]) =>
                eds.filter((e) => e.source !== nodeId && e.target !== nodeId)
            );
            setSelectedNode(null);
        },
        []
    );

    // Update node data
    const updateNodeData = useCallback((nodeId: string, updates: any, setNodes: any) => {
        setNodes((nds: Node[]) =>
            nds.map((n) =>
                n.id === nodeId
                    ? {
                          ...n,
                          data: {
                              ...n.data,
                              ...updates,
                          },
                      }
                    : n
            )
        );

        if (selectedNode?.id === nodeId) {
            setSelectedNode({
                ...selectedNode,
                data: {
                    ...selectedNode.data,
                    ...updates,
                },
            });
        }
    }, [selectedNode]);

    // Save flow
    const saveFlow = useCallback(
        async (nodes: Node[], edges: Edge[]) => {
            if (nodes.length === 0) {
                showNotification('El flujo debe tener al menos un nodo', 'error');
                return;
            }

            setIsSaving(true);
            await saveFlowMutation.mutateAsync({ nodes, edges });
        },
        [saveFlowMutation]
    );

    // Auto-layout nodes
    const autoLayoutNodes = useCallback((nodes: Node[], edges: Edge[], setNodes: any) => {
        const layoutedNodes = layoutNodes(nodes, edges);
        setNodes(layoutedNodes);
    }, []);

    return {
        selectedNode,
        setSelectedNode,
        isSaving,
        onConnect,
        addNode,
        deleteNode,
        updateNodeData,
        saveFlow,
        autoLayoutNodes,
    };
}

// Helper function to get node label
export function getNodeLabel(type: string): string {
    const labels: Record<string, string> = {
        input: 'Entrada',
        prompt: 'Prompt',
        condition: 'Condición',
        action: 'Acción',
        output: 'Salida',
    };
    return labels[type] || type;
}

// Helper function for hierarchical layout
export function layoutNodes(nodes: Node[], edges: Edge[]): Node[] {
    const levels: Record<string, number> = {};
    const visited = new Set<string>();

    function assignLevel(nodeId: string, level: number) {
        if (visited.has(nodeId)) return;
        visited.add(nodeId);
        levels[nodeId] = Math.max(levels[nodeId] ?? 0, level);

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

// Helper function to show notifications
export function showNotification(message: string, type: 'success' | 'error' = 'success') {
    // Use browser notification or toast
    // This is a placeholder - you can integrate with a toast library
    if (type === 'success') {
        console.log('✓', message);
    } else {
        console.error('✗', message);
    }
}
