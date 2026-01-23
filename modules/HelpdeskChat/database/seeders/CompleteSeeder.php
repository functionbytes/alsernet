<?php

namespace Modules\HelpdeskChat\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Automations\Automation;
use Modules\HelpdeskChat\Models\CannedResponse;
use Modules\HelpdeskChat\Models\Channels\Email;
use Modules\HelpdeskChat\Models\Channels\Facebook;
use Modules\HelpdeskChat\Models\Channels\Instagram;
use Modules\HelpdeskChat\Models\Channels\Web;
use Modules\HelpdeskChat\Models\Channels\Whatsapp;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Models\Label;
use Modules\HelpdeskChat\Models\Macro;
use Modules\HelpdeskChat\Models\Teams\Team;

class CompleteSeeder extends Seeder
{
    private Account $account;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeding completo de Chat...');

        // 1. Create Account
        $this->account = Account::firstOrCreate(
            ['name' => 'Acme Corporation']
        );
        $this->command->info('✅ Cuenta creada: '.$this->account->name);

        // 2. Create Users
        $users = $this->seedUsers();
        $this->command->info('✅ Usuarios creados: '.count($users));

        // 3. Create Teams
        $teams = $this->seedTeams($users);
        $this->command->info('✅ Equipos creados: '.count($teams));

        // 4. Create Labels
        $labels = $this->seedLabels();
        $this->command->info('✅ Etiquetas creadas: '.count($labels));

        // 5-7. Skipping Canned Responses, Macros, and Automations for now
        // (These models need PSR-4 compliance fixes)
        $this->command->info('⏭️  Saltando Canneds, Macros y Automations (pendientes de ajuste)');

        // 8. Create Channels & Inboxes
        $inboxes = $this->seedChannelsAndInboxes();
        $this->command->info('✅ Canales e inboxes creados: '.count($inboxes));

        // 9. Create Contacts
        $contacts = $this->seedContacts();
        $this->command->info('✅ Contactos creados: '.count($contacts));

        // 10. Create Conversations & Messages
        $conversations = $this->seedConversationsAndMessages($inboxes, $contacts, $users, $teams, $labels);
        $this->command->info('✅ Conversaciones creadas: '.count($conversations));

        $this->command->info('');
        $this->command->info('🎉 ¡Seeding completado exitosamente!');
        $this->command->info('');
        $this->command->info('👤 Credenciales de acceso:');
        $this->command->info('   Admin:    admin@acme.test / password');
        $this->command->info('   Agente 1: maria@acme.test / password');
        $this->command->info('   Agente 2: carlos@acme.test / password');
        $this->command->info('   Agente 3: ana@acme.test / password');
        $this->command->info('   Agente 4: pedro@acme.test / password');
    }

    private function seedUsers(): array
    {
        $users = [];

        // Admin
        $users['admin'] = User::firstOrCreate(
            ['email' => 'admin@acme.test'],
            [
                'account_id' => $this->account->id,
                'name' => 'Admin Principal',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Agents
        $agents = [
            ['name' => 'María García', 'email' => 'maria@acme.test'],
            ['name' => 'Carlos Rodríguez', 'email' => 'carlos@acme.test'],
            ['name' => 'Ana Martínez', 'email' => 'ana@acme.test'],
            ['name' => 'Pedro López', 'email' => 'pedro@acme.test'],
            ['name' => 'Laura Fernández', 'email' => 'laura@acme.test'],
        ];

        foreach ($agents as $agentData) {
            $users[$agentData['email']] = User::firstOrCreate(
                ['email' => $agentData['email']],
                [
                    'account_id' => $this->account->id,
                    'name' => $agentData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        return $users;
    }

    private function seedTeams(array $users): array
    {
        $teams = [];

        $teamsData = [
            [
                'name' => 'Soporte Técnico',
                'description' => 'Equipo encargado de resolver problemas técnicos y bugs',
                'allow_auto_assign' => true,
                'members' => ['maria@acme.test', 'carlos@acme.test'],
            ],
            [
                'name' => 'Ventas',
                'description' => 'Equipo de ventas y consultas comerciales',
                'allow_auto_assign' => true,
                'members' => ['ana@acme.test', 'pedro@acme.test'],
            ],
            [
                'name' => 'Atención al Cliente',
                'description' => 'Equipo de atención general y consultas',
                'allow_auto_assign' => false,
                'members' => ['laura@acme.test', 'maria@acme.test'],
            ],
        ];

        foreach ($teamsData as $teamData) {
            $team = Team::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'name' => $teamData['name'],
                ],
                [
                    'description' => $teamData['description'],
                    'allow_auto_assign' => $teamData['allow_auto_assign'],
                ]
            );

            // Attach members
            $memberIds = [];
            foreach ($teamData['members'] as $email) {
                if (isset($users[$email])) {
                    $memberIds[] = $users[$email]->id;
                }
            }
            $team->members()->sync($memberIds);

            $teams[] = $team;
        }

        return $teams;
    }

    private function seedLabels(): array
    {
        $labels = [];

        $labelsData = [
            ['title' => 'Bug', 'color' => '#e74c3c', 'description' => 'Reportes de errores y bugs', 'show_on_sidebar' => true],
            ['title' => 'Pregunta', 'color' => '#3498db', 'description' => 'Consultas generales', 'show_on_sidebar' => true],
            ['title' => 'Sugerencia', 'color' => '#9b59b6', 'description' => 'Sugerencias de mejora', 'show_on_sidebar' => true],
            ['title' => 'Urgente', 'color' => '#e67e22', 'description' => 'Requiere atención inmediata', 'show_on_sidebar' => true],
            ['title' => 'Facturación', 'color' => '#2ecc71', 'description' => 'Temas relacionados con facturación', 'show_on_sidebar' => true],
            ['title' => 'Cancelación', 'color' => '#95a5a6', 'description' => 'Solicitudes de cancelación', 'show_on_sidebar' => false],
            ['title' => 'VIP', 'color' => '#f39c12', 'description' => 'Clientes VIP', 'show_on_sidebar' => true],
            ['title' => 'Seguimiento', 'color' => '#1abc9c', 'description' => 'Requiere seguimiento', 'show_on_sidebar' => false],
        ];

        foreach ($labelsData as $labelData) {
            $labels[] = Label::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'title' => $labelData['title'],
                ],
                [
                    'color' => $labelData['color'],
                    'description' => $labelData['description'],
                    'show_on_sidebar' => $labelData['show_on_sidebar'],
                ]
            );
        }

        return $labels;
    }

    private function seedCannedResponses(): array
    {
        $responses = [];

        $responsesData = [
            [
                'short_code' => 'hola',
                'content' => '¡Hola! Gracias por contactarnos. ¿En qué puedo ayudarte hoy?',
            ],
            [
                'short_code' => 'espera',
                'content' => 'Déjame revisar eso por ti. Un momento por favor.',
            ],
            [
                'short_code' => 'gracias',
                'content' => '¡De nada! Si tienes alguna otra pregunta, no dudes en contactarnos. ¡Que tengas un excelente día!',
            ],
            [
                'short_code' => 'horario',
                'content' => 'Nuestro horario de atención es de Lunes a Viernes de 9:00 AM a 6:00 PM (hora local).',
            ],
            [
                'short_code' => 'refund',
                'content' => 'Para procesar tu reembolso, necesito algunos detalles. ¿Podrías proporcionarme tu número de pedido?',
            ],
            [
                'short_code' => 'tecnico',
                'content' => 'Voy a transferirte con nuestro equipo técnico que podrá ayudarte mejor con este problema.',
            ],
            [
                'short_code' => 'precio',
                'content' => 'Puedes ver todos nuestros planes y precios en https://acme.test/pricing. ¿Hay algún plan específico que te interese?',
            ],
        ];

        foreach ($responsesData as $responseData) {
            $responses[] = CannedResponse::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'short_code' => $responseData['short_code'],
                ],
                [
                    'content' => $responseData['content'],
                ]
            );
        }

        return $responses;
    }

    private function seedMacros(array $teams, array $labels, array $users): array
    {
        $macros = [];

        $macrosData = [
            [
                'name' => 'Asignar a Soporte Técnico',
                'actions' => [
                    ['action_name' => 'assign_team', 'action_params' => ['team_id' => $teams[0]->id ?? null]],
                    ['action_name' => 'add_label', 'action_params' => ['labels' => ['Bug']]],
                ],
            ],
            [
                'name' => 'Marcar como VIP y Alta Prioridad',
                'actions' => [
                    ['action_name' => 'add_label', 'action_params' => ['labels' => ['VIP']]],
                    ['action_name' => 'change_priority', 'action_params' => ['priority' => 'urgent']],
                ],
            ],
            [
                'name' => 'Resolver y Agradecer',
                'actions' => [
                    ['action_name' => 'change_status', 'action_params' => ['status' => 'resolved']],
                    ['action_name' => 'send_message', 'action_params' => ['message' => '¡Gracias por contactarnos! Tu caso ha sido resuelto.']],
                ],
            ],
        ];

        foreach ($macrosData as $macroData) {
            $macros[] = Macro::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'name' => $macroData['name'],
                ],
                [
                    'actions' => $macroData['actions'],
                    'visibility' => 'global',
                    'created_by_id' => $users['admin']->id ?? null,
                    'updated_by_id' => $users['admin']->id ?? null,
                ]
            );
        }

        return $macros;
    }

    private function seedAutomationRules(array $teams, array $labels, array $users): array
    {
        $rules = [];

        $rulesData = [
            [
                'name' => 'Auto-asignar bugs a Soporte Técnico',
                'description' => 'Asigna automáticamente conversaciones con la etiqueta Bug al equipo de soporte',
                'event_name' => 'conversation_created',
                'conditions' => [
                    ['attribute_key' => 'message_type', 'filter_operator' => 'equal_to', 'values' => ['incoming']],
                ],
                'actions' => [
                    ['action_name' => 'assign_team', 'action_params' => ['team_id' => $teams[0]->id ?? null]],
                ],
            ],
            [
                'name' => 'Marcar como urgente si contiene palabra clave',
                'description' => 'Marca como urgente si el mensaje contiene urgente, crítico o emergencia',
                'event_name' => 'message_created',
                'conditions' => [
                    ['attribute_key' => 'content', 'filter_operator' => 'contains', 'values' => ['urgente', 'crítico', 'emergencia']],
                ],
                'actions' => [
                    ['action_name' => 'change_priority', 'action_params' => ['priority' => 'urgent']],
                    ['action_name' => 'add_label', 'action_params' => ['labels' => ['Urgente']]],
                ],
            ],
        ];

        foreach ($rulesData as $ruleData) {
            $rules[] = Automation::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'name' => $ruleData['name'],
                ],
                [
                    'description' => $ruleData['description'],
                    'event_name' => $ruleData['event_name'],
                    'conditions' => $ruleData['conditions'],
                    'actions' => $ruleData['actions'],
                    'active' => true,
                ]
            );
        }

        return $rules;
    }

    private function seedChannelsAndInboxes(): array
    {
        $inboxes = [];

        // 1. Web Widget
        $widget = Web::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'website_url' => 'https://acme.test',
            ],
            [
                'website_token' => \Illuminate\Support\Str::random(32),
                'hmac_token' => \Illuminate\Support\Str::random(32),
                'widget_color' => '#1f93ff',
                'welcome_title' => '¡Hola! 👋',
                'welcome_tagline' => '¿Cómo podemos ayudarte hoy?',
                'pre_chat_form_enabled' => true,
                'pre_chat_form_options' => [
                    'require_email' => true,
                    'pre_chat_message' => '¡Gracias por contactarnos! Por favor comparte tu información.',
                ],
            ]
        );

        $inboxes[] = Inbox::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'channel_id' => $widget->id,
                'channel_type' => Web::class,
            ],
            [
                'name' => 'Chat del Sitio Web',
                'timezone' => 'America/Mexico_City',
            ]
        );

        // 2. Email Channel
        $email = Email::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'email' => 'soporte@acme.test',
            ],
            [
                'forward_to_email' => 'channel+soporte@acme.test',
                'imap_enabled' => false,
                'smtp_enabled' => false,
                'provider' => 'custom',
            ]
        );

        $inboxes[] = Inbox::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'channel_id' => $email->id,
                'channel_type' => Email::class,
            ],
            [
                'name' => 'Email Soporte',
                'timezone' => 'America/Mexico_City',
            ]
        );

        // 3. Facebook Page
        $facebook = Facebook::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'page_id' => 'demo_page_123456',
            ],
            [
                'page_name' => 'Acme Corporation',
                'user_access_token' => 'demo_token_'.uniqid(),
                'page_access_token' => 'demo_page_token_'.uniqid(),
            ]
        );

        $inboxes[] = Inbox::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'channel_id' => $facebook->id,
                'channel_type' => Facebook::class,
            ],
            [
                'name' => 'Facebook Messenger',
                'timezone' => 'America/Mexico_City',
            ]
        );

        // 4. Instagram
        $instagram = Instagram::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'instagram_id' => 'demo_ig_123456',
            ],
            [
                'username' => 'acmecorp',
                'user_access_token' => 'demo_ig_token_'.uniqid(),
                'page_access_token' => 'demo_page_token_'.uniqid(),
            ]
        );

        $inboxes[] = Inbox::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'channel_id' => $instagram->id,
                'channel_type' => Instagram::class,
            ],
            [
                'name' => 'Instagram DMs',
                'timezone' => 'America/Mexico_City',
            ]
        );

        // 5. WhatsApp
        $whatsapp = Whatsapp::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'phone_number' => '+525512345678',
            ],
            [
                'provider' => '360dialog',
                'provider_config' => [
                    'api_key' => 'demo_wa_key_'.uniqid(),
                ],
            ]
        );

        $inboxes[] = Inbox::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'channel_id' => $whatsapp->id,
                'channel_type' => Whatsapp::class,
            ],
            [
                'name' => 'WhatsApp Business',
                'timezone' => 'America/Mexico_City',
            ]
        );

        return $inboxes;
    }

    private function seedContacts(): array
    {
        $contacts = [];

        $contactsData = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@example.com',
                'phone_number' => '+525512345601',
                'additional_attributes' => [
                    'subscription_plan' => 'Pro',
                    'industry' => 'Tecnología',
                    'company_size' => '51-200',
                    'company' => 'Tech Solutions SA',
                ],
            ],
            [
                'name' => 'María González',
                'email' => 'maria.gonzalez@example.com',
                'phone_number' => '+525512345602',
                'additional_attributes' => [
                    'subscription_plan' => 'Enterprise',
                    'industry' => 'Finanzas',
                    'company_size' => '201-1000',
                    'company' => 'Banco Nacional',
                ],
            ],
            [
                'name' => 'Carlos Ramírez',
                'email' => 'carlos.ramirez@example.com',
                'phone_number' => '+525512345603',
                'additional_attributes' => [
                    'subscription_plan' => 'Basic',
                    'industry' => 'Retail',
                    'company_size' => '11-50',
                    'company' => 'Tiendas MX',
                ],
            ],
            [
                'name' => 'Ana Torres',
                'email' => 'ana.torres@example.com',
                'additional_attributes' => [
                    'subscription_plan' => 'Free',
                    'industry' => 'Educación',
                    'company_size' => '1-10',
                ],
            ],
            [
                'name' => 'Roberto Silva',
                'email' => 'roberto.silva@example.com',
                'phone_number' => '+525512345605',
                'additional_attributes' => [
                    'subscription_plan' => 'Pro',
                    'industry' => 'Salud',
                    'company_size' => '51-200',
                    'company' => 'HealthCare Plus',
                ],
            ],
            [
                'name' => 'Lucía Mendoza',
                'email' => 'lucia.mendoza@example.com',
                'additional_attributes' => [
                    'subscription_plan' => 'Basic',
                    'industry' => 'Tecnología',
                ],
            ],
            [
                'name' => 'Fernando Castro',
                'email' => 'fernando.castro@example.com',
                'phone_number' => '+525512345607',
                'additional_attributes' => [
                    'subscription_plan' => 'Enterprise',
                    'industry' => 'Finanzas',
                    'company_size' => '1000+',
                    'company' => 'Global Finance Corp',
                ],
            ],
        ];

        foreach ($contactsData as $contactData) {
            $contacts[] = Contact::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'email' => $contactData['email'],
                ],
                [
                    'name' => $contactData['name'],
                    'phone_number' => $contactData['phone_number'] ?? null,
                    'additional_attributes' => $contactData['additional_attributes'] ?? [],
                ]
            );
        }

        return $contacts;
    }

    private function seedConversationsAndMessages(array $inboxes, array $contacts, array $users, array $teams, array $labels): array
    {
        $conversations = [];

        // Get some users for assignment (exclude admin, get agents)
        $agentsList = array_values(array_filter($users, fn ($user) => $user->email !== 'admin@acme.test'));

        // Conversation 1: Web Widget - Open - High Priority - With Team
        $contactInbox1 = ContactInbox::firstOrCreate([
            'contact_id' => $contacts[0]->id,
            'inbox_id' => $inboxes[0]->id,
            'source_id' => 'web_'.uniqid(),
        ]);

        $conv1 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'contact_id' => $contacts[0]->id,
            'contact_inbox_id' => $contactInbox1->id,
            'status' => 'open',
            'priority' => 'high',
            'assignee_id' => $agentsList[0]->id ?? null,
            'team_id' => $teams[0]->id ?? null,
            'cached_label_list' => 'Bug,Urgente',
            'last_activity_at' => now(),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'conversation_id' => $conv1->id,
            'sender_id' => $contacts[0]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => '¡Hola! Estoy experimentando un error crítico en la aplicación. Cuando intento cargar mi dashboard, aparece una pantalla en blanco.',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'conversation_id' => $conv1->id,
            'sender_id' => $agentsList[0]->id ?? $users['admin']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Hola Juan, lamento escuchar eso. Déjame revisar tu cuenta. ¿Podrías decirme qué navegador estás usando?',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'conversation_id' => $conv1->id,
            'sender_id' => $contacts[0]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Estoy usando Chrome, versión más reciente.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv1;

        // Conversation 2: Email - Pending - Medium Priority
        $contactInbox2 = ContactInbox::firstOrCreate([
            'contact_id' => $contacts[1]->id,
            'inbox_id' => $inboxes[1]->id,
            'source_id' => $contacts[1]->email,
        ]);

        $conv2 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'contact_id' => $contacts[1]->id,
            'contact_inbox_id' => $contactInbox2->id,
            'status' => 'pending',
            'priority' => 'medium',
            'assignee_id' => $agentsList[1]->id ?? null,
            'team_id' => $teams[1]->id ?? null,
            'cached_label_list' => 'Facturación',
            'snoozed_until' => now()->addHours(24),
            'last_activity_at' => now()->subHours(2),
            'custom_attributes' => [
                'subject' => 'Consulta sobre facturación Enterprise',
                'acquisition_source' => 'Google Ads',
            ],
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'conversation_id' => $conv2->id,
            'sender_id' => $contacts[1]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Estimados, necesito información sobre la facturación del plan Enterprise. ¿Ofrecen descuentos por pago anual?',
            'status' => 'sent',
            'source_id' => '<msg-'.uniqid().'@example.com>',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'conversation_id' => $conv2->id,
            'sender_id' => $agentsList[1]->id ?? $users['admin']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Hola María, gracias por tu interés. Sí, ofrecemos un 20% de descuento en planes anuales Enterprise. Déjame prepararte una cotización personalizada.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv2;

        // Conversation 3: Facebook - Resolved - Low Priority
        $contactInbox3 = ContactInbox::firstOrCreate([
            'contact_id' => $contacts[2]->id,
            'inbox_id' => $inboxes[2]->id,
            'source_id' => 'fb_'.uniqid(),
        ]);

        $conv3 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[2]->id,
            'contact_id' => $contacts[2]->id,
            'contact_inbox_id' => $contactInbox3->id,
            'status' => 'resolved',
            'priority' => 'low',
            'assignee_id' => $agentsList[2]->id ?? null,
            'cached_label_list' => 'Pregunta',
            'last_activity_at' => now()->subDays(1),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[2]->id,
            'conversation_id' => $conv3->id,
            'sender_id' => $contacts[2]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => '¿Cuál es el horario de atención?',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[2]->id,
            'conversation_id' => $conv3->id,
            'sender_id' => $agentsList[2]->id ?? $users['admin']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Nuestro horario es de Lunes a Viernes de 9:00 AM a 6:00 PM (hora de México). ¿Hay algo más en lo que pueda ayudarte?',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[2]->id,
            'conversation_id' => $conv3->id,
            'sender_id' => $contacts[2]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'No, eso es todo. ¡Gracias!',
            'status' => 'sent',
        ]);

        $conversations[] = $conv3;

        // Conversation 4: WhatsApp - Open - Urgent - VIP
        $contactInbox4 = ContactInbox::firstOrCreate([
            'contact_id' => $contacts[6]->id,
            'inbox_id' => $inboxes[4]->id,
            'source_id' => 'wa_'.uniqid(),
        ]);

        $conv4 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[4]->id,
            'contact_id' => $contacts[6]->id,
            'contact_inbox_id' => $contactInbox4->id,
            'status' => 'open',
            'priority' => 'urgent',
            'assignee_id' => $users['admin']->id,
            'team_id' => $teams[1]->id ?? null,
            'cached_label_list' => 'VIP,Urgente',
            'last_activity_at' => now()->subMinutes(5),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[4]->id,
            'conversation_id' => $conv4->id,
            'sender_id' => $contacts[6]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Urgente: Necesito una renovación inmediata de nuestra licencia Enterprise. Expira en 2 días.',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[4]->id,
            'conversation_id' => $conv4->id,
            'sender_id' => $users['admin']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Fernando, entiendo la urgencia. Estoy procesando tu renovación ahora mismo. Te enviaré el link de pago en los próximos 10 minutos.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv4;

        // Conversation 5: Instagram - Pending - High Priority
        $contactInbox5 = ContactInbox::firstOrCreate([
            'contact_id' => $contacts[3]->id,
            'inbox_id' => $inboxes[3]->id,
            'source_id' => 'ig_'.uniqid(),
        ]);

        $conv5 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[3]->id,
            'contact_id' => $contacts[3]->id,
            'contact_inbox_id' => $contactInbox5->id,
            'status' => 'pending',
            'priority' => 'high',
            'assignee_id' => $agentsList[3]->id ?? null,
            'cached_label_list' => 'Sugerencia,Seguimiento',
            'last_activity_at' => now()->subHours(6),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[3]->id,
            'conversation_id' => $conv5->id,
            'sender_id' => $contacts[3]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Me encantaría que agregaran integración con Zapier. ¿Está en el roadmap?',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[3]->id,
            'conversation_id' => $conv5->id,
            'sender_id' => $agentsList[3]->id ?? $users['admin']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => '¡Excelente sugerencia Ana! Sí, Zapier está en nuestro roadmap para Q2. Te mantendré informada del progreso.',
            'status' => 'sent',
        ]);

        // Private note
        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[3]->id,
            'conversation_id' => $conv5->id,
            'sender_id' => $agentsList[3]->id ?? $users['admin']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Nota: Cliente educación, buen candidato para beta testing de Zapier.',
            'status' => 'sent',
            'private' => true,
        ]);

        $conversations[] = $conv5;

        // Conversation 6: Web Widget - Open - No Assignment (Unattended)
        $contactInbox6 = ContactInbox::firstOrCreate([
            'contact_id' => $contacts[4]->id,
            'inbox_id' => $inboxes[0]->id,
            'source_id' => 'web_'.uniqid(),
        ]);

        $conv6 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'contact_id' => $contacts[4]->id,
            'contact_inbox_id' => $contactInbox6->id,
            'status' => 'open',
            'priority' => 'medium',
            'cached_label_list' => 'Pregunta',
            'last_activity_at' => now()->subMinutes(30),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'conversation_id' => $conv6->id,
            'sender_id' => $contacts[4]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => '¿Tienen API disponible para integraciones personalizadas?',
            'status' => 'sent',
        ]);

        $conversations[] = $conv6;

        // Conversation 7: Email - Resolved - Cancellation
        $contactInbox7 = ContactInbox::firstOrCreate([
            'contact_id' => $contacts[5]->id,
            'inbox_id' => $inboxes[1]->id,
            'source_id' => $contacts[5]->email,
        ]);

        $conv7 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'contact_id' => $contacts[5]->id,
            'contact_inbox_id' => $contactInbox7->id,
            'status' => 'resolved',
            'priority' => 'low',
            'assignee_id' => $agentsList[0]->id ?? null,
            'cached_label_list' => 'Cancelación',
            'last_activity_at' => now()->subDays(3),
            'custom_attributes' => [
                'subject' => 'Cancelación de suscripción',
            ],
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'conversation_id' => $conv7->id,
            'sender_id' => $contacts[5]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Hola, quisiera cancelar mi suscripción Basic. Ya no lo estoy usando.',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'conversation_id' => $conv7->id,
            'sender_id' => $agentsList[0]->id ?? $users['admin']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Lamento que te vayas Lucía. Tu cancelación ha sido procesada. ¿Hay algo que podríamos haber hecho mejor?',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'conversation_id' => $conv7->id,
            'sender_id' => $contacts[5]->id,
            'sender_type' => Contact::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'No, el servicio estuvo bien. Simplemente cambié de proyecto. Gracias.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv7;

        return $conversations;
    }
}
