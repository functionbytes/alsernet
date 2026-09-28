import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

export default function RichMessageConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    return (
        <div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>URL de la imagen</label>
                <input type="text" className="form-control form-control-sm"
                    placeholder="https://.../imagen.jpg"
                    value={d.image_url || ''}
                    onChange={e => setData({ image_url: e.target.value })} />
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Título</label>
                <input type="text" className="form-control form-control-sm"
                    value={d.title || ''}
                    onChange={e => setData({ title: e.target.value })} />
            </div>
            <div style={{ marginBottom: 12 }}>
                <label style={labelStyle}>Texto</label>
                <textarea className="form-control form-control-sm" rows={2}
                    value={d.subtitle || ''}
                    onChange={e => setData({ subtitle: e.target.value })} />
            </div>
            <label style={labelStyle}>Opciones (botones)</label>
            {(d.options || []).map((opt: string, i: number) => (
                <div key={i} className="input-group input-group-sm mb-1">
                    <span className="input-group-text">{i + 1}</span>
                    <input type="text" className="form-control" value={opt}
                        onChange={e => {
                            const options = [...(d.options || [])];
                            options[i] = e.target.value;
                            setData({ options });
                        }} />
                    <button className="btn btn-outline-secondary"
                        onClick={() => setData({ options: (d.options || []).filter((_: any, j: number) => j !== i) })}>
                        <i className="fas fa-times" />
                    </button>
                </div>
            ))}
            <button className="btn btn-sm btn-outline-primary mt-1"
                onClick={() => setData({ options: [...(d.options || []), ''] })}>
                <i className="fas fa-plus me-1" />Opción
            </button>
            <p style={{ ...hintStyle, marginTop: 8 }}>Las opciones se muestran como lista numerada (1, 2, 3…) en todos los canales. Sin opciones, la tarjeta solo informa y continúa.</p>

            <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: 10, marginTop: 12 }}>
                <label style={labelStyle}>Tarjetas (carrusel de producto)</label>
                {(d.cards || []).map((card: any, i: number) => (
                    <div key={i} style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: 8, marginBottom: 8 }}>
                        <div className="d-flex justify-content-between align-items-center mb-1">
                            <strong style={{ fontSize: 12 }}>Tarjeta {i + 1}</strong>
                            <button className="btn btn-sm btn-link text-danger p-0"
                                onClick={() => setData({ cards: (d.cards || []).filter((_: any, j: number) => j !== i) })}>
                                <i className="fas fa-times" />
                            </button>
                        </div>
                        {[
                            { key: 'title', ph: 'Título' },
                            { key: 'subtitle', ph: 'Precio / subtítulo' },
                            { key: 'image_url', ph: 'URL de la imagen' },
                            { key: 'url', ph: 'Enlace (botón Ver)' },
                        ].map(f => (
                            <input key={f.key} type="text" className="form-control form-control-sm mb-1" placeholder={f.ph}
                                value={card[f.key] || ''}
                                onChange={e => {
                                    const cards = [...(d.cards || [])];
                                    cards[i] = { ...cards[i], [f.key]: e.target.value };
                                    setData({ cards });
                                }} />
                        ))}
                    </div>
                ))}
                <button className="btn btn-sm btn-outline-primary"
                    onClick={() => setData({ cards: [...(d.cards || []), {}] })}>
                    <i className="fas fa-plus me-1" />Tarjeta
                </button>
                <p style={{ ...hintStyle, marginTop: 8 }}>Con 2 o más tarjetas se envía como carrusel: nativo en Messenger, imágenes con texto en WhatsApp/Instagram, y lista numerada en web.</p>
            </div>
        </div>
    );
}
