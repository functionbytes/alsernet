import React from 'react';

interface ErrorBoundaryProps {
    children: React.ReactNode;
    /** Called when the visitor dismisses the error (e.g. to navigate home). */
    onReset?: () => void;
}

interface ErrorBoundaryState {
    hasError: boolean;
}

/**
 * Catches render/runtime errors in the widget screens so a single broken screen
 * shows a recoverable fallback instead of unmounting the entire widget (which
 * the visitor perceives as the chat "closing"). The bottom navigation lives
 * outside this boundary, so the visitor can always switch tabs.
 */
export class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
    state: ErrorBoundaryState = { hasError: false };

    static getDerivedStateFromError(): ErrorBoundaryState {
        return { hasError: true };
    }

    componentDidCatch(error: Error, info: React.ErrorInfo): void {
        // eslint-disable-next-line no-console
        console.error('[helpdesk-widget] screen error:', error, info?.componentStack);
    }

    private handleReset = (): void => {
        this.setState({ hasError: false });
        this.props.onReset?.();
    };

    render(): React.ReactNode {
        if (!this.state.hasError) {
            return this.props.children;
        }

        return (
            <div className="wgt-help" role="alert">
                <div className="wgt-help-empty">
                    Algo salió mal al cargar esta sección.
                </div>
                <div className="wgt-help-empty">
                    <button type="button" className="wgt-nav-tab" onClick={this.handleReset}>
                        Volver al inicio
                    </button>
                </div>
            </div>
        );
    }
}
