<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketTask;

/**
 * Trabajo dentro de un ticket (24-sep-2026): checklist de tareas y
 * subtickets. Un subticket es un ticket normal enlazado al padre con
 * link_type 'subticket_of'; Ticket::openBlockers() lo cuenta, así que el
 * padre no se cierra con hijos abiertos salvo forzándolo.
 */
class TicketWorkController extends Controller
{
    public function storeTask(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $task = TicketTask::create([
            'ticket_id' => $ticket->id,
            'title' => trim($validated['title']),
            'position' => ((int) TicketTask::query()->where('ticket_id', $ticket->id)->max('position')) + 1,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['task' => $task->toPanelRow()], 201);
    }

    public function updateTask(Request $request, Ticket $ticket, TicketTask $task): JsonResponse
    {
        $this->authorize('update', $ticket);
        abort_unless($task->ticket_id === $ticket->id, 404);

        $validated = $request->validate([
            'is_done' => ['sometimes', 'boolean'],
            'title' => ['sometimes', 'string', 'max:255'],
        ]);

        if (array_key_exists('is_done', $validated)) {
            $done = (bool) $validated['is_done'];
            $task->fill([
                'is_done' => $done,
                'done_by' => $done ? $request->user()->id : null,
                'done_at' => $done ? now() : null,
            ]);
        }

        if (isset($validated['title'])) {
            $task->title = trim($validated['title']);
        }

        $task->save();

        return response()->json(['task' => $task->toPanelRow()]);
    }

    public function destroyTask(Ticket $ticket, TicketTask $task): JsonResponse
    {
        $this->authorize('update', $ticket);
        abort_unless($task->ticket_id === $ticket->id, 404);

        $task->delete();

        return response()->json(['deleted' => true]);
    }

    public function storeSubticket(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);
        $this->authorize('create', Ticket::class);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $create = function () use ($ticket, $validated, $request) {
            $child = Ticket::create([
                'subject' => $validated['subject'],
                'description' => $validated['description'] ?? '',
                'customer_id' => $ticket->customer_id,
                'category_id' => $ticket->category_id,
                'group_id' => $ticket->group_id,
                'priority' => $ticket->priority,
                'status_id' => TicketStatus::where('slug', 'new')->value('id') ?? $ticket->status_id,
                'assignee_id' => $validated['assignee_id'] ?? null,
                'assigned_at' => ($validated['assignee_id'] ?? null) ? now() : null,
                'source' => $ticket->source,
            ]);

            TicketLink::create([
                'ticket_id' => $child->id,
                'linked_ticket_id' => $ticket->id,
                'link_type' => 'subticket_of',
                'created_by' => $request->user()->id,
            ]);

            return $child;
        };

        // Misma guarda que MacroExecutor/Ticket::generateTicketNumber(): si
        // la conexión ya está en una transacción, se reutiliza.
        $connection = DB::connection('helpdesk');
        $child = $connection->getPdo()->inTransaction() ? $create() : $connection->transaction($create);

        // Sin aviso al cliente: es trabajo interno derivado de su ticket.
        TicketCreated::dispatch($child, false);

        return response()->json([
            'success' => true,
            'message' => "Subticket {$child->ticket_number} creado.",
            'ticket_id' => $child->id,
            'ticket_number' => $child->ticket_number,
        ], 201);
    }

    /**
     * Campos personalizados de la categoría, editables por el agente
     * (24-sep-2026). Antes solo se rellenaban desde el formulario público y
     * en el panel eran de solo lectura. Mismas reglas por tipo que
     * TicketCategoryValidationBuilder, sin los campos de fichero.
     */
    public function updateCustomFields(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $category = $ticket->category;
        abort_unless($category, 422, 'El ticket no tiene categoría.');

        $fields = $category->fields()->where('is_visible', true)->where('type', '!=', 'file')->ordered()->get();

        $rules = ['fields' => ['nullable', 'array']];
        $attributes = [];
        foreach ($fields as $field) {
            $fieldRules = $field->is_required ? ['required'] : ['nullable'];
            match ($field->type) {
                'email' => $fieldRules[] = 'email',
                'phone' => $fieldRules[] = 'regex:/^\+?[\d\s\-\(\)\.]+$/',
                'number' => $fieldRules[] = 'numeric',
                'date' => $fieldRules[] = 'date',
                'select', 'radio' => $field->options
                    ? $fieldRules[] = 'in:'.implode(',', array_column($field->options, 'value'))
                    : null,
                'checkbox' => $fieldRules[] = 'array',
                default => $fieldRules[] = 'string',
            };
            $rules['fields.'.$field->key] = $fieldRules;
            $attributes['fields.'.$field->key] = $field->label;
        }

        $validated = $request->validate($rules, [], $attributes);
        $incoming = collect($validated['fields'] ?? [])->only($fields->pluck('key'))->all();

        $current = $ticket->custom_fields ?: [];
        $changed = collect($incoming)->filter(fn ($v, $k) => ($current[$k] ?? null) != $v)->keys();

        if ($changed->isNotEmpty()) {
            $ticket->forceFill(['custom_fields' => array_merge($current, $incoming)])->save();

            $labels = $fields->whereIn('key', $changed->all())->pluck('label')->implode(', ');
            $ticket->items()->create([
                'type' => 'custom_fields_changed',
                'user_id' => $request->user()->id,
                'body' => 'Campos actualizados: '.$labels,
                'is_internal' => true,
            ]);
        }

        return response()->json(['success' => true, 'values' => $ticket->fresh()->custom_fields ?: []]);
    }
}
