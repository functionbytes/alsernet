import React from 'react';
import { VariantGroup, VariantOption } from '../widget-commerce';
import { useTranslation } from '../i18n/useLanguage';

/**
 * Selector de combinación (talla, color…) embebido en la tarjeta del carrusel
 * o en la ficha del panel "Productos": un grupo de chips por atributo
 * (colores con muestra si el grupo trae `color_hex`, el resto como cajas).
 * Las combinaciones sin stock se muestran tachadas y deshabilitadas.
 */

interface VariantPickerProps {
    groups: VariantGroup[];
    options: VariantOption[];
    selection: Record<string, string>;
    onSelect: (groupName: string, value: string) => void;
    primaryColor?: string;
}

/** Combinaciones que siguen siendo alcanzables con el resto de grupos ya elegidos. */
function matchingOptions(options: VariantOption[], groups: VariantGroup[], selection: Record<string, string>, groupName: string, value: string): VariantOption[] {
    return options.filter((o) => {
        if (o.groups[groupName] !== value) return false;

        return groups.every((g) => g.name === groupName || !selection[g.name] || o.groups[g.name] === selection[g.name]);
    });
}

export function VariantPicker({ groups, options, selection, onSelect, primaryColor = '#90bb13' }: VariantPickerProps) {
    const t = useTranslation();

    const moveFocus = (e: React.KeyboardEvent<HTMLDivElement>, buttons: HTMLButtonElement[], index: number) => {
        const delta = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : e.key === 'ArrowLeft' || e.key === 'ArrowUp' ? -1 : 0;
        if (!delta) return;
        e.preventDefault();
        const next = buttons[(index + delta + buttons.length) % buttons.length];
        next?.focus();
    };

    return (
        <div className="wgt-variant-picker">
            {groups.map((group) => (
                <div className="wgt-variant-group" key={group.name}>
                    <span className="wgt-variant-group-label">{group.name}</span>
                    <div
                        className="wgt-variant-chips"
                        role="radiogroup"
                        aria-label={group.name}
                        onKeyDown={(e) => {
                            const buttons = Array.from(e.currentTarget.querySelectorAll<HTMLButtonElement>('.wgt-variant-chip:not(:disabled)'));
                            const index = buttons.indexOf(document.activeElement as HTMLButtonElement);
                            if (index !== -1) moveFocus(e, buttons, index);
                        }}
                    >
                        {(() => {
                            const values = group.values.map((v) => ({
                                v,
                                candidates: matchingOptions(options, groups, selection, group.name, v.value),
                            }));
                            const firstAvailable = values.find(({ candidates }) => candidates.some((c) => c.available))?.v.value;

                            return values.map(({ v, candidates }) => {
                                const available = candidates.some((c) => c.available);
                                const lowStock = available && candidates.filter((c) => c.available).every((c) => c.low_stock);
                                const isSelected = selection[group.name] === v.value;
                                const isColor = group.type === 'color';
                                const isRovingTarget = isSelected || (!selection[group.name] && v.value === firstAvailable);

                                return (
                                    <button
                                        key={v.value}
                                        type="button"
                                        role="radio"
                                        aria-checked={isSelected}
                                        aria-label={isColor ? v.value : undefined}
                                        title={!available ? `${v.value} · ${t('shop.out_of_stock')}` : lowStock ? `${v.value} · ${t('shop.last_units')}` : v.value}
                                        className={`wgt-variant-chip${isColor ? ' wgt-variant-chip-color' : ''}${isSelected ? ' is-selected' : ''}${!available ? ' is-disabled' : ''}`}
                                        style={isSelected && !isColor ? { borderColor: primaryColor, color: primaryColor } : undefined}
                                        disabled={!available}
                                        tabIndex={isRovingTarget ? 0 : -1}
                                        onClick={() => onSelect(group.name, v.value)}
                                    >
                                        {isColor ? (
                                            <span className="wgt-variant-swatch" style={{ background: v.color_hex || '#ccc' }} />
                                        ) : (
                                            v.value
                                        )}
                                        {/* Punto discreto (el texto va en el title y para lectores de pantalla):
                                            con varias tallas seguidas las etiquetas se solapaban. */}
                                        {lowStock && (
                                            <span className="wgt-variant-lowstock" aria-hidden="true" />
                                        )}
                                        {lowStock && <span className="wgt-sr-only">{t('shop.last_units')}</span>}
                                    </button>
                                );
                            });
                        })()}
                    </div>
                    {(() => {
                        // Aviso legible solo para la opción elegida.
                        const chosen = selection[group.name];
                        if (!chosen) return null;
                        const available = matchingOptions(options, groups, selection, group.name, chosen).filter((c) => c.available);
                        return available.length > 0 && available.every((c) => c.low_stock)
                            ? <p className="wgt-variant-lowstock-note">{t('shop.last_units')}</p>
                            : null;
                    })()}
                </div>
            ))}
        </div>
    );
}
