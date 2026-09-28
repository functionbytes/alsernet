import React from 'react';

interface SettingToggleProps {
    label: string;
    hint: string;
    checked: boolean;
    onChange: (v: boolean) => void;
}

export default function SettingToggle({ label, hint, checked, onChange }: SettingToggleProps) {
    return (
        <div style={{ marginBottom: 12 }}>
            <label style={{ display: 'flex', alignItems: 'flex-start', gap: 8, cursor: 'pointer' }}>
                <input type="checkbox" checked={checked} onChange={e => onChange(e.target.checked)} style={{ marginTop: 3 }} />
                <span>
                    <span style={{ fontSize: 13, fontWeight: 500, color: '#1e293b' }}>{label}</span>
                    <span style={{ display: 'block', fontSize: 11, color: '#94a3b8', marginTop: 2 }}>{hint}</span>
                </span>
            </label>
        </div>
    );
}
