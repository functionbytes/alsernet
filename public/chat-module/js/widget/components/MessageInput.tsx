/**
 * MessageInput - Input field with file upload and send button
 */

import React, { useState, useRef, KeyboardEvent } from 'react';
import { validateFile } from '../utils/fileValidator';

interface MessageInputProps {
    onSendMessage: (content: string) => void;
    onUploadFile: (file: File) => void;
    onTyping: (isTyping: boolean) => void;
    color: string;
    disabled?: boolean;
}

export function MessageInput({
    onSendMessage,
    onUploadFile,
    onTyping,
    color,
    disabled = false,
}: MessageInputProps) {
    const [message, setMessage] = useState('');
    const [isUploading, setIsUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    const handleInputChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        const value = e.target.value;
        setMessage(value);

        // Send typing indicator
        onTyping(true);

        // Clear existing timeout
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        // Stop typing after 1 second of inactivity
        typingTimeoutRef.current = setTimeout(() => {
            onTyping(false);
        }, 1000);
    };

    const handleSend = () => {
        const trimmed = message.trim();
        if (!trimmed || disabled) return;

        onSendMessage(trimmed);
        setMessage('');
        onTyping(false);

        // Clear typing timeout
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }
    };

    const handleKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const validation = validateFile(file);
        if (!validation.valid) {
            alert(validation.error || 'Invalid file');
            return;
        }

        setIsUploading(true);
        try {
            await onUploadFile(file);
        } finally {
            setIsUploading(false);
            // Reset file input
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    return (
        <div className="border-t border-gray-200 p-4">
            <div className="flex items-end space-x-2">
                {/* File upload button */}
                <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    disabled={disabled || isUploading}
                    className="flex-shrink-0 w-10 h-10 flex items-center justify-center text-gray-500 hover:text-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    aria-label="Attach file"
                >
                    {isUploading ? (
                        <i className="fas fa-spinner fa-spin"></i>
                    ) : (
                        <i className="fas fa-paperclip text-lg"></i>
                    )}
                </button>

                <input
                    ref={fileInputRef}
                    type="file"
                    onChange={handleFileSelect}
                    className="hidden"
                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv"
                />

                {/* Message textarea */}
                <textarea
                    value={message}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                    disabled={disabled}
                    placeholder="Type a message..."
                    rows={1}
                    className="flex-1 resize-none px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-opacity-50 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    style={{
                        maxHeight: '120px',
                        minHeight: '40px',
                    }}
                />

                {/* Send button */}
                <button
                    type="button"
                    onClick={handleSend}
                    disabled={!message.trim() || disabled}
                    className="flex-shrink-0 w-10 h-10 rounded-full text-white flex items-center justify-center transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105"
                    style={{ backgroundColor: color }}
                    aria-label="Send message"
                >
                    <i className="fas fa-paper-plane"></i>
                </button>
            </div>

            <div className="mt-2 text-xs text-gray-500 text-center">
                Press Enter to send, Shift+Enter for new line
            </div>
        </div>
    );
}
