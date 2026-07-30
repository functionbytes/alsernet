import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { apiUrl, conversationAuthHeaders } from '../api';
import { setVisitorIdentity } from '../widget-identity';

type PreChatOption = string | { value: string; label: string };

interface PreChatField {
    key: string;
    label: string;
    type: 'text' | 'email' | 'phone' | 'tel' | 'select' | 'textarea';
    required: boolean;
    options?: PreChatOption[];
    placeholder?: string;
}

interface PreChatFormConfig {
    id: number;
    name: string;
    fields: PreChatField[];
}

const DEFAULT_FIELDS: PreChatField[] = [
    { key: 'name',  label: 'Nombre', type: 'text',  required: true,  placeholder: 'Tu nombre' },
    { key: 'email', label: 'Email',  type: 'email', required: true,  placeholder: 'tu@email.com' },
];

function validateEmail(value: string): boolean {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

export function PreChatFormScreen() {
    const settings    = useWidgetStore(state => state.settings);
    const prefillData = useWidgetStore(state => state.prefillData);
    const navigate    = useNavigate();

    const [formConfig,   setFormConfig]   = useState<PreChatFormConfig | null>(null);
    const [fields,       setFields]       = useState<PreChatField[]>(DEFAULT_FIELDS);
    const [values,       setValues]       = useState<Record<string, string>>({});
    const [errors,       setErrors]       = useState<Record<string, string>>({});
    const [isLoading,    setIsLoading]    = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitError,  setSubmitError]  = useState('');

    // Apply prefill data from Engagement triggers
    useEffect(() => {
        if (!prefillData) return;
        setValues(prev => ({ ...prev, ...prefillData }));
    }, [prefillData]);

    // Fetch form config from backend
    useEffect(() => {
        let cancelled = false;
        const inboxId = (window as any).HELPDESK_WIDGET_CONFIG?.inboxId;
        const url = inboxId
            ? apiUrl(`/api/v1/helpdesk-livechat/pre-chat-form?inbox_id=${inboxId}`)
            : apiUrl('/api/v1/helpdesk-livechat/pre-chat-form');

        fetch(url)
            .then(r => r.ok ? r.json() : null)
            .then((data: { form: PreChatFormConfig | null } | null) => {
                if (cancelled) return;
                if (data?.form?.fields?.length) {
                    setFormConfig(data.form);
                    setFields(data.form.fields);
                }
            })
            .catch(() => { /* keep defaults */ })
            .finally(() => { if (!cancelled) setIsLoading(false); });

        return () => { cancelled = true; };
    }, []);

    const validate = (): boolean => {
        const next: Record<string, string> = {};

        for (const field of fields) {
            const val = (values[field.key] ?? '').trim();

            if (field.required && !val) {
                next[field.key] = `${field.label} es obligatorio`;
                continue;
            }

            if (field.type === 'email' && val && !validateEmail(val)) {
                next[field.key] = 'Ingresa un email válido';
            }
        }

        setErrors(next);
        return Object.keys(next).length === 0;
    };

    const handleChange = (key: string, value: string) => {
        setValues(prev => ({ ...prev, [key]: value }));
        if (errors[key]) {
            setErrors(prev => ({ ...prev, [key]: '' }));
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!validate()) return;

        setIsSubmitting(true);
        setSubmitError('');

        // Persist identity to localStorage so ConversationScreen picks it up
        const identityData: Record<string, string> = {};
        for (const field of fields) {
            const val = (values[field.key] ?? '').trim();
            if (val) identityData[field.key] = val;
        }

        setVisitorIdentity({
            name:  identityData['name']  ?? undefined,
            email: identityData['email'] ?? undefined,
            phone: identityData['phone'] ?? undefined,
        });

        // If there's no conversation yet, navigate directly to start one.
        // The submit endpoint requires a conversation_id which only exists
        // after the conversation is created — we save the form values to
        // localStorage and let ConversationScreen send them when the first
        // message creates the conversation.
        const pendingPreChat = Object.fromEntries(
            fields.map(f => [f.key, (values[f.key] ?? '').trim()])
        );
        try {
            localStorage.setItem('livechat_pre_chat_data', JSON.stringify(pendingPreChat));
        } catch { /* ignore */ }

        // Submit to backend if a conversation_id is already known
        const conversationId = localStorage.getItem('livechat_conversation_id');
        if (conversationId && formConfig) {
            try {
                await fetch(apiUrl('/api/v1/helpdesk-livechat/pre-chat-form'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', ...conversationAuthHeaders() },
                    body: JSON.stringify({
                        conversation_id: parseInt(conversationId, 10),
                        data: pendingPreChat,
                    }),
                });
            } catch { /* non-blocking — data already in localStorage */ }
        }

        setIsSubmitting(false);
        navigate('/conversation');
    };

    return (
        <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
            {/* Header */}
            <div className="wgt-screen-header">
                <Link to="/" className="wgt-icon-btn" aria-label="Volver">
                    <svg className="wgt-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <span className="wgt-flex-1 wgt-screen-header-title">Antes de empezar</span>
            </div>

            {isLoading ? (
                <div className="wgt-flex-1 wgt-row-center" style={{ justifyContent: 'center' }}>
                    <div className="wgt-spinner" aria-label="Cargando" />
                </div>
            ) : (
                <form
                    onSubmit={handleSubmit}
                    noValidate
                    className="wgt-flex-1 wgt-overflow-y wgt-px-4 wgt-py-4"
                >
                    <div className="wgt-stack-y wgt-gap-4">
                        <p className="wgt-text-muted" style={{ fontSize: 13 }}>
                            Completa los datos para que podamos atenderte mejor.
                        </p>

                        {fields.map(field => (
                            <FieldInput
                                key={field.key}
                                field={field}
                                value={values[field.key] ?? ''}
                                error={errors[field.key] ?? ''}
                                onChange={handleChange}
                            />
                        ))}

                        {submitError && (
                            <p role="alert" style={{ color: 'var(--wgt-danger)', fontSize: 13 }}>
                                {submitError}
                            </p>
                        )}
                    </div>

                    <div className="wgt-bg-white wgt-border-top wgt-px-4 wgt-py-3" style={{ marginTop: 24 }}>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="wgt-btn-primary"
                            style={{ backgroundColor: settings.primary_color }}
                        >
                            {isSubmitting ? (
                                <>
                                    <svg viewBox="0 0 24 24" fill="none" width={16} height={16} style={{ animation: 'wgt-spin 0.8s linear infinite' }} aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeOpacity="0.3" />
                                        <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                                    </svg>
                                    Iniciando...
                                </>
                            ) : 'Iniciar conversación'}
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}

interface FieldInputProps {
    field: PreChatField;
    value: string;
    error: string;
    onChange: (key: string, value: string) => void;
}

function FieldInput({ field, value, error, onChange }: FieldInputProps) {
    const inputStyle = error ? { borderColor: 'var(--wgt-danger)' } : {};

    return (
        <div className="wgt-form-group">
            <label className="wgt-label" htmlFor={`pre-chat-${field.key}`}>
                {field.label}
                {field.required && <span aria-hidden="true" style={{ color: 'var(--wgt-danger)', marginLeft: 2 }}>*</span>}
            </label>

            {field.type === 'textarea' ? (
                <textarea
                    id={`pre-chat-${field.key}`}
                    className="wgt-textarea"
                    style={inputStyle}
                    value={value}
                    placeholder={field.placeholder ?? ''}
                    rows={3}
                    onChange={e => onChange(field.key, e.target.value)}
                    aria-required={field.required}
                    aria-invalid={!!error}
                    aria-describedby={error ? `pre-chat-${field.key}-err` : undefined}
                />
            ) : field.type === 'select' && field.options ? (
                <select
                    id={`pre-chat-${field.key}`}
                    className="wgt-select"
                    style={inputStyle}
                    value={value}
                    onChange={e => onChange(field.key, e.target.value)}
                    aria-required={field.required}
                    aria-invalid={!!error}
                    aria-describedby={error ? `pre-chat-${field.key}-err` : undefined}
                >
                    <option value="">Selecciona una opción</option>
                    {field.options.map(opt => {
                        const v = typeof opt === 'string' ? opt : opt.value;
                        const l = typeof opt === 'string' ? opt : opt.label;
                        return <option key={v} value={v}>{l}</option>;
                    })}
                </select>
            ) : (
                <input
                    id={`pre-chat-${field.key}`}
                    className="wgt-input"
                    style={inputStyle}
                    type={field.type === 'phone' ? 'tel' : field.type}
                    value={value}
                    placeholder={field.placeholder ?? ''}
                    onChange={e => onChange(field.key, e.target.value)}
                    aria-required={field.required}
                    aria-invalid={!!error}
                    aria-describedby={error ? `pre-chat-${field.key}-err` : undefined}
                />
            )}

            {error && (
                <p id={`pre-chat-${field.key}-err`} role="alert" style={{ fontSize: 12, color: 'var(--wgt-danger)', margin: 0 }}>
                    {error}
                </p>
            )}
        </div>
    );
}
