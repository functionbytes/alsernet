import React from 'react';

/**
 * Formato ligero para mensajes del agente y del bot IA (que responde en
 * markdown): **negrita**, *cursiva* / _cursiva_, saltos de línea, listas con
 * "- " o "• " y enlaces http(s). Todo con elementos React (sin innerHTML), así
 * que un texto malicioso nunca se interpreta como HTML.
 */

const URL_RE = /(https?:\/\/[^\s<>"')\]]+)/g;
const INLINE_RE = /(\*\*[^*\n]+\*\*|\*[^*\n]+\*|_[^_\n]+_)/g;

function renderLinks(text: string, keyPrefix: string): React.ReactNode[] {
    return text.split(URL_RE).map((part, i) => {
        if (/^https?:\/\//.test(part)) {
            return (
                <a key={`${keyPrefix}-l${i}`} href={part} target="_blank" rel="noopener noreferrer" className="wgt-rich-link">
                    {part}
                </a>
            );
        }
        return <React.Fragment key={`${keyPrefix}-t${i}`}>{part}</React.Fragment>;
    });
}

function renderInline(text: string, keyPrefix: string): React.ReactNode[] {
    return text.split(INLINE_RE).map((part, i) => {
        const key = `${keyPrefix}-${i}`;
        if (part.length > 4 && part.startsWith('**') && part.endsWith('**')) {
            return <strong key={key}>{renderLinks(part.slice(2, -2), key)}</strong>;
        }
        if (part.length > 2 && ((part.startsWith('*') && part.endsWith('*')) || (part.startsWith('_') && part.endsWith('_')))) {
            return <em key={key}>{renderLinks(part.slice(1, -1), key)}</em>;
        }
        return <React.Fragment key={key}>{renderLinks(part, key)}</React.Fragment>;
    });
}

export function RichText({ text }: { text: string }) {
    const lines = text.replace(/\r\n/g, '\n').split('\n');
    const blocks: React.ReactNode[] = [];
    let list: string[] = [];

    const flushList = (key: string) => {
        if (list.length) {
            blocks.push(
                <ul key={key} className="wgt-rich-list">
                    {list.map((item, i) => <li key={i}>{renderInline(item, `${key}-${i}`)}</li>)}
                </ul>
            );
            list = [];
        }
    };

    lines.forEach((line, i) => {
        const item = line.match(/^\s*(?:[-•*]|\d+[.)])\s+(.*)$/);
        if (item) {
            list.push(item[1]);
            return;
        }
        flushList(`ul${i}`);
        if (line.trim() === '') {
            blocks.push(<span key={`br${i}`} className="wgt-rich-gap" />);
            return;
        }
        blocks.push(<span key={`p${i}`} className="wgt-rich-line">{renderInline(line, `p${i}`)}</span>);
    });
    flushList('ul-end');

    return <>{blocks}</>;
}
