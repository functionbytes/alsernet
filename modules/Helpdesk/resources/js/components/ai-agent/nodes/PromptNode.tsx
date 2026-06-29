import React from 'react';
import { Handle, Position } from '@xyflow/react';

interface PromptNodeData {
    label: string;
    instructions?: string;
}

export default function PromptNode({ data }: { data: PromptNodeData }) {
    return (
        <div className="prompt-node">
            <Handle type="target" position={Position.Top} />
            <div className="node-title">
                <i className="fa fa-message"></i> {data.label}
            </div>
            {data.instructions && (
                <div className="node-content">
                    <small>{data.instructions.substring(0, 100)}...</small>
                </div>
            )}
            <Handle type="source" position={Position.Bottom} />
        </div>
    );
}
