<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Log\Events\MessageLogged;
use Modules\Activity\Models\ApplicationLog;

class LogToDatabase
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageLogged $event): void
    {
        // Solo guardar logs WARNING y superiores
        $levels = ['warning', 'error', 'critical', 'alert', 'emergency'];

        if (! in_array($event->level, $levels)) {
            return;
        }

        try {
            $request = request();
            $user_id = null;

            if (auth()->check()) {
                $user_id = auth()->id();
            }

            // Extraer stack trace si existe
            $stack_trace = null;
            if (isset($event->context['exception'])) {
                $stack_trace = (string) $event->context['exception'];
            }

            ApplicationLog::create([
                'level' => strtoupper($event->level),
                'channel' => $event->channel ?? 'default',
                'message' => $event->message,
                'context' => json_encode($event->context ?? []),
                'stack_trace' => $stack_trace,
                'user_id' => $user_id,
                'ip_address' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        } catch (\Throwable $e) {
            // Silenciar errores para no romper el flujo de logging
            error_log('Failed to log to database: '.$e->getMessage());
        }
    }
}
