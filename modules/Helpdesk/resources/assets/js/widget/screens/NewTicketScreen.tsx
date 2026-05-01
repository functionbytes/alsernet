import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';

interface TicketFormData {
    subject: string;
    category: string;
    priority: string;
    description: string;
    attachments: File[];
}

export function NewTicketScreen() {
    const settings = useWidgetStore(state => state.settings);
    const navigate = useNavigate();

    const [formData, setFormData] = useState<TicketFormData>({
        subject: '',
        category: '',
        priority: 'normal',
        description: '',
        attachments: []
    });

    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (field: keyof TicketFormData, value: string) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setFormData(prev => ({
                ...prev,
                attachments: [...prev.attachments, ...Array.from(e.target.files!)]
            }));
        }
    };

    const removeAttachment = (index: number) => {
        setFormData(prev => ({
            ...prev,
            attachments: prev.attachments.filter((_, i) => i !== index)
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!formData.subject.trim() || !formData.description.trim()) {
            alert('Por favor completa el asunto y la descripción');
            return;
        }

        setIsSubmitting(true);

        setTimeout(() => {
            console.log('Ticket creado:', formData);
            setIsSubmitting(false);
            alert('¡Ticket creado exitosamente! Te responderemos pronto.');
            navigate('/');
        }, 1000);
    };

    const priorities = [
        { value: 'low', label: 'Baja' },
        { value: 'normal', label: 'Normal' },
        { value: 'high', label: 'Alta' },
    ];

    return (
        <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
            {/* Header */}
            <div className="wgt-screen-header wgt-gap-3">
                <Link to="/" className="wgt-icon-btn">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <span className="wgt-flex-1 wgt-screen-header-title">Crear Ticket</span>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmit} className="wgt-flex-1 wgt-overflow-y wgt-px-4 wgt-py-4">
                <div className="wgt-stack-y wgt-gap-4">
                    {/* Subject */}
                    <div className="wgt-form-group">
                        <label className="wgt-label">Asunto *</label>
                        <input
                            type="text"
                            value={formData.subject}
                            onChange={(e) => handleInputChange('subject', e.target.value)}
                            placeholder="Describe brevemente tu problema"
                            className="wgt-input"
                            required
                        />
                    </div>

                    {/* Category */}
                    <div className="wgt-form-group">
                        <label className="wgt-label">Categoría</label>
                        <select
                            value={formData.category}
                            onChange={(e) => handleInputChange('category', e.target.value)}
                            className="wgt-select"
                        >
                            <option value="">Selecciona una categoría</option>
                            <option value="technical">Soporte Técnico</option>
                            <option value="billing">Facturación</option>
                            <option value="general">Consulta General</option>
                            <option value="feature">Nueva Funcionalidad</option>
                            <option value="bug">Reportar Error</option>
                        </select>
                    </div>

                    {/* Priority */}
                    <div className="wgt-form-group">
                        <label className="wgt-label">Prioridad</label>
                        <div className="wgt-priority-grid">
                            {priorities.map(({ value, label }) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => handleInputChange('priority', value)}
                                    className={`wgt-priority-btn${formData.priority === value ? ' is-active' : ''}`}
                                    style={formData.priority === value ? { backgroundColor: settings.primary_color } : {}}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Description */}
                    <div className="wgt-form-group">
                        <label className="wgt-label">Descripción *</label>
                        <textarea
                            value={formData.description}
                            onChange={(e) => handleInputChange('description', e.target.value)}
                            placeholder="Describe tu problema con el mayor detalle posible..."
                            rows={5}
                            className="wgt-textarea"
                            required
                        />
                    </div>

                    {/* Attachments */}
                    <div className="wgt-form-group">
                        <label className="wgt-label">Adjuntar Archivos</label>

                        <input
                            type="file"
                            multiple
                            onChange={handleFileChange}
                            style={{ display: 'none' }}
                            id="ticket-file-upload"
                            accept="image/*,.pdf,.doc,.docx,.txt"
                        />

                        <label htmlFor="ticket-file-upload" className="wgt-file-drop">
                            <svg className="wgt-icon wgt-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span>Haz click para adjuntar archivos</span>
                        </label>

                        {formData.attachments.length > 0 && (
                            <div className="wgt-file-list wgt-mt-2">
                                {formData.attachments.map((file, index) => (
                                    <div key={index} className="wgt-file-chip">
                                        <div className="wgt-file-chip-info">
                                            <svg className="wgt-icon-sm wgt-shrink-0 wgt-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span className="wgt-text-secondary wgt-text-sm wgt-truncate">{file.name}</span>
                                            <span className="wgt-text-muted wgt-text-xs wgt-shrink-0">({(file.size / 1024).toFixed(1)} KB)</span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => removeAttachment(index)}
                                            className="wgt-file-chip-remove"
                                        >
                                            <svg className="wgt-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </form>

            {/* Submit */}
            <div className="wgt-bg-white wgt-border-top wgt-px-4 wgt-py-3">
                <button
                    type="submit"
                    onClick={handleSubmit}
                    disabled={isSubmitting || !formData.subject.trim() || !formData.description.trim()}
                    className="wgt-btn-primary"
                    style={{ backgroundColor: settings.primary_color }}
                >
                    {isSubmitting ? (
                        <>
                            <svg className="wgt-icon" fill="none" viewBox="0 0 24 24" style={{ animation: 'wgt-spin 0.8s linear infinite' }}>
                                <circle style={{ opacity: 0.25 }} cx="12" cy="12" r="10" stroke="white" strokeWidth="4" />
                                <path style={{ opacity: 0.75 }} fill="white" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Creando ticket...
                        </>
                    ) : (
                        <>
                            <svg className="wgt-icon" fill="none" stroke="white" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                            Crear Ticket
                        </>
                    )}
                </button>
            </div>
        </div>
    );
}
