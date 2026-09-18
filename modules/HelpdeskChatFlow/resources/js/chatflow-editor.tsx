import React from 'react';
import { createRoot } from 'react-dom/client';
import '@xyflow/react/dist/style.css';
import ChatFlowEditor from './ChatFlowEditor';

const root = document.getElementById('chatflow-editor-root');
if (root) {
    const props = JSON.parse(root.dataset.props || '{}');
    createRoot(root).render(
        <React.StrictMode>
            <ChatFlowEditor {...props} />
        </React.StrictMode>
    );
}
