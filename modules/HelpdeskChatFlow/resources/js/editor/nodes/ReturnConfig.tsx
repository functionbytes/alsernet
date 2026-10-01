import React from 'react';
import { NodeConfigProps } from '../types';
import { hintStyle } from '../components/styles';

export default function ReturnConfig(_: NodeConfigProps) {
    return (
        <p style={hintStyle}>
            Termina el procedimiento y devuelve el control al flow que lo llamó, que continúa en el paso
            siguiente a «Llamar procedimiento». Fuera de un procedimiento termina la conversación.
        </p>
    );
}
