import React from 'react';
import { Handle, Position } from '@xyflow/react';

interface InputNodeData {
    label: string;
    description?: string;
}

export default function InputNode({ data }: { data: InputNodeData }) {
    return (
        <div className="input-node">
            <div className="node-title">
                <i className="fa fa-arrow-down"></i> {data.label}
            </div>
            <div className="node-description">{data.description || 'Punto de entrada del flujo'}</div>
            <Handle type="source" position={Position.Bottom} />
        </div>
    );
}
