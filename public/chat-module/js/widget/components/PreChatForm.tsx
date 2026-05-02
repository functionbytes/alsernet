/**
 * PreChatForm - Capture customer information before starting chat
 */

import React, { useState } from 'react';
import type { Customer } from '../types';

interface PreChatFormProps {
    onSubmit: (data: Partial<Customer>) => void;
    color: string;
    welcomeTitle?: string;
    welcomeTagline?: string;
}

export function PreChatForm({
    onSubmit,
    color,
    welcomeTitle = 'Welcome!',
    welcomeTagline = 'How can we help you today?',
}: PreChatFormProps) {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone_number: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
        // Clear error when user starts typing
        if (errors[name]) {
            setErrors((prev) => ({ ...prev, [name]: '' }));
        }
    };

    const validate = (): boolean => {
        const newErrors: Record<string, string> = {};

        if (!formData.name.trim()) {
            newErrors.name = 'Name is required';
        }

        if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            newErrors.email = 'Invalid email address';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (validate()) {
            onSubmit(formData);
        }
    };

    return (
        <div className="flex flex-col h-full bg-white">
            {/* Header */}
            <div
                className="p-6 text-white"
                style={{ backgroundColor: color }}
            >
                <h2 className="text-xl font-bold mb-1">{welcomeTitle}</h2>
                <p className="text-sm opacity-90">{welcomeTagline}</p>
            </div>

            {/* Form */}
            <div className="flex-1 p-6 overflow-y-auto">
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label
                            htmlFor="name"
                            className="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Name <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value={formData.name}
                            onChange={handleChange}
                            className={`w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 ${
                                errors.name
                                    ? 'border-red-500 focus:ring-red-500'
                                    : 'border-gray-300 focus:ring-blue-500'
                            }`}
                            placeholder="Enter your name"
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-red-500">{errors.name}</p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="email"
                            className="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            className={`w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 ${
                                errors.email
                                    ? 'border-red-500 focus:ring-red-500'
                                    : 'border-gray-300 focus:ring-blue-500'
                            }`}
                            placeholder="your@email.com"
                        />
                        {errors.email && (
                            <p className="mt-1 text-sm text-red-500">{errors.email}</p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="phone_number"
                            className="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Phone
                        </label>
                        <input
                            type="tel"
                            id="phone_number"
                            name="phone_number"
                            value={formData.phone_number}
                            onChange={handleChange}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="+1 (555) 000-0000"
                        />
                    </div>

                    <button
                        type="submit"
                        className="w-full py-3 text-white font-medium rounded-lg transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style={{ backgroundColor: color }}
                    >
                        Start Chat
                    </button>
                </form>
            </div>
        </div>
    );
}
