import React from 'react';
import { EmojiPicker } from './EmojiPicker';

function buildAcceptAttr(types: string[]): string {
    const map: Record<string, string> = {
        images: 'image/*',
        documents: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt',
        video: 'video/*',
        audio: 'audio/*',
    };
    const parts = types.flatMap(t => (map[t] ? [map[t]] : []));
    return parts.length > 0 ? parts.join(',') : '*/*';
}

interface MessageComposerProps {
    inputValue: string;
    onInputChange: (value: string) => void;
    onKeyPress: (e: React.KeyboardEvent) => void;
    onSend: () => void;
    isSending: boolean;
    attachedFiles: File[];
    onFileSelect: (e: React.ChangeEvent<HTMLInputElement>) => void;
    onRemoveAttachment: (index: number) => void;
    showEmojiPicker: boolean;
    onToggleEmojiPicker: () => void;
    onEmojiSelect: (emoji: string) => void;
    inputRef: React.RefObject<HTMLInputElement | null>;
    fileInputRef: React.RefObject<HTMLInputElement | null>;
    primaryColor?: string;
    placeholder?: string;
    enableEmoji?: boolean;
    enableFileUpload?: boolean;
    allowedFileTypes?: string[];
}

export function MessageComposer({
    inputValue,
    onInputChange,
    onKeyPress,
    onSend,
    isSending,
    attachedFiles,
    onFileSelect,
    onRemoveAttachment,
    showEmojiPicker,
    onToggleEmojiPicker,
    onEmojiSelect,
    inputRef,
    fileInputRef,
    primaryColor,
    placeholder,
    enableEmoji,
    enableFileUpload,
    allowedFileTypes,
}: MessageComposerProps) {
    return (
        <div className="wgt-composer">
            {attachedFiles.length > 0 && (
                <div className="wgt-composer-files">
                    {attachedFiles.map((file, index) => (
                        <div key={index} className="wgt-file-chip">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M16.5 6v11.5a4 4 0 1 1-8 0V5a2.5 2.5 0 0 1 5 0v10.5a1 1 0 0 1-2 0V6H10v9.5a2.5 2.5 0 0 0 5 0V5a4 4 0 0 0-8 0v12.5a5.5 5.5 0 0 0 11 0V6h-1.5z" />
                            </svg>
                            <span className="wgt-file-chip-name">{file.name}</span>
                            <button
                                type="button"
                                onClick={() => onRemoveAttachment(index)}
                                className="wgt-file-chip-x"
                                aria-label="Remove"
                            >
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z" />
                                </svg>
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <div className="wgt-composer-bar">
                {enableFileUpload !== false && (
                    <input
                        ref={fileInputRef}
                        type="file"
                        multiple
                        onChange={onFileSelect}
                        className="wgt-hidden"
                        accept={buildAcceptAttr(allowedFileTypes ?? ['images', 'documents'])}
                    />
                )}

                <input
                    ref={inputRef}
                    type="text"
                    value={inputValue}
                    onChange={(e) => onInputChange(e.target.value)}
                    onKeyPress={onKeyPress}
                    placeholder={placeholder || 'Enter your message here…'}
                    disabled={isSending}
                    className="wgt-composer-input"
                />

                {enableEmoji !== false && (
                    <div className="wgt-composer-icon-wrap">
                        <button
                            type="button"
                            className="wgt-composer-icon wgt-tip"
                            data-tooltip="Emoji"
                            aria-label="Emoji"
                            onClick={onToggleEmojiPicker}
                        >
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-3.5-9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm-3.5 5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" />
                            </svg>
                        </button>
                        {showEmojiPicker && (
                            <EmojiPicker
                                onSelect={(emoji) => {
                                    onEmojiSelect(emoji);
                                    inputRef.current?.focus();
                                }}
                                onClose={onToggleEmojiPicker}
                            />
                        )}
                    </div>
                )}

                {enableFileUpload !== false && (
                    <button
                        type="button"
                        className="wgt-composer-icon wgt-tip"
                        data-tooltip="Attach file"
                        aria-label="Attach file"
                        onClick={() => fileInputRef.current?.click()}
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M16.5 6v11.5a4 4 0 1 1-8 0V5a2.5 2.5 0 0 1 5 0v10.5a1 1 0 0 1-2 0V6H10v9.5a2.5 2.5 0 0 0 5 0V5a4 4 0 0 0-8 0v12.5a5.5 5.5 0 0 0 11 0V6h-1.5z" />
                        </svg>
                    </button>
                )}

                <button
                    type="button"
                    onClick={onSend}
                    disabled={!inputValue.trim() || isSending}
                    className="wgt-composer-send wgt-tip"
                    data-tooltip="Send"
                    style={{ color: primaryColor }}
                    aria-label="Send"
                >
                    {isSending ? (
                        <svg viewBox="0 0 24 24" fill="none" style={{ animation: 'wgt-spin 0.8s linear infinite' }} aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeOpacity="0.25" />
                            <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                        </svg>
                    ) : (
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z" />
                        </svg>
                    )}
                </button>
            </div>
        </div>
    );
}
