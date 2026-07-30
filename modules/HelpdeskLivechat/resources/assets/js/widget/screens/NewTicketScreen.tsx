import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { apiUrl } from '../api';

interface FieldOption {
    label: string;
    value: string;
}

interface CustomField {
    id: number;
    key: string;
    type: 'text' | 'textarea' | 'email' | 'phone' | 'number' | 'select' | 'radio' | 'checkbox' | 'date' | 'file';
    label: string;
    placeholder?: string;
    help_text?: string;
    is_required: boolean;
    width: 'full' | 'half' | 'third';
    options?: FieldOption[];
}

interface Category {
    id: number;
    name: string;
    fields?: CustomField[];
    required_fields?: string[];
}

interface TicketFormData {
    subject: string;
    category_id: string;
    priority: string;
    description: string;
    customer_email: string;
    customer_name: string;
    attachments: File[];
    custom_fields: Record<string, string>;
}

export function NewTicketScreen() {
    const settings = useWidgetStore(state => state.settings);
    const navigate = useNavigate();

    const [categories, setCategories] = useState<Category[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<Category | null>(null);
    const [deflectionArticles, setDeflectionArticles] = useState<{ id: number; title: string }[]>([]);
    const [deflectionDismissed, setDeflectionDismissed] = useState(false);
    const deflectionTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const [formData, setFormData] = useState<TicketFormData>({
        subject: '',
        category_id: '',
        priority: 'normal',
        description: '',
        customer_email: localStorage.getItem('livechat_customer_email') || '',
        customer_name: localStorage.getItem('livechat_customer_name') || '',
        attachments: [],
        custom_fields: {},
    });

    const [customFieldFiles, setCustomFieldFiles] = useState<Record<string, File>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [success, setSuccess] = useState<{ ticket_number: string } | null>(null);

    useEffect(() => {
        let cancelled = false;
        fetch(apiUrl('/hd/api/tickets/categories'))
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (cancelled || !data?.success) { return; }
                setCategories(data.data || []);
            })
            .catch(() => { /* silently fall back to empty list */ });
        return () => { cancelled = true; };
    }, []);

    const getWebsiteToken = (): string | null =>
        (window as any).HELPDESK_WIDGET_CONFIG?.websiteToken
        ?? (window as any).helpdeskSettings?.websiteToken
        ?? new URLSearchParams(window.location.search).get('website_token');

    const handleInputChange = (field: keyof TicketFormData, value: string) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => {
                const next = { ...prev };
                delete next[field];
                return next;
            });
        }
    };

    const handleSubjectChange = (value: string) => {
        handleInputChange('subject', value);

        if (deflectionTimer.current) {
            clearTimeout(deflectionTimer.current);
        }

        if (deflectionDismissed || value.length <= 4) {
            return;
        }

        deflectionTimer.current = setTimeout(() => {
            const token = getWebsiteToken();
            if (!token) { return; }

            fetch(apiUrl(`/hd/api/helpcenter/search?q=${encodeURIComponent(value)}&website_token=${encodeURIComponent(token)}`))
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) { return; }
                    const articles = data.articles ?? data.data ?? [];
                    setDeflectionArticles(articles.slice(0, 3));
                })
                .catch(() => { /* silently ignore */ });
        }, 600);
    };

    const handleCategoryChange = (value: string) => {
        handleInputChange('category_id', value);
        const category = categories.find(c => String(c.id) === value) ?? null;
        setSelectedCategory(category);
        setFormData(prev => ({ ...prev, custom_fields: {} }));
        setCustomFieldFiles({});
    };

    const handleCustomFieldChange = (name: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            custom_fields: { ...prev.custom_fields, [name]: value },
        }));
    };

    const handleCustomFieldFileChange = (key: string, file: File | undefined) => {
        if (!file) { return; }
        setCustomFieldFiles(prev => ({ ...prev, [key]: file }));
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setFormData(prev => ({
                ...prev,
                attachments: [...prev.attachments, ...Array.from(e.target.files!)],
            }));
        }
    };

    const removeAttachment = (index: number) => {
        setFormData(prev => ({
            ...prev,
            attachments: prev.attachments.filter((_, i) => i !== index),
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});

        if (!formData.subject.trim() || !formData.description.trim() || !formData.customer_email.trim()) {
            setErrors({ form: 'Por favor completa los campos obligatorios.' });
            return;
        }

        const requiredCustomFields = (selectedCategory?.fields ?? []).filter(f => f.is_required);
        for (const field of requiredCustomFields) {
            if (!formData.custom_fields[field.key]?.trim()) {
                setErrors({ form: 'Por favor completa todos los campos obligatorios.' });
                return;
            }
        }

        const websiteToken = getWebsiteToken();
        if (!websiteToken) {
            setErrors({ form: 'Token de widget no disponible.' });
            return;
        }

        setIsSubmitting(true);

        const payload = new FormData();
        payload.append('website_token', websiteToken);
        payload.append('subject', formData.subject);
        payload.append('description', formData.description);
        payload.append('priority', formData.priority);
        payload.append('customer_email', formData.customer_email);
        if (formData.customer_name) { payload.append('customer_name', formData.customer_name); }
        if (formData.category_id) { payload.append('category_id', formData.category_id); }
        const serializableCustomFields = Object.fromEntries(
            Object.entries(formData.custom_fields).filter(([key]) => !(key in customFieldFiles))
        );
        if (Object.keys(serializableCustomFields).length > 0) {
            payload.append('custom_fields', JSON.stringify(serializableCustomFields));
        }
        formData.attachments.forEach(file => payload.append('attachments[]', file));
        Object.values(customFieldFiles).forEach(file => payload.append('attachments[]', file));

        try {
            const response = await fetch(apiUrl('/hd/api/tickets'), {
                method: 'POST',
                body: payload,
                headers: { Accept: 'application/json' },
            });

            const data = await response.json();

            if (!response.ok || !data?.success) {
                if (data?.errors) {
                    const fieldErrors: Record<string, string> = {};
                    Object.entries(data.errors as Record<string, string[]>).forEach(([field, messages]) => {
                        fieldErrors[field] = messages[0];
                    });
                    setErrors(fieldErrors);
                } else {
                    setErrors({ form: data?.message || 'No se pudo crear el ticket.' });
                }
                return;
            }

            localStorage.setItem('livechat_customer_email', formData.customer_email);
            if (formData.customer_name) { localStorage.setItem('livechat_customer_name', formData.customer_name); }

            setSuccess({ ticket_number: data.data.ticket_number });
        } catch {
            setErrors({ form: 'Error de conexión. Inténtalo de nuevo.' });
        } finally {
            setIsSubmitting(false);
        }
    };

    const priorities = [
        { value: 'low', label: 'Baja' },
        { value: 'normal', label: 'Normal' },
        { value: 'high', label: 'Alta' },
    ];

    if (success) {
        return (
            <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
                <div className="wgt-screen-header wgt-gap-3">
                    <Link to="/" className="wgt-icon-btn">
                        <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <span className="wgt-flex-1 wgt-screen-header-title">Ticket creado</span>
                </div>
                <div className="wgt-flex-1 wgt-overflow-y wgt-px-4 wgt-py-4 wgt-stack-y wgt-gap-3 wgt-align-center wgt-text-center">
                    <svg viewBox="0 0 24 24" fill="none" stroke={settings.primary_color} strokeWidth={2} style={{ width: 64, height: 64 }}>
                        <circle cx="12" cy="12" r="10" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
                    </svg>
                    <h3 style={{ margin: 0 }}>¡Ticket creado!</h3>
                    <p style={{ color: '#666', margin: 0 }}>
                        Tu ticket <strong>{success.ticket_number}</strong> ha sido recibido.
                        Te responderemos por correo lo antes posible.
                    </p>
                    <button
                        type="button"
                        onClick={() => navigate('/')}
                        className="wgt-btn-primary"
                        style={{ backgroundColor: settings.primary_color, marginTop: 16 }}
                    >
                        Volver al inicio
                    </button>
                </div>
            </div>
        );
    }

    const customFields = selectedCategory?.fields ?? [];
    const showDeflection = deflectionArticles.length > 0 && !deflectionDismissed;

    return (
        <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
            <div className="wgt-screen-header wgt-gap-3">
                <Link to="/" className="wgt-icon-btn">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <span className="wgt-flex-1 wgt-screen-header-title">Crear Ticket</span>
            </div>

            <form onSubmit={handleSubmit} className="wgt-flex-1 wgt-overflow-y wgt-px-4 wgt-py-4">
                <div className="wgt-stack-y wgt-gap-4">
                    {errors.form && (
                        <div className="wgt-form-error" role="alert">
                            {errors.form}
                        </div>
                    )}

                    <div className="wgt-form-group">
                        <label className="wgt-label">Nombre</label>
                        <input
                            type="text"
                            value={formData.customer_name}
                            onChange={(e) => handleInputChange('customer_name', e.target.value)}
                            placeholder="Tu nombre"
                            className="wgt-input"
                        />
                        {errors.customer_name && <small className="wgt-text-danger">{errors.customer_name}</small>}
                    </div>

                    <div className="wgt-form-group">
                        <label className="wgt-label">Correo electrónico *</label>
                        <input
                            type="email"
                            value={formData.customer_email}
                            onChange={(e) => handleInputChange('customer_email', e.target.value)}
                            placeholder="tu@email.com"
                            className="wgt-input"
                            required
                        />
                        {errors.customer_email && <small className="wgt-text-danger">{errors.customer_email}</small>}
                    </div>

                    <div className="wgt-form-group">
                        <label className="wgt-label">Asunto *</label>
                        <input
                            type="text"
                            value={formData.subject}
                            onChange={(e) => handleSubjectChange(e.target.value)}
                            placeholder="Describe brevemente tu problema"
                            className="wgt-input"
                            required
                        />
                        {errors.subject && <small className="wgt-text-danger">{errors.subject}</small>}
                    </div>

                    <div className="wgt-form-group">
                        <label className="wgt-label">Categoría</label>
                        <select
                            value={formData.category_id}
                            onChange={(e) => handleCategoryChange(e.target.value)}
                            className="wgt-select"
                        >
                            <option value="">Selecciona una categoría</option>
                            {categories.map(c => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
                    </div>

                    {customFields.map(field => {
                        const isRequired = field.is_required;
                        const value = formData.custom_fields[field.key] ?? '';

                        return (
                            <div key={field.key} className="wgt-form-group">
                                <label className="wgt-label">
                                    {field.label}{isRequired && ' *'}
                                </label>

                                {(field.type === 'text' || field.type === 'email' || field.type === 'phone' || field.type === 'number') && (
                                    <input
                                        type={field.type === 'phone' ? 'tel' : field.type}
                                        value={value}
                                        onChange={(e) => handleCustomFieldChange(field.key, e.target.value)}
                                        placeholder={field.placeholder}
                                        className="wgt-input"
                                        required={isRequired}
                                    />
                                )}

                                {field.type === 'textarea' && (
                                    <textarea
                                        value={value}
                                        onChange={(e) => handleCustomFieldChange(field.key, e.target.value)}
                                        placeholder={field.placeholder}
                                        className="wgt-textarea"
                                        required={isRequired}
                                    />
                                )}

                                {field.type === 'date' && (
                                    <input
                                        type="date"
                                        value={value}
                                        onChange={(e) => handleCustomFieldChange(field.key, e.target.value)}
                                        className="wgt-input"
                                        required={isRequired}
                                    />
                                )}

                                {field.type === 'select' && (
                                    <select
                                        value={value}
                                        onChange={(e) => handleCustomFieldChange(field.key, e.target.value)}
                                        className="wgt-select"
                                        required={isRequired}
                                    >
                                        <option value="">Selecciona...</option>
                                        {(field.options ?? []).map(o => (
                                            <option key={o.value} value={o.value}>{o.label}</option>
                                        ))}
                                    </select>
                                )}

                                {field.type === 'radio' && (
                                    <div className="wgt-stack-y wgt-gap-1">
                                        {(field.options ?? []).map(o => (
                                            <label key={o.value} className="wgt-checkbox-label">
                                                <input
                                                    type="radio"
                                                    name={`field_${field.key}`}
                                                    value={o.value}
                                                    checked={value === o.value}
                                                    onChange={() => handleCustomFieldChange(field.key, o.value)}
                                                    required={isRequired}
                                                />
                                                <span>{o.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                )}

                                {field.type === 'checkbox' && (
                                    <label className="wgt-checkbox-label">
                                        <input
                                            type="checkbox"
                                            checked={value === '1'}
                                            onChange={(e) => handleCustomFieldChange(field.key, e.target.checked ? '1' : '')}
                                            required={isRequired}
                                        />
                                        <span>{field.placeholder || field.label}</span>
                                    </label>
                                )}

                                {field.type === 'file' && (
                                    <input
                                        type="file"
                                        onChange={(e) => handleCustomFieldFileChange(field.key, e.target.files?.[0])}
                                        className="wgt-input"
                                        required={isRequired}
                                    />
                                )}

                                {field.help_text && (
                                    <small style={{ color: '#888', marginTop: 2, display: 'block' }}>{field.help_text}</small>
                                )}
                            </div>
                        );
                    })}

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
                        {errors.description && <small className="wgt-text-danger">{errors.description}</small>}
                    </div>

                    {showDeflection && (
                        <div className="wgt-deflection-box">
                            <div className="wgt-deflection-header">
                                <span>¿Encontraste lo que buscas?</span>
                                <button
                                    type="button"
                                    onClick={() => setDeflectionDismissed(true)}
                                    className="wgt-icon-btn"
                                    aria-label="Cerrar sugerencias"
                                >
                                    <svg className="wgt-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            {deflectionArticles.map(a => (
                                <Link key={a.id} to={`/help/article/${a.id}`} className="wgt-deflection-link">
                                    {a.title}
                                </Link>
                            ))}
                        </div>
                    )}

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

            <div className="wgt-bg-white wgt-border-top wgt-px-4 wgt-py-3">
                <button
                    type="submit"
                    onClick={handleSubmit}
                    disabled={isSubmitting || !formData.subject.trim() || !formData.description.trim() || !formData.customer_email.trim()}
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
