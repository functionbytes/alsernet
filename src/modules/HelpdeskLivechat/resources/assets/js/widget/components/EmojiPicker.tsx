import React, { useEffect, useRef, useState } from 'react';

interface EmojiPickerProps {
    onSelect: (emoji: string) => void;
    onClose: () => void;
}

const CATEGORIES: Array<{ key: string; label: string; icon: string; emojis: string[] }> = [
    {
        key: 'smileys',
        label: 'Smileys',
        icon: '😀',
        emojis: [
            '😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊',
            '😇','🥰','😍','🤩','😘','😗','😚','😙','😋','😛','😜','🤪',
            '😝','🤑','🤗','🤭','🤫','🤔','🤨','😐','😑','😶','😏','😒',
            '🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤧',
        ],
    },
    {
        key: 'gestures',
        label: 'Gestures',
        icon: '👋',
        emojis: [
            '👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘',
            '🤙','👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛',
            '🤜','👏','🙌','👐','🤲','🤝','🙏','💪',
        ],
    },
    {
        key: 'hearts',
        label: 'Hearts',
        icon: '❤️',
        emojis: [
            '❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕',
            '💞','💓','💗','💖','💘','💝','💟','♥️','💌','💋','🌹','🌸',
        ],
    },
    {
        key: 'objects',
        label: 'Objects',
        icon: '✨',
        emojis: [
            '✨','⭐','🌟','💫','⚡','🔥','💥','💢','💨','💦','💧','🎉',
            '🎊','🎈','🎁','🎂','🍕','🍔','🍟','🍩','☕','🍺','🍷','🥂',
            '🚀','🌈','☀️','🌙','☁️','❄️','🎵','🎶','📷','📱','💻','🔔',
        ],
    },
];

export function EmojiPicker({ onSelect, onClose }: EmojiPickerProps) {
    const [activeCat, setActiveCat] = useState(CATEGORIES[0].key);
    const [search, setSearch] = useState('');
    const wrapRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (!wrapRef.current?.contains(e.target as Node)) onClose();
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [onClose]);

    const cat = CATEGORIES.find(c => c.key === activeCat) ?? CATEGORIES[0];
    const visible = search.trim()
        ? CATEGORIES.flatMap(c => c.emojis).filter(e => e.includes(search.trim()))
        : cat.emojis;

    return (
        <div ref={wrapRef} className="wgt-emoji-picker" role="dialog" aria-label="Emoji picker">
            <div className="wgt-emoji-grid">
                {visible.map((e, i) => (
                    <button
                        key={`${e}-${i}`}
                        type="button"
                        className="wgt-emoji-cell"
                        onClick={() => onSelect(e)}
                        aria-label={`Insert ${e}`}
                    >
                        {e}
                    </button>
                ))}
                {visible.length === 0 && (
                    <div className="wgt-emoji-empty">No matches</div>
                )}
            </div>
            <div className="wgt-emoji-tabs" role="tablist">
                {CATEGORIES.map(c => (
                    <button
                        key={c.key}
                        type="button"
                        role="tab"
                        aria-selected={activeCat === c.key}
                        className={`wgt-emoji-tab${activeCat === c.key ? ' is-active' : ''}`}
                        onClick={() => { setActiveCat(c.key); setSearch(''); }}
                        title={c.label}
                    >
                        {c.icon}
                    </button>
                ))}
            </div>
        </div>
    );
}
