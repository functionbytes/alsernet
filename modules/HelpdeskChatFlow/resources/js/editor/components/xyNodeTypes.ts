import FlowNode from './FlowNode';
import AddStepNode from './AddStepNode';

// Registry passed as ReactFlow's `nodeTypes` prop (canvas node renderers).
export const xyNodeTypes = { flowNode: FlowNode, addStepNode: AddStepNode };
