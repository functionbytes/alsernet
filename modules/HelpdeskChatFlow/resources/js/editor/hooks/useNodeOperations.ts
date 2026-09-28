import React, { useCallback, useEffect } from 'react';
import { BackendNode } from '../types';
import { NODE_LABELS } from '../constants';
import { nanoid } from '../utils';

// Node CRUD operations (add/delete/duplicate/branch) plus the `window.__chatflow*`
// wiring that lets the canvas nodes and node-config panels reach them without
// prop-drilling through ReactFlow's node data.
export function useNodeOperations(
    setBackendNodes: React.Dispatch<React.SetStateAction<BackendNode[]>>,
    setSelectedId: React.Dispatch<React.SetStateAction<string | null>>,
) {
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
    }, [setBackendNodes, setSelectedId]);

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
    }, [setBackendNodes, setSelectedId]);

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
    }, [setBackendNodes]);

    const addBranch = useCallback((branchesId: string) => {
        setBackendNodes(prev => [
            ...prev,
            { id: nanoid(), type: 'branchItem', parentId: branchesId, label: 'Nueva rama', data: { name: 'Nueva rama', isElse: false, conditions: [] } },
        ]);
    }, [setBackendNodes]);

    const updateBranchName = useCallback((itemId: string, name: string) => {
        setBackendNodes(prev => prev.map(n =>
            n.id === itemId ? { ...n, label: name, data: { ...n.data, name } } : n
        ));
    }, [setBackendNodes]);

    // Wire globals for sub-components (custom xyflow nodes, node config panels).
    useEffect(() => {
        window.__chatflowAddNode          = addNode;
        window.__chatflowDeleteNode       = deleteNode;
        window.__chatflowDuplicateNode    = duplicateNode;
        window.__chatflowAddBranch        = addBranch;
        window.__chatflowUpdateBranchName = updateBranchName;
    }, [addNode, deleteNode, duplicateNode, addBranch, updateBranchName]);

    return { addNode, deleteNode, duplicateNode, addBranch, updateBranchName };
}
