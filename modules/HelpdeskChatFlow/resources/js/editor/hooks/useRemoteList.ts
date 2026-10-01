import { useEffect, useState } from 'react';
import axios from 'axios';

// Loads `{ data: T[] }` from a JSON endpoint once (and again when the URL changes).
export function useRemoteList<T>(url: string | null | undefined): { items: T[]; loading: boolean; failed: boolean } {
    const [items, setItems] = useState<T[]>([]);
    const [loading, setLoading] = useState(!!url);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        if (!url) { setLoading(false); return; }
        let cancelled = false;
        setLoading(true);
        axios.get(url, { headers: { Accept: 'application/json' } })
            .then(r => { if (!cancelled) { setItems(r.data?.data ?? []); setFailed(false); } })
            .catch(() => { if (!cancelled) setFailed(true); })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, [url]);

    return { items, loading, failed };
}
