import React from 'react';
import { Handle, Position } from '@xyflow/react';

interface OutputNodeData {
    label: string;
    description?: string;
}

export default function OutputNode({ data }: { data: OutputNodeData }) {
    return (
        <div className="output-node">
            <Handle type="target" position={Position.Top} />
            <div className="node-title">
                <i className="fa fa-arrow-up"></i> {data.label}
            </div>
            {data.description && (
                <div className="node-description">
                    <small>{data.description}</small>
                </div>
            )}
        </div>
    );
}
