/**
 * Date formatting utilities for widget
 */

/**
 * Format date as time (HH:MM) or date if not today
 */
export function formatMessageTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();

    if (isToday) {
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        });
    }

    const isYesterday =
        new Date(now.getTime() - 24 * 60 * 60 * 1000).toDateString() ===
        date.toDateString();

    if (isYesterday) {
        return 'Yesterday';
    }

    const isThisWeek = now.getTime() - date.getTime() < 7 * 24 * 60 * 60 * 1000;

    if (isThisWeek) {
        return date.toLocaleDateString('en-US', { weekday: 'short' });
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

/**
 * Format date as relative time (e.g., "2 minutes ago")
 */
export function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) {
        return 'Just now';
    }

    if (diffMin < 60) {
        return `${diffMin} ${diffMin === 1 ? 'minute' : 'minutes'} ago`;
    }

    if (diffHour < 24) {
        return `${diffHour} ${diffHour === 1 ? 'hour' : 'hours'} ago`;
    }

    if (diffDay < 7) {
        return `${diffDay} ${diffDay === 1 ? 'day' : 'days'} ago`;
    }

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: diffDay > 365 ? 'numeric' : undefined,
    });
}

/**
 * Format full date and time
 */
export function formatFullDateTime(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
