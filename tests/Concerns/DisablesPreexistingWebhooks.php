<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * La BD de test es una copia con webhooks salientes activos preexistentes
 * (host example.com). Con la cola en `sync`, cualquier evento de conversación
 * los dispara de verdad y DispatchWebhookJob lanza "HTTP 405" cuando
 * example.com responde a un POST. Los tests que necesitan webhooks los crean
 * ellos mismos; los de la copia se apagan aquí.
 *
 * Solo actúa dentro de una transacción de test (DatabaseTransactions), que se
 * revierte sola: fuera de una nunca toca la copia. Llamar tras parent::setUp().
 */
trait DisablesPreexistingWebhooks
{
    protected function disablePreexistingWebhooks(): void
    {
        $connection = DB::connection('helpdesk');

        if ($connection->transactionLevel() < 1) {
            return;
        }

        $connection->table('helpdesk_webhooks')->where('is_active', true)->update(['is_active' => false]);
    }
}
