# Architectural Decision Records (ADR)

Decisiones arquitectonicas relevantes del proyecto Alsernet (Inoqualab).

Los ADRs documentan decisiones de diseno significativas: el contexto que las motivo, las opciones evaluadas y las consecuencias esperadas. Un ADR puede estar Propuesto (decision pendiente), Aceptado (en vigor) o Rechazado (descartado con justificacion).

## Indice

| ADR | Titulo | Estado |
|-----|--------|--------|
| [0001](./0001-mailer-vs-mailrelay.md) | Mailer vs Mailrelay — coexistencia y futura consolidacion | **Aceptado** (Opcion C) — 2026-04-27 |

## Como crear un ADR nuevo

1. Copiar el archivo `0001-mailer-vs-mailrelay.md` como base.
2. Numerar incrementalmente: `0002-titulo-corto.md`.
3. Estado inicial: `Propuesto`.
4. Tras la decision del equipo: actualizar a `Aceptado` o `Rechazado` con la fecha de decision.
5. Agregar la entrada en la tabla de indice de este README.

## Estados validos

- **Propuesto**: decision pendiente, opciones documentadas para discusion.
- **Aceptado**: decision tomada y vigente.
- **Rechazado**: evaluado y descartado; el ADR se mantiene como registro historico.
- **Reemplazado por ADR-XXXX**: decision superada por una decision posterior.
