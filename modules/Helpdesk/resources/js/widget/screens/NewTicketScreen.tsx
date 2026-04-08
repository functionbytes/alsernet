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
            const newFiles = Array.from(e.target.files);
            setFormData(prev => ({
                ...prev,
                attachments: [...prev.attachments, ...newFiles]
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

        // Simular envío de ticket (en PASO 6 esto será una llamada real a la API)
        setTimeout(() => {
            console.log('✅ Ticket creado:', formData);
            setIsSubmitting(false);

            // Mostrar mensaje de éxito
            alert('¡Ticket creado exitosamente! Te responderemos pronto.');

            // Redirigir a home
            navigate('/');
        }, 1000);
    };

    return (
        <div className="flex flex-col h-full bg-gray-50 opacity-0" style={{ animation: 'fadeIn 0.3s ease-out forwards' }}>
            {/* Header */}
            <div className="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 shadow-sm">
                <Link to="/" className="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors">
                    <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>

                <h1 className="flex-1 text-lg font-semibold text-gray-900">
                    Crear Ticket
                </h1>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-4 py-4">
                <div className="space-y-4">
                    {/* Subject */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                            Asunto *
                        </label>
                        <input
                            type="text"
                            value={formData.subject}
                            onChange={(e) => handleInputChange('subject', e.target.value)}
                            placeholder="Describe brevemente tu problema"
                            className="w-full px-4 py-2 bg-white text-gray-900 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all text-sm"
                            required
                        />
                    </div>

                    {/* Category */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                            Categoría
                        </label>
                        <select
                            value={formData.category}
                            onChange={(e) => handleInputChange('category', e.target.value)}
                            className="w-full px-4 py-2 bg-white text-gray-900 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all text-sm"
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
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                            Prioridad
                        </label>
                        <div className="grid grid-cols-3 gap-2">
                            {['low', 'normal', 'high'].map((priority) => (
                                <button
                                    key={priority}
                                    type="button"
                                    onClick={() => handleInputChange('priority', priority)}
                                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                                        formData.priority === priority
                                            ? 'bg-primary-600 text-white shadow-md'
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                    }`}
                                >
                                    {priority === 'low' ? 'Baja' : priority === 'normal' ? 'Normal' : 'Alta'}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Description */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                            Descripción *
                        </label>
                        <textarea
                            value={formData.description}
                            onChange={(e) => handleInputChange('description', e.target.value)}
                            placeholder="Describe tu problema con el mayor detalle posible..."
                            rows={5}
                            className="w-full px-4 py-2 bg-white text-gray-900 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all text-sm resize-none"
                            required
                        />
                    </div>

                    {/* Attachments */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">
                            Adjuntar Archivos
                        </label>

                        <input
                            type="file"
                            multiple
                            onChange={handleFileChange}
                            className="hidden"
                            id="ticket-file-upload"
                            accept="image/*,.pdf,.doc,.docx,.txt"
                        />

                        <label
                            htmlFor="ticket-file-upload"
                            className="flex items-center justify-center gap-2 px-4 py-3 bg-white border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                        >
                            <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span className="text-sm text-gray-600">
                                Haz click para adjuntar archivos
                            </span>
                        </label>

                        {/* Attached Files List */}
                        {formData.attachments.length > 0 && (
                            <div className="mt-3 space-y-2">
                                {formData.attachments.map((file, index) => (
                                    <div key={index} className="flex items-center justify-between px-3 py-2 bg-gray-100 rounded-lg">
                                        <div className="flex items-center gap-2 flex-1 min-w-0">
                                            <svg className="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span className="text-sm text-gray-700 truncate">
                                                {file.name}
                                            </span>
                                            <span className="text-xs text-gray-500 flex-shrink-0">
                                                ({(file.size / 1024).toFixed(1)} KB)
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => removeAttachment(index)}
                                            className="ml-2 p-1 hover:bg-gray-200 rounded transition-colors"
                                        >
                                            <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            {/* Submit Button */}
            <div className="px-4 py-3 bg-white border-t border-gray-200">
                <button
                    type="submit"
                    onClick={handleSubmit}
                    disabled={isSubmitting || !formData.subject.trim() || !formData.description.trim()}
                    className="w-full px-4 py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors flex items-center justify-center gap-2"
                >
                    {isSubmitting ? (
                        <>
                            <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creando ticket...
                        </>
                    ) : (
                        <>
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
