/**
 * MessageBubble - Individual message display
 */

import React from 'react';
import type { Message } from '../types';
import { formatMessageTime } from '../utils/dateFormatter';
import { getFileIcon, formatFileSize } from '../utils/fileValidator';

interface MessageBubbleProps {
    message: Message;
    isOwnMessage: boolean;
    color: string;
}

export function MessageBubble({ message, isOwnMessage, color }: MessageBubbleProps) {
    const isFile = message.content_type !== 'text';
    const isImage = message.content_type === 'image';

    return (
        <div className={`flex mb-4 ${isOwnMessage ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[75%] ${isOwnMessage ? 'order-2' : 'order-1'}`}>
                {/* Sender name (only for agent messages) */}
                {!isOwnMessage && (
                    <div className="text-xs text-gray-500 mb-1 px-1">
                        {message.sender.name}
                    </div>
                )}

                {/* Message bubble */}
                <div
                    className={`rounded-2xl px-4 py-2.5 ${
                        isOwnMessage
                            ? 'text-white rounded-br-none'
                            : 'bg-gray-100 text-gray-800 rounded-bl-none'
                    }`}
                    style={isOwnMessage ? { backgroundColor: color } : {}}
                >
                    {/* Image message */}
                    {isImage && message.content_attributes?.file_url && (
                        <div className="mb-2">
                            <img
                                src={message.content_attributes.file_url}
                                alt={message.content}
                                className="rounded-lg max-w-full h-auto cursor-pointer"
                                onClick={() =>
                                    window.open(message.content_attributes?.file_url, '_blank')
                                }
                            />
                        </div>
                    )}

                    {/* File message */}
                    {isFile && !isImage && message.content_attributes && (
                        <a
                            href={message.content_attributes.file_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center space-x-3 hover:opacity-80 transition-opacity"
                        >
                            <div className="text-2xl">
                                <i
                                    className={`fas ${getFileIcon(
                                        message.content_attributes.mime_type || ''
                                    )}`}
                                ></i>
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="font-medium truncate">
                                    {message.content_attributes.file_name || message.content}
                                </div>
                                {message.content_attributes.file_size && (
                                    <div className="text-xs opacity-75">
                                        {formatFileSize(message.content_attributes.file_size)}
                                    </div>
                                )}
                            </div>
                            <div className="text-lg">
                                <i className="fas fa-download"></i>
                            </div>
                        </a>
                    )}

                    {/* Text message */}
                    {!isFile && (
                        <div className="whitespace-pre-wrap break-words">{message.content}</div>
                    )}

                    {/* Timestamp */}
                    <div
                        className={`text-xs mt-1 ${
                            isOwnMessage ? 'text-white opacity-75' : 'text-gray-500'
                        }`}
                    >
                        {formatMessageTime(message.created_at)}
                    </div>
                </div>
            </div>
        </div>
    );
}
