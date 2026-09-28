import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function OrderLookupConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Variable con el nº de pedido</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="numero_pedido"
                    value={d.order_variable || ''}
                    onChange={e => setData({ order_variable: e.target.value })} />
                <p style={hintStyle}>El cliente debe haberse identificado y dado el nº de pedido antes.</p>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Fuente</label>
                <select className="form-select form-select-sm"
                    value={d.source || 'auto'}
                    onChange={e => setData({ source: e.target.value })}>
                    <option value="auto">Automática (ERP y PrestaShop)</option>
                    <option value="erp">Solo ERP</option>
                    <option value="ps">Solo PrestaShop</option>
                </select>
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Mensaje si se encuentra</label>
                <textarea className="form-control form-control-sm" rows={3}
                    value={d.found_message || ''}
                    placeholder="Déjalo vacío para usar el formato automático. Variables: {{order_status}}, {{order_total}}, {{order_tracking}}"
                    onChange={e => setData({ found_message: e.target.value })} />
            </div>
            <label style={labelStyle}>Mensaje si NO se encuentra</label>
            <textarea className="form-control form-control-sm" rows={2}
                value={d.not_found_message || ''}
                placeholder="No he encontrado ese pedido asociado a tu cuenta."
                onChange={e => setData({ not_found_message: e.target.value })} />
            <p style={hintStyle}>Guarda en contexto: order_found, order_status, order_total, order_tracking.</p>
        </div>
    );
}
