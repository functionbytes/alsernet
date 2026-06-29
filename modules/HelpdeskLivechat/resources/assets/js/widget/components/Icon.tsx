/**
 * Inline SVG icon component — replaces the Font Awesome CDN dependency.
 *
 * SVG paths sourced from Font Awesome 6 Free (Solid / Regular).
 * Font Awesome Free License: CC BY 4.0 — https://creativecommons.org/licenses/by/4.0/
 * Icons: Copyright 2023 Fonticons, Inc. — https://fontawesome.com
 *
 * Only the icons actually used by this widget are included. Add more as needed
 * rather than pulling in the full ~80KB CDN stylesheet.
 */

import React from 'react';

// FA6 Free Solid paths
const ICONS: Record<string, string> = {
    // fa-plus-circle / fa-circle-plus
    'plus-circle': 'M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344l0-64-64 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l64 0 0-64c0-13.3 10.7-24 24-24s24 10.7 24 24l0 64 64 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-64 0 0 64c0 13.3-10.7 24-24 24s-24-10.7-24-24z',
    // fa-home / fa-house
    'home': 'M575.8 255.5c0 18-15 32.1-32 32.1l-32 0 .7 160.2c0 2.7-.2 5.4-.5 8.1l0 16.2c0 22.1-17.9 40-40 40l-16 0c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1L416 512l-24 0c-22.1 0-40-17.9-40-40l0-24 0-64c0-17.7-14.3-32-32-32l-64 0c-17.7 0-32 14.3-32 32l0 64 0 24c0 22.1-17.9 40-40 40l-24 0-31.9 0c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2l-16 0c-22.1 0-40-17.9-40-40l0-112c0-.9 0-1.9 .1-2.8l0-69.7-32 0c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 12 15 11 24z',
    // fa-comments
    'comments': 'M512 240c0 114.9-114.6 208-256 208c-37.1 0-72.3-6.4-104.1-17.9c-11.9 4.8-49.1 20.2-52.4 20.2c-13.8 0-20.2-12.2-20.2-20.2c0-4.4 1.4-9.8 3.7-13.7l18.9-27.7C36.9 361.3 0 306.3 0 240C0 125.1 114.6 32 256 32s256 93.1 256 208zm256 208c0 66.3-36.9 121.3-94.9 153.7l18.9 27.7c2.3 3.9 3.7 9.3 3.7 13.7c0 7.9-6.5 20.2-20.2 20.2c-3.4 0-40.6-15.4-52.4-20.2C591.3 453.6 556.1 460 519 460c-91.8 0-171.7-39.3-215.2-97.5c6.8 .5 13.6 .7 20.5 .7c37.1 0 71.7-5.3 104.1-14.3c63.9-18.1 119.2-54.6 159-101c36.8-42.7 57.6-92.9 57.6-144.8c0-7.2-.4-14.3-1.1-21.3C853.4 112.9 896 172 896 240z',
    // fa-question-circle / fa-circle-question
    'question-circle': 'M504 256c0 136.997-111.043 248-248 248S8 392.997 8 256C8 119.083 119.043 8 256 8s248 111.083 248 248zM262.655 90c-54.497 0-89.255 22.957-116.549 63.758-3.536 5.286-2.353 12.415 2.715 16.258l34.699 26.31c5.205 3.947 12.621 3.008 16.665-2.122 17.864-22.658 30.113-35.797 57.303-35.797 20.429 0 45.698 13.148 45.698 32.958 0 14.976-12.363 22.667-32.534 33.976C247.128 238.528 216 254.941 216 296v4c0 6.627 5.373 12 12 12h56c6.627 0 12-5.373 12-12v-1.333c0-28.462 83.186-29.647 83.186-106.667 0-58.002-60.165-102-116.531-102zM256 338c-25.365 0-46 20.635-46 46 0 25.364 20.635 46 46 46s46-20.636 46-46c0-25.365-20.635-46-46-46z',
    // fa-minus
    'minus': 'M432 256c0 17.7-14.3 32-32 32L48 288c-17.7 0-32-14.3-32-32s14.3-32 32-32l352 0c17.7 0 32 14.3 32 32z',
    // fa-times / fa-xmark
    'times': 'M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z',
    // fa-robot
    'robot': 'M320 0c17.7 0 32 14.3 32 32l0 64 120 0c39.8 0 72 32.2 72 72l0 432c0 39.8-32.2 72-72 72L72 672c-39.8 0-72-32.2-72-72L0 168c0-39.8 32.2-72 72-72l120 0 0-64c0-17.7 14.3-32 32-32l96 0zM208 352c-17.7 0-32 14.3-32 32s14.3 32 32 32l32 0 0 32c0 17.7 14.3 32 32 32s32-14.3 32-32l0-32 32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-128 0zm-80-80a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm192-32a32 32 0 1 0 64 0 32 32 0 1 0 -64 0z',
    // fa-paperclip
    'paperclip': 'M364.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z',
    // fa-paper-plane
    'paper-plane': 'M498.1 5.6c10.1 7 15.4 19.1 13.5 31.2l-64 416c-1.5 9.7-7.4 18.2-16 23s-18.9 5.4-28 1.6L284 427.7l-68.5 74.1c-8.9 9.7-22.9 12.9-35.2 8.1S160 493.2 160 480l0-83.6c0-4 1.5-7.8 4.2-10.8L331.8 202.8c5.8-6.3 5.6-16-.4-22s-15.7-6.4-22-.7L106 360.8 17.7 316.6C7.1 311.3 .3 300.7 0 288.9s5.9-22.8 16.1-28.7l448-256c10.7-6.1 23.9-5.5 34 1.4z',
};

/** viewBox values differ by icon family — all FA6 solid icons use 0 0 512 512 by default,
 *  but some use wider canvases. We hardcode the correct values per icon. */
const VIEWBOXES: Record<string, string> = {
    'plus-circle':    '0 0 512 512',
    'home':           '0 0 576 512',
    'comments':       '0 0 896 512',
    'question-circle':'0 0 512 512',
    'minus':          '0 0 448 512',
    'times':          '0 0 384 512',
    'robot':          '0 0 640 512',
    'paperclip':      '0 0 448 512',
    'paper-plane':    '0 0 512 512',
};

interface IconProps {
    name: string;
    className?: string;
    style?: React.CSSProperties;
    /** Defaults to "24px" but can be overridden */
    size?: number | string;
    'aria-label'?: string;
}

/**
 * Renders an inline SVG icon. Use instead of `<i className="fa fa-...">`.
 *
 * @example
 * <Icon name="paper-plane" size={16} />
 */
export function Icon({ name, className, style, size = 16, 'aria-label': ariaLabel }: IconProps) {
    const path = ICONS[name];
    const viewBox = VIEWBOXES[name] ?? '0 0 512 512';

    if (!path) {
        if (import.meta.env.DEV) {
            console.warn(`[Icon] Unknown icon: "${name}". Add it to Icon.tsx.`);
        }
        return null;
    }

    return (
        <svg
            viewBox={viewBox}
            fill="currentColor"
            width={size}
            height={size}
            className={className}
            style={style}
            aria-hidden={ariaLabel ? undefined : true}
            aria-label={ariaLabel}
            role={ariaLabel ? 'img' : undefined}
        >
            <path d={path} />
        </svg>
    );
}
