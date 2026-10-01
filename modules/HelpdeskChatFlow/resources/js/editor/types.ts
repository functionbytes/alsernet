// ─── Shared types for the ChatFlow editor ──────────────────────────────────────

export interface BackendNode {
    id: string;
    type: string;
    parentId: string | null;
    label: string;
    data: Record<string, any>;
}

// `value` is a string for most operators, a list for in / not_in and a
// [min, max] pair for between. is_empty / not_empty carry no value.
export interface Condition {
    variable: string;
    operator: string;
    value: string | string[];
}

export interface AssignOption { id: number; name: string; }

export interface ChatFlowEditorProps {
    chatFlowId: number;
    chatFlowName: string;
    chatFlowStatus: string;
    chatFlowTriggerType?: string;
    proceduresUrl?: string;
    actionsCatalogUrl?: string | null;
    nodes: BackendNode[];
    settings?: Record<string, any>;
    agents?: AssignOption[];
    groups?: AssignOption[];
    saveUrl: string;
    publishUrl: string;
    indexUrl: string;
    csrfToken: string;
}

export interface FlowIssue {
    level: 'error' | 'warning';
    message: string;
    nodeId?: string;
}

export interface FlowValidation {
    errors: FlowIssue[];
    warnings: FlowIssue[];
    byNode: Map<string, FlowIssue>;
}

// Props shared by every per-node-type configuration panel under `editor/nodes/`.
export interface NodeConfigProps {
    draft: BackendNode;
    allNodes: BackendNode[];
    agents: AssignOption[];
    groups: AssignOption[];
    setData: (patch: Record<string, any>) => void;
}

// ─── Global type augmentation ─────────────────────────────────────────────────
// Bridge used by node config panels / custom xyflow nodes to reach the editor's
// state mutators without prop-drilling through ReactFlow's node data.

declare global {
    interface Window {
        __chatflowUrls?:              { flowId: number; procedures?: string; actionsCatalog?: string | null };
        __chatflowNodes?:             BackendNode[];
        __chatflowAddNode?:           (type: string, parentId: string) => void;
        __chatflowDeleteNode?:        (id: string) => void;
        __chatflowDuplicateNode?:     (id: string) => void;
        __chatflowAddBranch?:         (branchesId: string) => void;
        __chatflowUpdateBranchName?:  (itemId: string, name: string) => void;
    }
}
