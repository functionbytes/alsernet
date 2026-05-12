import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useWidgetStore } from '../widget-store';
import { apiUrl } from '../api';

type SubmitState = 'idle' | 'submitting' | 'done' | 'error';

export function PostChatFormScreen() {
    const settings  = useWidgetStore(state => state.settings);
    const navigate  = useNavigate();

    const [rating,      setRating]      = useState<number>(0);
    const [hovered,     setHovered]     = useState<number>(0);
    const [feedback,    setFeedback]    = useState('');
    const [submitState, setSubmitState] = useState<SubmitState>('idle');

    const conversationId = localStorage.getItem('livechat_conversation_id');
    const customerId     = localStorage.getItem('livechat_customer_id');
    const customerEmail  = localStorage.getItem('livechat_customer_email');

    const closeConversation = async (withRating: number, withFeedback: string) => {
        if (!conversationId) {
            navigate('/');
            return;
        }

        setSubmitState('submitting');

        try {
            const response = await fetch(apiUrl(`/hd/api/conversation/${conversationId}/close`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_id:    customerId ? parseInt(customerId, 10) : null,
                    customer_email: customerEmail ?? null,
                    rating:         withRating  || null,
                    feedback:       withFeedback.trim() || null,
                }),
            });

            const data = await response.json();

            if (data?.success) {
                localStorage.removeItem('livechat_conversation_id');
                setSubmitState('done');
            } else {
                setSubmitState('error');
            }
        } catch {
            setSubmitState('error');
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        closeConversation(rating, feedback);
    };

    const handleSkip = () => {
        closeConversation(0, '');
    };

    if (submitState === 'done') {
        return (
            <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
                <div className="wgt-flex-1" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '32px 24px', textAlign: 'center', gap: 16 }}>
                    <div
                        style={{
                            width: 64,
                            height: 64,
                            borderRadius: '50%',
                            backgroundColor: 'var(--wgt-success-light)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                        }}
                        aria-hidden="true"
                    >
                        <svg viewBox="0 0 24 24" fill="var(--wgt-success)" width={32} height={32}>
                            <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                        </svg>
                    </div>
                    <p className="wgt-fw-semibold" style={{ fontSize: 16 }}>¡Gracias por tu valoración!</p>
                    <p className="wgt-text-muted" style={{ fontSize: 13 }}>Tu conversación ha sido cerrada. Puedes iniciar una nueva cuando lo necesites.</p>
                    <button
                        type="button"
                        className="wgt-btn-primary"
                        style={{ backgroundColor: settings.primary_color, marginTop: 8 }}
                        onClick={() => navigate('/')}
                    >
                        Volver al inicio
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="wgt-stack-y wgt-h-full wgt-bg-light wgt-fade-in">
            {/* Header */}
            <div className="wgt-screen-header">
                <div className="wgt-flex-1 wgt-screen-header-title">
                    ¿Cómo fue tu experiencia?
                </div>
            </div>

            <form
                onSubmit={handleSubmit}
                className="wgt-flex-1 wgt-overflow-y wgt-px-4 wgt-py-4"
            >
                <div className="wgt-stack-y wgt-gap-4">
                    {/* Star rating */}
                    <div className="wgt-form-group" style={{ alignItems: 'center' }}>
                        <label className="wgt-label">Califica la conversación</label>
                        <div
                            role="group"
                            aria-label="Valoración de 1 a 5 estrellas"
                            style={{ display: 'flex', gap: 8, marginTop: 4 }}
                        >
                            {[1, 2, 3, 4, 5].map(star => {
                                const isActive = star <= (hovered || rating);
                                return (
                                    <button
                                        key={star}
                                        type="button"
                                        aria-label={`${star} estrella${star > 1 ? 's' : ''}`}
                                        aria-pressed={rating === star}
                                        onClick={() => setRating(star)}
                                        onMouseEnter={() => setHovered(star)}
                                        onMouseLeave={() => setHovered(0)}
                                        style={{
                                            background: 'none',
                                            border: 'none',
                                            cursor: 'pointer',
                                            padding: 2,
                                            fontSize: 28,
                                            color: isActive ? '#f59e0b' : 'var(--wgt-gray-300)',
                                            transition: 'color 0.15s ease, transform 0.1s ease',
                                            transform: isActive ? 'scale(1.15)' : 'scale(1)',
                                            lineHeight: 1,
                                        }}
                                    >
                                        {isActive ? '★' : '☆'}
                                    </button>
                                );
                            })}
                        </div>
                        {rating > 0 && (
                            <p style={{ fontSize: 12, color: 'var(--wgt-gray-600)', marginTop: 4 }}>
                                {['', 'Muy mala', 'Mala', 'Regular', 'Buena', 'Excelente'][rating]}
                            </p>
                        )}
                    </div>

                    {/* Feedback textarea */}
                    <div className="wgt-form-group">
                        <label className="wgt-label" htmlFor="post-chat-feedback">
                            Comentarios <span className="wgt-text-muted" style={{ fontWeight: 400 }}>(opcional)</span>
                        </label>
                        <textarea
                            id="post-chat-feedback"
                            className="wgt-textarea"
                            rows={4}
                            placeholder="Cuéntanos cómo podemos mejorar..."
                            value={feedback}
                            onChange={e => setFeedback(e.target.value)}
                        />
                    </div>

                    {submitState === 'error' && (
                        <p role="alert" style={{ fontSize: 13, color: 'var(--wgt-danger)', margin: 0 }}>
                            No se pudo enviar la valoración. Inténtalo de nuevo.
                        </p>
                    )}
                </div>
            </form>

            {/* Footer actions */}
            <div className="wgt-bg-white wgt-border-top wgt-px-4 wgt-py-3" style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                <button
                    type="submit"
                    disabled={submitState === 'submitting'}
                    className="wgt-btn-primary"
                    style={{ backgroundColor: settings.primary_color }}
                    onClick={handleSubmit}
                >
                    {submitState === 'submitting' ? (
                        <>
                            <svg viewBox="0 0 24 24" fill="none" width={16} height={16} style={{ animation: 'wgt-spin 0.8s linear infinite' }} aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeOpacity="0.3" />
                                <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                            </svg>
                            Enviando...
                        </>
                    ) : 'Enviar valoración'}
                </button>

                <button
                    type="button"
                    disabled={submitState === 'submitting'}
                    onClick={handleSkip}
                    style={{
                        background: 'none',
                        border: 'none',
                        cursor: 'pointer',
                        fontSize: 13,
                        color: 'var(--wgt-gray-500)',
                        padding: '6px 0',
                        textAlign: 'center',
                    }}
                >
                    Cerrar sin valorar
                </button>
            </div>
        </div>
    );
}
