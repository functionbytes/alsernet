import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle, labelStyle } from '../components/styles';

const DAYS = [
    { n: 1, label: 'Lun' }, { n: 2, label: 'Mar' }, { n: 3, label: 'Mié' },
    { n: 4, label: 'Jue' }, { n: 5, label: 'Vie' }, { n: 6, label: 'Sáb' }, { n: 7, label: 'Dom' },
];

export default function BusinessHoursConfig({ draft, setData }: NodeConfigProps) {
    const d = draft.data || {};
    const activeDays: number[] = d.days || [1, 2, 3, 4, 5];
    return (
        <div>
            <label style={labelStyle}>Días activos</label>
            <div className="d-flex flex-wrap gap-1 mb-3">
                {DAYS.map(day => {
                    const on = activeDays.includes(day.n);
                    return (
                        <button key={day.n} type="button"
                            className={`btn btn-sm ${on ? 'btn-primary' : 'btn-outline-secondary'}`}
                            onClick={() => {
                                const next = on ? activeDays.filter(x => x !== day.n) : [...activeDays, day.n].sort();
                                setData({ days: next });
                            }}>
                            {day.label}
                        </button>
                    );
                })}
            </div>
            <div className="d-flex gap-2 mb-3">
                <div className="flex-fill">
                    <label style={labelStyle}>Desde</label>
                    <input type="time" className="form-control form-control-sm"
                        value={d.start_time || '09:00'}
                        onChange={e => setData({ start_time: e.target.value })} />
                </div>
                <div className="flex-fill">
                    <label style={labelStyle}>Hasta</label>
                    <input type="time" className="form-control form-control-sm"
                        value={d.end_time || '18:00'}
                        onChange={e => setData({ end_time: e.target.value })} />
                </div>
            </div>
            <label style={labelStyle}>Zona horaria</label>
            <input type="text" className="form-control form-control-sm"
                placeholder="Europe/Madrid"
                value={d.timezone || ''}
                onChange={e => setData({ timezone: e.target.value })} />
            <p style={hintStyle}>Guarda <code>within_business_hours</code> (sí/no) en el contexto. Pon un nodo <strong>Condición</strong> después para ramificar dentro/fuera de horario. Un festivo cargado en <em>Festivos</em> también cuenta como fuera de horario, aunque caiga dentro del rango de días/horas de arriba.</p>
        </div>
    );
}
