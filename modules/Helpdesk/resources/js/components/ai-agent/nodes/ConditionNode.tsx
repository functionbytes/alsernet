import React from 'react';
import { Handle, Position } from '@xyflow/react';

interface ConditionNodeData {
    label: string;
    condition?: string;
}

export default function ConditionNode({ data }: { data: ConditionNodeData }) {
    return (
        <div className="condition-node">
            <Handle type="target" position={Position.Top} />
            <div className="node-title">
                <i className="fa fa-code-branch"></i> {data.label}
            </div>
            {data.condition && (
                <div className="node-content">
                    <small className="code-text">{data.condition}</small>
                </div>
            )}
            <div className="node-handles">
                <Handle
                    type="source"
                    position={Position.Bottom}
                    id="true"
                    style={{ left: '30%' }}
                />
                <Handle
                    type="source"
                    position={Position.Bottom}
                    id="false"
                    style={{ left: '70%' }}
                />
            </div>
        </div>
    );
}
