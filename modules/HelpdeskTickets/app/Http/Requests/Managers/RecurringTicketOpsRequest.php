<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskTickets\Models\RecurringTicket;

/**
 * Alta y edición de una recurrencia desde el modal 30 de /tickets.
 *
 * Una sola clase para las dos operaciones (a diferencia de
 * Store/UpdateRecurringTicketRequest, que son idénticas entre sí) porque lo
 * único que cambia es el permiso exigido y las frecuencias admitidas, y las
 * dos cosas se deducen de si la ruta trae ya una recurrencia.
 */
class RecurringTicketOpsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $this->recurrencia()
            ? $user->can('helpdesk.tickets.update')
            : $user->can('helpdesk.tickets.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_ticket_categories,id'],
            'priority_id' => ['nullable', 'integer', 'exists:helpdesk.helpdesk_priorities,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'frequency' => ['required', 'in:'.implode(',', $this->frecuenciasAdmitidas())],
            'next_run_at' => ['nullable', 'date'],
        ];
    }

    /**
     * El modal no ofrece 'custom': RecurringTicket::nextRunFromCron() solo
     * entiende tres formas de cron ("0 * * * *", "M H * * *" y "M H * * DOW")
     * y cualquier otra — la del "día 1 de cada mes" del mockup, entre ellas —
     * cae en el `return now()->addDay()` final, o sea, la recurrencia se
     * ejecutaría al día siguiente sin avisar. Los crons se siguen editando en
     * la pantalla completa de Ajustes.
     *
     * Una recurrencia que YA es 'custom' sí puede guardarse tal cual desde el
     * modal (nombre, asunto, categoría…): lo contrario obligaría a cambiarle
     * la frecuencia para poder tocarle el asunto.
     *
     * @return array<int, string>
     */
    private function frecuenciasAdmitidas(): array
    {
        $base = ['daily', 'weekly', 'monthly'];

        return $this->recurrencia()?->frequency === 'custom'
            ? [...$base, 'custom']
            : $base;
    }

    private function recurrencia(): ?RecurringTicket
    {
        $ruta = $this->route('recurringTicket');

        return $ruta instanceof RecurringTicket ? $ruta : null;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ponle un nombre a la recurrencia.',
            'subject.required' => 'El asunto del ticket es obligatorio.',
            'frequency.required' => 'Elige cada cuánto se crea el ticket.',
            'frequency.in' => 'La frecuencia debe ser diaria, semanal o mensual.',
            'next_run_at.date' => 'La próxima ejecución no es una fecha válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'description' => 'descripcion',
            'category_id' => 'categoria',
            'priority_id' => 'prioridad',
            'assignee_id' => 'agente asignado',
            'frequency' => 'frecuencia',
            'next_run_at' => 'proxima ejecucion',
        ];
    }
}
