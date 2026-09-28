import React from 'react';
import { hintStyle } from '../components/styles';

export default function DocumentLinkConfig() {
    return (
        <div>
            <p style={{ ...hintStyle, marginBottom: 8 }}>
                Resuelve el expediente de documentos de la conversación (módulo HelpdeskDocument) y deja
                disponibles estas variables para los mensajes siguientes:
            </p>
            <ul style={{ ...hintStyle, paddingLeft: 18, marginBottom: 8 }}>
                <li><code>{'{{doc_upload_url}}'}</code> — enlace seguro del portal para subir/consultar.</li>
                <li><code>{'{{doc_missing}}'}</code> — documentos que faltan por entregar.</li>
            </ul>
            <p style={hintStyle}>Coloca este nodo antes de un mensaje que use esas variables.</p>
        </div>
    );
}
