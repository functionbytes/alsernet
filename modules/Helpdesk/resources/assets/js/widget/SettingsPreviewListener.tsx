import { useEffect, useRef } from 'react';
import { useWidgetStore } from './widget-store';

export function SettingsPreviewListener() {
    const alreadyFiredLoadedEvent = useRef(false);
    const updateSettings = useWidgetStore(state => state.updateSettings);

    useEffect(() => {
        // Listen for messages from backups page
        const handleMessage = (event: MessageEvent) => {
            // Security: Only accept messages from same origin
            if (event.origin !== window.location.origin) {
                return;
            }

            // Only process messages from backups editor
            if (event.data?.source !== 'be-backups-editor') {
                return;
            }

            // Handle different command types
            switch (event.data.type) {
                case 'setValues':
                    if (event.data.values) {
                        updateSettings(event.data.values);
                    }
                    break;

                default:
                    console.log('Unknown command type:', event.data.type);
            }
        };

        // Add message listener
        window.addEventListener('message', handleMessage);

        // Notify parent that widget is loaded
        if (!alreadyFiredLoadedEvent.current) {
            window.parent.postMessage(
                {
                    source: 'be-backups-preview',
                    type: 'appLoaded'
                },
                '*'
            );
            alreadyFiredLoadedEvent.current = true;
        }

        // Cleanup
        return () => {
            window.removeEventListener('message', handleMessage);
        };
    }, [updateSettings]);

    return null;
}
