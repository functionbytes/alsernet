import React, { useEffect, useMemo, useRef, useState } from 'react';

interface AudioPlayerProps {
    src: string;
    mime?: string;
    title?: string;
    isUser?: boolean;
}

const WAVEFORM_BARS = 32;

function formatTime(seconds: number): string {
    if (!isFinite(seconds) || seconds < 0) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
}

// Deterministic pseudo-waveform: produces a stable, varied bar height pattern
// from the audio src so each track shows the same shape across re-renders
// without needing actual audio decoding (which is expensive in a chat widget).
function buildWaveform(seed: string): number[] {
    let h = 0;
    for (let i = 0; i < seed.length; i += 1) {
        h = (h * 31 + seed.charCodeAt(i)) | 0;
    }
    const bars: number[] = [];
    for (let i = 0; i < WAVEFORM_BARS; i += 1) {
        h = (h * 1103515245 + 12345) | 0;
        const r = ((h >>> 16) & 0x7fff) / 0x7fff;
        const eased = 0.35 + r * 0.65;
        bars.push(Math.round(eased * 100) / 100);
    }
    return bars;
}

export function AudioPlayer({ src, mime, title, isUser }: AudioPlayerProps) {
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const trackRef = useRef<HTMLDivElement | null>(null);
    const [isPlaying, setIsPlaying] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [duration, setDuration] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [rate, setRate] = useState(1);

    const waveform = useMemo(() => buildWaveform(src), [src]);

    useEffect(() => {
        const audio = audioRef.current;
        if (!audio) return;

        const onLoadedMeta = () => setDuration(audio.duration || 0);
        const onTimeUpdate = () => setCurrentTime(audio.currentTime);
        const onPlay = () => { setIsPlaying(true); setIsLoading(false); };
        const onPause = () => setIsPlaying(false);
        const onEnded = () => { setIsPlaying(false); setCurrentTime(0); };
        const onWaiting = () => setIsLoading(true);
        const onPlaying = () => setIsLoading(false);

        audio.addEventListener('loadedmetadata', onLoadedMeta);
        audio.addEventListener('timeupdate', onTimeUpdate);
        audio.addEventListener('play', onPlay);
        audio.addEventListener('pause', onPause);
        audio.addEventListener('ended', onEnded);
        audio.addEventListener('waiting', onWaiting);
        audio.addEventListener('playing', onPlaying);

        return () => {
            audio.removeEventListener('loadedmetadata', onLoadedMeta);
            audio.removeEventListener('timeupdate', onTimeUpdate);
            audio.removeEventListener('play', onPlay);
            audio.removeEventListener('pause', onPause);
            audio.removeEventListener('ended', onEnded);
            audio.removeEventListener('waiting', onWaiting);
            audio.removeEventListener('playing', onPlaying);
        };
    }, [src]);

    const toggle = () => {
        const audio = audioRef.current;
        if (!audio) return;
        if (isPlaying) {
            audio.pause();
        } else {
            audio.play().catch(() => setIsLoading(false));
        }
    };

    const seekFromPointer = (clientX: number) => {
        const audio = audioRef.current;
        const track = trackRef.current;
        if (!audio || !track || !duration) return;
        const rect = track.getBoundingClientRect();
        const ratio = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width));
        audio.currentTime = ratio * duration;
    };

    const onTrackClick = (e: React.MouseEvent<HTMLDivElement>) => {
        seekFromPointer(e.clientX);
    };

    const cycleRate = () => {
        const audio = audioRef.current;
        if (!audio) return;
        const next = rate === 1 ? 1.5 : rate === 1.5 ? 2 : 1;
        audio.playbackRate = next;
        setRate(next);
    };

    const progress = duration > 0 ? currentTime / duration : 0;
    const remaining = duration > 0 ? Math.max(0, duration - currentTime) : 0;
    const timeLabel = isPlaying || currentTime > 0
        ? formatTime(currentTime)
        : formatTime(duration);

    return (
        <div className={`wgt-audio-player${isUser ? ' is-user' : ' is-agent'}`} title={title}>
            <button
                type="button"
                className="wgt-audio-btn"
                onClick={toggle}
                aria-label={isPlaying ? 'Pausar' : 'Reproducir'}
            >
                {isLoading ? (
                    <svg viewBox="0 0 24 24" className="wgt-audio-spinner" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" strokeWidth="2.4" strokeDasharray="14 42" strokeLinecap="round" />
                    </svg>
                ) : isPlaying ? (
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <rect x="7" y="5" width="3.5" height="14" rx="1" />
                        <rect x="13.5" y="5" width="3.5" height="14" rx="1" />
                    </svg>
                ) : (
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8 5.5v13l11-6.5z" />
                    </svg>
                )}
            </button>

            <div
                ref={trackRef}
                className="wgt-audio-wave"
                onClick={onTrackClick}
                role="slider"
                aria-label="Posición del audio"
                aria-valuemin={0}
                aria-valuemax={Math.max(1, Math.round(duration))}
                aria-valuenow={Math.round(currentTime)}
                tabIndex={0}
            >
                {waveform.map((h, i) => {
                    const filled = i / WAVEFORM_BARS < progress;
                    return (
                        <span
                            key={i}
                            className={`wgt-audio-bar${filled ? ' is-filled' : ''}`}
                            style={{ height: `${Math.round(h * 100)}%` }}
                        />
                    );
                })}
            </div>

            <div className="wgt-audio-meta">
                <span className="wgt-audio-time">{timeLabel}</span>
                {duration > 0 && (
                    <button
                        type="button"
                        className={`wgt-audio-rate${rate !== 1 ? ' is-active' : ''}`}
                        onClick={cycleRate}
                        title="Velocidad de reproducción"
                        aria-label="Cambiar velocidad"
                    >
                        {rate}x
                    </button>
                )}
            </div>

            <audio ref={audioRef} preload="metadata" style={{ display: 'none' }}>
                <source src={src} type={mime || undefined} />
            </audio>
        </div>
    );
}
