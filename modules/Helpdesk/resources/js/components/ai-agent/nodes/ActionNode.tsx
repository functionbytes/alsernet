import React from 'react';
import { Handle, Position } from '@xyflow/react';

interface ActionNodeData {
    label: string;
    action?: string;
}

export default function ActionNode({ data }: { data: ActionNodeData }) {
    const getActionIcon = (action?: string) => {
        switch (action) {
            case 'email':
                return 'fa fa-envelope';
            case 'save':
                return 'fa fa-database';
            case 'webhook':
                return 'fa fa-code';
            case 'log':
            default:
                return 'fa fa-bolt';
        }
    };

    const getActionLabel = (action?: string) => {
        switch (action) {
            case 'email':
                return 'Enviar Email';
            case 'save':
                return 'Guardar Datos';
            case 'webhook':
                return 'Llamar Webhook';
            case 'log':
            default:
                return 'Registrar';
        }
    };

    return (
        <div className="action-node">
            <Handle type="target" position={Position.Top} />
            <div className="node-title">
                <i className={getActionIcon(data.action)}></i> {data.label}
            </div>
            {data.action && (
                <div className="node-content">
                    <small className="action-type">{getActionLabel(data.action)}</small>
                </div>
            )}
            <Handle type="source" position={Position.Bottom} />
        </div>
    );
}
