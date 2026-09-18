import React, { useEffect, useState, useCallback } from 'react';

export interface LightboxImage {
    url: string;
    name?: string;
}

interface ImageLightboxProps {
    images: LightboxImage[];
    initialIndex: number;
    onClose: () => void;
}

export function ImageLightbox({ images, initialIndex, onClose }: ImageLightboxProps) {
    const [index, setIndex] = useState(initialIndex);
    const [zoom, setZoom] = useState(1);

    const current = images[index];
    const canPrev = index > 0;
    const canNext = index < images.length - 1;

    const goPrev = useCallback(() => {
        if (!canPrev) return;
        setIndex((i) => i - 1);
        setZoom(1);
    }, [canPrev]);

    const goNext = useCallback(() => {
        if (!canNext) return;
        setIndex((i) => i + 1);
        setZoom(1);
    }, [canNext]);

    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
            else if (e.key === 'ArrowLeft') goPrev();
            else if (e.key === 'ArrowRight') goNext();
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose, goPrev, goNext]);


    if (!current) return null;

    return (
        <div className="wgt-lightbox" onClick={onClose} role="dialog" aria-modal="true">

            <div className="wgt-lightbox-toolbar" onClick={(e) => e.stopPropagation()}>
                <span className="wgt-lightbox-counter">
                    {index + 1} / {images.length}
                </span>
                <div className="wgt-lightbox-actions">
                    <button
                        type="button"
                        className="wgt-lightbox-btn"
                        onClick={() => setZoom((z) => Math.max(0.5, z - 0.25))}
                        title="Reducir zoom"
                        aria-label="Reducir zoom"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13H5v-2h14v2z"/></svg>
                    </button>
                    <button
                        type="button"
                        className="wgt-lightbox-btn"
                        onClick={() => setZoom((z) => Math.min(3, z + 0.25))}
                        title="Aumentar zoom"
                        aria-label="Aumentar zoom"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    </button>
                    <a
                        className="wgt-lightbox-btn"
                        href={current.url}
                        download={current.name}
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Descargar"
                        aria-label="Descargar"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                    </a>
                    <button
                        type="button"
                        className="wgt-lightbox-btn wgt-lightbox-close"
                        onClick={onClose}
                        title="Cerrar"
                        aria-label="Cerrar"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                    </button>
                </div>
            </div>

            <div className="wgt-lightbox-stage" onClick={(e) => e.stopPropagation()}>
                {canPrev && (
                    <button
                        type="button"
                        className="wgt-lightbox-nav wgt-lightbox-nav-prev"
                        onClick={goPrev}
                        title="Anterior"
                        aria-label="Imagen anterior"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6z"/></svg>
                    </button>
                )}

                <img
                    className="wgt-lightbox-img"
                    src={current.url}
                    alt={current.name ?? ''}
                    style={{ transform: `scale(${zoom})` }}
                />

                {canNext && (
                    <button
                        type="button"
                        className="wgt-lightbox-nav wgt-lightbox-nav-next"
                        onClick={goNext}
                        title="Siguiente"
                        aria-label="Imagen siguiente"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>
                    </button>
                )}
            </div>

            {current.name && (
                <div className="wgt-lightbox-caption" onClick={(e) => e.stopPropagation()}>
                    {current.name}
                </div>
            )}
        </div>
    );
}
