import React from 'react';

export function TestWidget() {
    return (
        <div style={{
            width: '360px',
            height: '560px',
            background: 'white',
            borderRadius: '12px',
            padding: '20px',
            boxShadow: '0 4px 24px rgba(0, 0, 0, 0.15)',
            display: 'flex',
            flexDirection: 'column',
            gap: '20px'
        }}>
            <div style={{
                background: '#90bb13',
                padding: '20px',
                borderRadius: '8px',
                color: 'white'
            }}>
                <h2>🚀 React Widget Test</h2>
                <p>If you can see this, React is working!</p>
            </div>

            <div style={{
                background: '#f5f5f5',
                padding: '20px',
                borderRadius: '8px'
            }}>
                <h3>Widget Information</h3>
                <p>✅ React rendering successfully</p>
                <p>✅ Vite hot reload active</p>
                <p>✅ TypeScript compiling</p>
            </div>

            <button style={{
                background: '#90bb13',
                color: 'white',
                padding: '12px',
                border: 'none',
                borderRadius: '8px',
                cursor: 'pointer',
                fontSize: '16px'
            }} onClick={() => alert('Button works!')}>
                Click me to test interactivity
            </button>
        </div>
    );
}
