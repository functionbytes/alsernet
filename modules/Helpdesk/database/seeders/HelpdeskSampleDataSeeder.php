<?php

namespace Modules\Helpdesk\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Database\Factories\ConversationFactory;
use Modules\Helpdesk\Database\Factories\ConversationItemFactory;
use Modules\Helpdesk\Database\Factories\CustomerFactory;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;

class HelpdeskSampleDataSeeder extends Seeder
{
    private const CUSTOMERS_COUNT = 25;

    private const CONVERSATIONS_COUNT = 60;

    private const SPANISH_NAMES = [
        'Carlos Mendoza', 'María González', 'Juan Rodríguez', 'Ana Martínez',
        'Luis Hernández', 'Sofia García', 'Pedro Jiménez', 'Laura López',
        'Andrés Torres', 'Carmen Flores', 'Diego Ramírez', 'Isabel Vargas',
        'Roberto Castillo', 'Valentina Morales', 'Javier Romero', 'Gabriela Soto',
        'Fernando Díaz', 'Natalia Reyes', 'Alejandro Cruz', 'Patricia Herrera',
        'Ricardo Núñez', 'Claudia Ortiz', 'Sebastián Guerrero', 'Mónica Aguilar',
        'Manuel Fuentes',
    ];

    private const SPANISH_CITIES = [
        'Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Zaragoza',
        'Málaga', 'Murcia', 'Palma', 'Las Palmas', 'Bilbao',
        'Ciudad de México', 'Guadalajara', 'Monterrey', 'Bogotá', 'Lima',
        'Santiago', 'Buenos Aires', 'Caracas', 'Quito', 'Medellín',
    ];

    private const INCOMING_MESSAGES = [
        'Hola, necesito ayuda con mi pedido.',
        'Buenos días, tengo un problema con mi cuenta.',
        'No puedo acceder al servicio, ¿pueden ayudarme?',
        '¿Cuándo recibiré mi pedido? Ya pasaron varios días.',
        'Quería consultar sobre los planes disponibles.',
        'Me cobraron dos veces el mismo mes, ¿pueden revisarlo?',
        'Necesito cambiar mi método de pago.',
        'El producto que recibí está defectuoso.',
        '¿Tienen disponibilidad para este fin de semana?',
        'Quisiera cancelar mi suscripción, por favor.',
        'No funciona la aplicación desde ayer.',
        'Tengo una pregunta sobre las políticas de devolución.',
        '¿Cómo puedo actualizar mis datos personales?',
        'Olvidé mi contraseña y no recibo el correo de recuperación.',
        'Necesito una factura del mes pasado.',
        '¿Hay algún descuento disponible para nuevos clientes?',
        'El servicio estuvo caído durante horas, ¿qué pasó?',
        'Recibí una notificación extraña, ¿es legítima?',
    ];

    private const OUTGOING_MESSAGES = [
        'Hola, gracias por contactarnos. Estamos revisando su caso.',
        'Entendemos su situación. Vamos a resolverlo de inmediato.',
        'Hemos recibido su solicitud y la estamos procesando.',
        'Le informamos que el inconveniente ya fue solucionado.',
        'Puede comunicarse con nosotros cuando lo necesite.',
        'Hemos enviado las instrucciones a su correo electrónico.',
        'El equipo técnico ya está trabajando en ello.',
        'Disculpe los inconvenientes causados, ya fue resuelto.',
        'Su pedido fue despachado hace unas horas y llegará pronto.',
        'Hemos realizado el ajuste en su cuenta correctamente.',
        'Por favor, confirme si el problema fue resuelto.',
        'Quedamos atentos a cualquier consulta adicional.',
        '¿Hay algo más en que podamos ayudarle?',
        'Gracias por su paciencia. El equipo ya fue notificado.',
    ];

    public function run(): void
    {
        if (Conversation::count() > 50) {
            $this->command->info('Skipping: more than 50 conversations already exist.');

            return;
        }

        $this->ensureStatusesExist();

        $statusIds = ConversationStatus::pluck('id', 'is_open')->toArray();
        $openStatusIds = ConversationStatus::where('is_open', true)->pluck('id')->toArray();
        $closedStatusIds = ConversationStatus::where('is_open', false)->pluck('id')->toArray();
        $allStatusIds = array_merge($openStatusIds, $closedStatusIds);

        $userIds = DB::connection(config('database.default'))->table('users')->pluck('id')->toArray();

        $customers = $this->createCustomers();
        $this->createConversations($customers, $allStatusIds, $openStatusIds, $closedStatusIds, $userIds);

        $customerCount = Customer::count();
        $conversationCount = Conversation::count();
        $itemCount = ConversationItem::count();

        $this->command->info("Sample data seeded: {$customerCount} customers, {$conversationCount} conversations, {$itemCount} items.");
    }

    private function ensureStatusesExist(): void
    {
        if (ConversationStatus::count() > 0) {
            return;
        }

        $this->call(HelpdeskConversationStatusSeeder::class);
    }

    /** @return Collection<int, Customer> */
    private function createCustomers(): Collection
    {
        $customers = collect();

        foreach (self::SPANISH_NAMES as $index => $name) {
            $nameParts = explode(' ', $name);
            $firstName = strtolower($nameParts[0]);
            $lastName = strtolower($nameParts[1] ?? 'cliente');
            $email = "{$firstName}.{$lastName}{$index}@ejemplo.com";

            $cityIndex = $index % count(self::SPANISH_CITIES);
            $city = self::SPANISH_CITIES[$cityIndex];

            $customer = CustomerFactory::new()->create([
                'name' => $name,
                'email' => $email,
                'phone' => '+34 6'.str_pad((string) fake()->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'city' => $city,
                'country' => 'ES',
                'language' => 'es',
                'email_verified_at' => $index % 3 !== 0 ? now()->subDays(fake()->numberBetween(1, 365)) : null,
                'last_seen_at' => now()->subHours(fake()->numberBetween(1, 720)),
                'total_conversations' => 0,
            ]);

            $customers->push($customer);
        }

        return $customers;
    }

    private function createConversations(
        Collection $customers,
        array $allStatusIds,
        array $openStatusIds,
        array $closedStatusIds,
        array $userIds
    ): void {
        $channels = ['whatsapp', 'facebook', 'instagram', 'email', 'web'];
        $priorities = ['low', 'normal', 'normal', 'normal', 'high', 'urgent'];

        for ($i = 0; $i < self::CONVERSATIONS_COUNT; $i++) {
            $customer = $customers->random();
            $channel = $channels[$i % count($channels)];
            $priority = fake()->randomElement($priorities);
            $lastMessageAt = $this->distributedLastMessageAt($i);
            $isClosed = $i % 4 === 0;

            $statusId = $isClosed
                ? fake()->randomElement($closedStatusIds ?: $allStatusIds)
                : fake()->randomElement($openStatusIds ?: $allStatusIds);

            $assigneeId = null;
            if (! empty($userIds) && $i % 3 !== 0) {
                $assigneeId = fake()->randomElement($userIds);
            }

            $conversation = ConversationFactory::new()->create([
                'customer_id' => $customer->id,
                'subject' => $this->subjectForChannel($channel, $customer->name),
                'channel' => $channel,
                'priority' => $priority,
                'status_id' => $statusId,
                'assignee_id' => $assigneeId,
                'assigned_at' => $assigneeId ? $lastMessageAt->copy()->subMinutes(fake()->numberBetween(5, 120)) : null,
                'last_message_at' => $lastMessageAt,
                'closed_at' => $isClosed ? $lastMessageAt->copy()->addMinutes(fake()->numberBetween(10, 1440)) : null,
                'tags' => null,
                'external_id' => in_array($channel, ['whatsapp', 'facebook', 'instagram'])
                    ? (string) fake()->numberBetween(100000000, 999999999)
                    : null,
            ]);

            $this->createItems($conversation, $customer->id, $userIds, $lastMessageAt);
        }
    }

    private function createItems(
        Conversation $conversation,
        int $customerId,
        array $userIds,
        Carbon $lastMessageAt
    ): void {
        $count = fake()->numberBetween(3, 12);
        $startedAt = $lastMessageAt->copy()->subMinutes($count * fake()->numberBetween(5, 30));

        for ($j = 0; $j < $count; $j++) {
            $isOutgoing = $j % 2 !== 0 && ! empty($userIds);
            $createdAt = $startedAt->copy()->addMinutes($j * fake()->numberBetween(5, 30));

            ConversationItemFactory::new()->create([
                'conversation_id' => $conversation->id,
                'type' => 'message',
                'author_id' => $isOutgoing ? null : $customerId,
                'user_id' => $isOutgoing ? fake()->randomElement($userIds) : null,
                'body' => $isOutgoing
                    ? fake()->randomElement(self::OUTGOING_MESSAGES)
                    : fake()->randomElement(self::INCOMING_MESSAGES),
                'is_internal' => false,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function distributedLastMessageAt(int $index): Carbon
    {
        // Spread across today / yesterday / last week / older for realistic grouping
        return match (true) {
            $index < 10 => now()->subMinutes(fake()->numberBetween(1, 1439)),       // today
            $index < 20 => now()->subHours(fake()->numberBetween(24, 47)),          // yesterday
            $index < 35 => now()->subDays(fake()->numberBetween(2, 6)),             // this week
            default => now()->subDays(fake()->numberBetween(7, 30)),            // older
        };
    }

    private function subjectForChannel(string $channel, string $customerName): string
    {
        $subjects = [
            'whatsapp' => [
                "Consulta de {$customerName} por WhatsApp",
                'Problema con pedido vía WhatsApp',
                'Soporte técnico WhatsApp',
            ],
            'facebook' => [
                'Mensaje desde Facebook Messenger',
                "Consulta de {$customerName} por Messenger",
                'Soporte por Facebook',
            ],
            'instagram' => [
                'DM desde Instagram',
                "Consulta de {$customerName} por Instagram",
                'Mensaje directo Instagram',
            ],
            'email' => [
                "Solicitud de soporte de {$customerName}",
                'Consulta por correo electrónico',
                'Reporte de incidencia por email',
            ],
            'web' => [
                'Chat en vivo desde el sitio web',
                "Consulta de {$customerName} en el chat",
                'Soporte en tiempo real',
            ],
        ];

        return fake()->randomElement($subjects[$channel] ?? ["Consulta de {$customerName}"]);
    }
}
