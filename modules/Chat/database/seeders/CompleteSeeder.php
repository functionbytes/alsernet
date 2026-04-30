<?php

namespace Modules\Chat\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Automations\Automation;
use Modules\Chat\Models\Canneds\Canned;
use Modules\Chat\Models\Channels\Email;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Channels\Instagram;
use Modules\Chat\Models\Channels\Web;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Conversations\ConversationPriority;
use Modules\Chat\Models\Conversations\ConversationSession;
use Modules\Chat\Models\Conversations\ConversationStatus;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerActivity;
use Modules\Chat\Models\Customers\CustomerAttribute;
use Modules\Chat\Models\Customers\CustomerInbox;
use Modules\Chat\Models\Customers\CustomerNote;
use Modules\Chat\Models\Customers\CustomerSegment;
use Modules\Chat\Models\Customers\CustomerSession;
use Modules\Chat\Models\Customers\PageVisit;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;
use Modules\Chat\Models\Helpcenters\HelpcenterTag;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Inbox\InboxHour;
use Modules\Chat\Models\Macro;
use Modules\Chat\Models\Support\CustomerLabel;
use Modules\Chat\Models\Teams\Team;

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

        // 2. Create Users (with account_id)
        $users = $this->seedUsers($this->account->id);
        $this->command->info('✅ Usuarios creados: '.count($users));

        // 3. Create Teams
        $teams = $this->seedTeams($users);
        $this->command->info('✅ Equipos creados: '.count($teams));

        // 4. Create Labels
        $labels = $this->seedLabels();
        $this->command->info('✅ Etiquetas creadas: '.count($labels));

        // 5. Create Custom Attributes
        $customAttributes = $this->seedCustomAttributes();
        $this->command->info('✅ Atributos personalizados creados: '.count($customAttributes));

        // 6. Create Canned Responses
        $cannedResponses = $this->seedCannedResponses();
        $this->command->info('✅ Respuestas predefinidas creadas: '.count($cannedResponses));

        // 7. Create Macros
        $macros = $this->seedMacros($teams, $labels, $users);
        $this->command->info('✅ Macros creadas: '.count($macros));

        // 8. Create Automation Rules
        $automationRules = $this->seedAutomationRules($teams, $labels);
        $this->command->info('✅ Reglas de automatización creadas: '.count($automationRules));

        // 9. Create Channels & Inboxes
        $inboxes = $this->seedChannelsAndInboxes();
        $this->command->info('✅ Canales e inboxes creados: '.count($inboxes));

        // 10. Create Customers
        $customers = $this->seedCustomers();
        $this->command->info('✅ Clientes creados: '.count($customers));

        // 11. Create Customer Sessions
        $customerSessions = $this->seedCustomerSessions($customers);
        $this->command->info('✅ Sesiones de clientes creadas: '.count($customerSessions));

        // 12. Create Business Hours
        $hours = $this->seedBusinessHours($inboxes);
        $this->command->info('✅ Horarios de atención creados: '.count($hours));

        // 13. Create Helpcenter (Categories & Articles)
        $helpcenter = $this->seedHelpcenter($users);
        $this->command->info('✅ Centro de ayuda creado: '.$helpcenter['categories'].' categorías, '.$helpcenter['articles'].' artículos');

        // 14. Create Customer Notes & Activities
        $notes = $this->seedCustomerNotesAndActivities($customers, $users);
        $this->command->info('✅ Notas y actividades de clientes: '.$notes['notes'].' notas, '.$notes['activities'].' actividades');

        // 15. Create Customer Segments & Labels
        $segments = $this->seedCustomerSegmentsAndLabels($customers, $labels, $users);
        $this->command->info('✅ Segmentos creados: '.count($segments).' segmentos');

        // 16. Create Page Visits
        $pageVisits = $this->seedPageVisits($customers, $customerSessions);
        $this->command->info('✅ Visitas de página creadas: '.count($pageVisits));

        // 17. Create Conversations & Messages
        $conversations = $this->seedConversationsAndMessages($inboxes, $customers, $users, $teams, $labels);
        $this->command->info('✅ Conversaciones creadas: '.count($conversations));

        // 18. Create Conversation Sessions
        $sessions = $this->seedConversationSessions($conversations, $customers);
        $this->command->info('✅ Sesiones de conversación creadas: '.count($sessions));

        $this->command->info('');
        $this->command->info('🎉 ¡Seeding completado exitosamente!');
        $this->command->info('');
        $this->command->info('👤 Credenciales de acceso:');
        $this->command->info('   Admin:    chats@acme.test / password');
        $this->command->info('   Agente 1: maria@acme.test / password');
        $this->command->info('   Agente 2: carlos@acme.test / password');
        $this->command->info('   Agente 3: ana@acme.test / password');
        $this->command->info('   Agente 4: pedro@acme.test / password');
    }

    private function seedUsers(int $accountId): array
    {
        $users = [];

        // Settings
        $userData = [
            'firstname' => 'Settings',
            'lastname' => 'Principal',
            'password' => Hash::make('password'),
            'mail_verified_at' => now(),
        ];

        // Only add account_id if User model has this attribute
        if (Schema::hasColumn('users', 'account_id')) {
            $userData['account_id'] = $accountId;
        }

        $users['chats'] = User::firstOrCreate(
            ['email' => 'chats@acme.test'],
            $userData
        );

        // Agents
        $agents = [
            ['firstname' => 'María', 'lastname' => 'García', 'email' => 'maria@acme.test'],
            ['firstname' => 'Carlos', 'lastname' => 'Rodríguez', 'email' => 'carlos@acme.test'],
            ['firstname' => 'Ana', 'lastname' => 'Martínez', 'email' => 'ana@acme.test'],
            ['firstname' => 'Pedro', 'lastname' => 'López', 'email' => 'pedro@acme.test'],
            ['firstname' => 'Laura', 'lastname' => 'Fernández', 'email' => 'laura@acme.test'],
        ];

        foreach ($agents as $agentData) {
            $agentUserData = [
                'firstname' => $agentData['firstname'],
                'lastname' => $agentData['lastname'],
                'password' => Hash::make('password'),
                'mail_verified_at' => now(),
            ];

            // Only add account_id if User model has this attribute
            if (Schema::hasColumn('users', 'account_id')) {
                $agentUserData['account_id'] = $accountId;
            }

            $users[$agentData['email']] = User::firstOrCreate(
                ['email' => $agentData['email']],
                $agentUserData
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
            $labels[] = ConversationLabel::firstOrCreate(
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

    private function seedCustomAttributes(): array
    {
        $attributes = [];

        $attributesData = [
            [
                'attribute_display_name' => 'Plan de Suscripción',
                'attribute_key' => 'subscription_plan',
                'attribute_display_type' => CustomerAttribute::DISPLAY_TYPE_LIST,
                'attribute_model' => CustomerAttribute::MODEL_CONTACT,
                'attribute_values' => ['Free', 'Basic', 'Pro', 'Enterprise'],
            ],
            [
                'attribute_display_name' => 'Industria',
                'attribute_key' => 'industry',
                'attribute_display_type' => CustomerAttribute::DISPLAY_TYPE_LIST,
                'attribute_model' => CustomerAttribute::MODEL_CONTACT,
                'attribute_values' => ['Tecnología', 'Retail', 'Salud', 'Educación', 'Finanzas'],
            ],
            [
                'attribute_display_name' => 'Tamaño Empresa',
                'attribute_key' => 'company_size',
                'attribute_display_type' => CustomerAttribute::DISPLAY_TYPE_LIST,
                'attribute_model' => CustomerAttribute::MODEL_CONTACT,
                'attribute_values' => ['1-10', '11-50', '51-200', '201-1000', '1000+'],
            ],
            [
                'attribute_display_name' => 'Fuente de Adquisición',
                'attribute_key' => 'acquisition_source',
                'attribute_display_type' => CustomerAttribute::DISPLAY_TYPE_LIST,
                'attribute_model' => CustomerAttribute::MODEL_CONVERSATION,
                'attribute_values' => ['Google Ads', 'Facebook', 'Referido', 'Búsqueda Orgánica', 'Otro'],
            ],
        ];

        foreach ($attributesData as $attrData) {
            $attributes[] = CustomerAttribute::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'attribute_key' => $attrData['attribute_key'],
                ],
                [
                    'attribute_display_name' => $attrData['attribute_display_name'],
                    'attribute_display_type' => $attrData['attribute_display_type'],
                    'attribute_model' => $attrData['attribute_model'],
                    'attribute_values' => $attrData['attribute_values'],
                ]
            );
        }

        return $attributes;
    }

    private function seedCannedResponses(): array
    {
        $responses = [];

        $responsesData = [
            ['short_code' => 'hola', 'content' => '¡Hola! Gracias por contactarnos. ¿En qué puedo ayudarte hoy?'],
            ['short_code' => 'espera', 'content' => 'Déjame revisar eso por ti. Un momento por favor.'],
            ['short_code' => 'gracias', 'content' => '¡De nada! Si tienes alguna otra pregunta, no dudes en contactarnos. ¡Que tengas un excelente día!'],
            ['short_code' => 'horario', 'content' => 'Nuestro horario de atención es de Lunes a Viernes de 9:00 AM a 6:00 PM (hora local).'],
            ['short_code' => 'refund', 'content' => 'Para procesar tu reembolso, necesito algunos detalles. ¿Podrías proporcionarme tu número de pedido?'],
            ['short_code' => 'tecnico', 'content' => 'Voy a transferirte con nuestro equipo técnico que podrá ayudarte mejor con este problema.'],
            ['short_code' => 'precio', 'content' => 'Puedes ver todos nuestros planes y precios en https://acme.test/pricing. ¿Hay algún plan específico que te interese?'],
        ];

        foreach ($responsesData as $responseData) {
            $responses[] = Canned::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'short_code' => $responseData['short_code'],
                ],
                [
                    'content' => $responseData['content'],
                    'visibility' => 1, // Global
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

        $firstUser = reset($users);
        foreach ($macrosData as $macroData) {
            $macros[] = Macro::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'name' => $macroData['name'],
                ],
                [
                    'actions' => $macroData['actions'],
                    'visibility' => 1, // Global
                    'created_by_id' => $firstUser->id ?? null,
                    'updated_by_id' => $firstUser->id ?? null,
                ]
            );
        }

        return $macros;
    }

    private function seedAutomationRules(array $teams, array $labels): array
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
                'website_token' => Str::random(32),
                'hmac_token' => Str::random(32),
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

    private function seedCustomers(): array
    {
        $customers = [];

        $customersData = [
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

        foreach ($customersData as $customerData) {
            $customers[] = Customer::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'email' => $customerData['email'],
                ],
                [
                    'name' => $customerData['name'],
                    'phone_number' => $customerData['phone_number'] ?? null,
                    'additional_attributes' => $customerData['additional_attributes'] ?? [],
                ]
            );
        }

        return $customers;
    }

    private function seedCustomerSessions(array $customers): array
    {
        $sessions = [];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ];

        $cities = [
            ['city' => 'Ciudad de México', 'country' => 'México', 'lat' => 19.4326, 'lon' => -99.1332, 'ip' => '201.141.34.10'],
            ['city' => 'Guadalajara', 'country' => 'México', 'lat' => 20.6597, 'lon' => -103.3496, 'ip' => '189.203.12.45'],
            ['city' => 'Monterrey', 'country' => 'México', 'lat' => 25.6866, 'lon' => -100.3161, 'ip' => '187.189.23.78'],
            ['city' => 'Madrid', 'country' => 'España', 'lat' => 40.4168, 'lon' => -3.7038, 'ip' => '85.34.56.12'],
            ['city' => 'Buenos Aires', 'country' => 'Argentina', 'lat' => -34.6037, 'lon' => -58.3816, 'ip' => '190.104.23.45'],
        ];

        foreach ($customers as $index => $customer) {
            $location = $cities[$index % count($cities)];
            $sessions[] = CustomerSession::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'customer_id' => $customer->id,
                    'session_token' => Str::random(32),
                ],
                [
                    'ip_address' => $location['ip'],
                    'user_agent' => $userAgents[$index % count($userAgents)],
                    'country' => $location['country'],
                    'city' => $location['city'],
                    'latitude' => $location['lat'],
                    'longitude' => $location['lon'],
                    'session_data' => json_encode([
                        'referrer' => 'https://google.com',
                        'landing_page' => '/',
                    ]),
                    'active' => $index < 3, // First 3 sessions are active
                    'last_activity_at' => now()->subMinutes(rand(5, 120)),
                ]
            );
        }

        return $sessions;
    }

    private function seedBusinessHours(array $inboxes): array
    {
        $hours = [];

        // Business hours for first inbox (Web Widget) - Monday to Friday 9 AM - 6 PM
        foreach ([InboxHour::MONDAY, InboxHour::TUESDAY, InboxHour::WEDNESDAY, InboxHour::THURSDAY, InboxHour::FRIDAY] as $day) {
            $hours[] = InboxHour::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'inbox_id' => $inboxes[0]->id ?? null,
                    'day_of_week' => $day,
                ],
                [
                    'open_time' => '09:00:00',
                    'close_time' => '18:00:00',
                    'is_enabled' => true,
                    'timezone' => 'America/Mexico_City',
                ]
            );
        }

        // Weekend hours (Saturday only) - 10 AM - 2 PM
        $hours[] = InboxHour::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'inbox_id' => $inboxes[0]->id ?? null,
                'day_of_week' => InboxHour::SATURDAY,
            ],
            [
                'open_time' => '10:00:00',
                'close_time' => '14:00:00',
                'is_enabled' => true,
                'timezone' => 'America/Mexico_City',
            ]
        );

        // Sunday closed (disabled)
        $hours[] = InboxHour::firstOrCreate(
            [
                'account_id' => $this->account->id,
                'inbox_id' => $inboxes[0]->id ?? null,
                'day_of_week' => InboxHour::SUNDAY,
            ],
            [
                'open_time' => '00:00:00',
                'close_time' => '00:00:00',
                'is_enabled' => false,
                'timezone' => 'America/Mexico_City',
            ]
        );

        // WhatsApp channel - 24/7 support
        if (isset($inboxes[4])) {
            foreach (range(InboxHour::SUNDAY, InboxHour::SATURDAY) as $day) {
                $hours[] = InboxHour::firstOrCreate(
                    [
                        'account_id' => $this->account->id,
                        'inbox_id' => $inboxes[4]->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'open_time' => '00:00:00',
                        'close_time' => '23:59:59',
                        'is_enabled' => true,
                        'timezone' => 'America/Mexico_City',
                    ]
                );
            }
        }

        return $hours;
    }

    private function seedHelpcenter(array $users): array
    {
        $result = ['categories' => 0, 'articles' => 0, 'tags' => 0];

        // Create tags first
        $tags = [];
        $tagNames = ['Principiante', 'Avanzado', 'API', 'Configuración', 'Integración', 'Troubleshooting', 'Seguridad'];
        foreach ($tagNames as $tagName) {
            $tags[] = HelpcenterTag::firstOrCreate(['name' => $tagName]);
        }
        $result['tags'] = count($tags);

        $author = reset($users); // First user as author

        // Create categories with articles
        $categoriesData = [
            [
                'name' => 'Primeros pasos',
                'description' => 'Guías básicas para comenzar a usar la plataforma',
                'icon' => 'fa-rocket',
                'articles' => [
                    [
                        'title' => 'Cómo crear tu primera cuenta',
                        'body' => '<h2>Introducción</h2><p>Crear una cuenta es muy sencillo...</p>',
                        'description' => 'Aprende a crear tu cuenta en 3 pasos simples',
                        'tags' => ['Principiante', 'Configuración'],
                    ],
                    [
                        'title' => 'Configuración inicial del inbox',
                        'body' => '<h2>Configurando tu inbox</h2><p>Los inboxes son canales de comunicación...</p>',
                        'description' => 'Configura tu primer canal de comunicación',
                        'tags' => ['Principiante', 'Configuración'],
                    ],
                ],
            ],
            [
                'name' => 'Gestión de conversaciones',
                'description' => 'Aprende a gestionar tus conversaciones efectivamente',
                'icon' => 'fa-comments',
                'articles' => [
                    [
                        'title' => 'Asignación de conversaciones',
                        'body' => '<h2>Asignación automática y manual</h2><p>Existen dos formas de asignar conversaciones...</p>',
                        'description' => 'Guía completa sobre asignación de conversaciones',
                        'tags' => ['Avanzado', 'Configuración'],
                    ],
                    [
                        'title' => 'Uso de etiquetas y prioridades',
                        'body' => '<h2>Organiza con etiquetas</h2><p>Las etiquetas te ayudan a categorizar...</p>',
                        'description' => 'Organiza mejor tus conversaciones',
                        'tags' => ['Principiante'],
                    ],
                ],
            ],
            [
                'name' => 'Integraciones y API',
                'description' => 'Conecta con servicios externos y usa nuestra API',
                'icon' => 'fa-plug',
                'articles' => [
                    [
                        'title' => 'Integración con WhatsApp Business',
                        'body' => '<h2>Conecta WhatsApp</h2><p>Para integrar WhatsApp necesitas...</p>',
                        'description' => 'Conecta tu número de WhatsApp Business',
                        'tags' => ['Integración', 'Avanzado'],
                    ],
                    [
                        'title' => 'Uso de la API REST',
                        'body' => '<h2>Documentación de API</h2><p>Nuestra API REST te permite...</p>',
                        'description' => 'Guía completa de nuestra API',
                        'tags' => ['API', 'Avanzado'],
                    ],
                    [
                        'title' => 'Webhooks y notificaciones',
                        'body' => '<h2>Configurar webhooks</h2><p>Los webhooks te notifican en tiempo real...</p>',
                        'description' => 'Recibe notificaciones en tiempo real',
                        'tags' => ['API', 'Integración'],
                    ],
                ],
            ],
            [
                'name' => 'Problemas comunes',
                'description' => 'Soluciones a los problemas más frecuentes',
                'icon' => 'fa-wrench',
                'articles' => [
                    [
                        'title' => 'No puedo enviar mensajes',
                        'body' => '<h2>Solución de problemas de envío</h2><p>Si no puedes enviar mensajes verifica...</p>',
                        'description' => 'Soluciona problemas de envío de mensajes',
                        'tags' => ['Troubleshooting'],
                    ],
                    [
                        'title' => 'Problemas de conexión con canales',
                        'body' => '<h2>Reconectar canales</h2><p>A veces los canales se desconectan...</p>',
                        'description' => 'Resuelve problemas de conexión',
                        'tags' => ['Troubleshooting', 'Integración'],
                    ],
                ],
            ],
            [
                'name' => 'Seguridad y privacidad',
                'description' => 'Protege tu cuenta y datos de clientes',
                'icon' => 'fa-shield-alt',
                'articles' => [
                    [
                        'title' => 'Autenticación de dos factores',
                        'body' => '<h2>Activa 2FA</h2><p>La autenticación de dos factores añade...</p>',
                        'description' => 'Protege tu cuenta con 2FA',
                        'tags' => ['Seguridad', 'Configuración'],
                    ],
                    [
                        'title' => 'Gestión de permisos de equipo',
                        'body' => '<h2>Roles y permisos</h2><p>Controla qué puede hacer cada miembro...</p>',
                        'description' => 'Administra permisos de tu equipo',
                        'tags' => ['Seguridad', 'Avanzado'],
                    ],
                ],
            ],
        ];

        foreach ($categoriesData as $position => $categoryData) {
            $category = HelpcenterCategory::firstOrCreate(
                ['name' => $categoryData['name']],
                [
                    'description' => $categoryData['description'],
                    'icon' => $categoryData['icon'],
                    'position' => $position,
                    'is_section' => false,
                ]
            );
            $result['categories']++;

            // Create articles for this category
            foreach ($categoryData['articles'] as $articlePosition => $articleData) {
                $article = HelpcenterArticle::firstOrCreate(
                    ['slug' => Str::slug($articleData['title'])],
                    [
                        'title' => $articleData['title'],
                        'body' => $articleData['body'],
                        'description' => $articleData['description'],
                        'position' => $articlePosition,
                        'draft' => false,
                        'views' => rand(10, 500),
                        'was_helpful' => rand(5, 100),
                        'author_id' => $author->id,
                    ]
                );
                $result['articles']++;

                // Attach article to category
                if (! $category->articles()->where('article_id', $article->id)->exists()) {
                    $category->articles()->attach($article->id, ['position' => $articlePosition]);
                }

                // Attach tags to article
                $articleTagNames = $articleData['tags'];
                $articleTags = array_filter($tags, fn ($tag) => in_array($tag->name, $articleTagNames));
                $tagIds = array_map(fn ($tag) => $tag->id, $articleTags);
                $article->tags()->syncWithoutDetaching($tagIds);
            }
        }

        return $result;
    }

    private function seedCustomerNotesAndActivities(array $customers, array $users): array
    {
        $result = ['notes' => 0, 'activities' => 0];
        $agent = reset($users); // First user as agent

        // Create notes for some customers
        foreach (array_slice($customers, 0, 5) as $customer) {
            $notesData = [
                'VIP customer - handled personally by CEO',
                'Customer requested callback for product demo',
                'Payment issue resolved - updated credit card',
                'Interested in Enterprise plan upgrade',
                'Referred 3 new customers - eligible for referral bonus',
            ];

            $note = CustomerNote::create([
                'account_id' => $this->account->id,
                'customer_id' => $customer->id,
                'user_id' => $agent->id,
                'content' => $notesData[$result['notes'] % count($notesData)],
            ]);
            $result['notes']++;

            // Create activity for note creation
            CustomerActivity::create([
                'account_id' => $this->account->id,
                'customer_id' => $customer->id,
                'user_id' => $agent->id,
                'activity_type' => 'note_created',
                'description' => 'Added a new note to customer profile',
                'metadata' => [
                    'note_id' => $note->id,
                    'content' => $note->content,
                ],
                'created_at' => now()->subDays(rand(1, 10)),
            ]);
            $result['activities']++;
        }

        // Create additional activities
        $activities = [
            ['type' => 'email_updated', 'desc' => 'Email address was updated', 'data' => ['old' => 'old@example.com', 'new' => 'new@example.com']],
            ['type' => 'phone_updated', 'desc' => 'Phone number was updated', 'data' => ['old' => '+525512345600', 'new' => '+525512345601']],
            ['type' => 'subscription_upgraded', 'desc' => 'Subscription plan was upgraded', 'data' => ['from' => 'Basic', 'to' => 'Pro']],
            ['type' => 'label_added', 'desc' => 'Label was added to customer', 'data' => ['label' => 'VIP']],
        ];

        foreach (array_slice($customers, 0, 4) as $index => $customer) {
            CustomerActivity::create([
                'account_id' => $this->account->id,
                'customer_id' => $customer->id,
                'user_id' => $agent->id,
                'activity_type' => $activities[$index]['type'],
                'description' => $activities[$index]['desc'],
                'metadata' => $activities[$index]['data'],
                'created_at' => now()->subHours(rand(1, 72)),
            ]);
            $result['activities']++;
        }

        return $result;
    }

    private function seedCustomerSegmentsAndLabels(array $customers, array $labels, array $users): array
    {
        $segments = [];
        $firstUser = reset($users); // Get first user for creator

        // Create customer segments
        $segmentsData = [
            [
                'name' => 'Enterprise Customers',
                'description' => 'Customers on Enterprise plan',
                'filter_criteria' => ['subscription_plan' => 'Enterprise'],
                'is_dynamic' => true,
            ],
            [
                'name' => 'Active Users',
                'description' => 'Users active in last 30 days',
                'filter_criteria' => ['last_activity' => '30_days'],
                'is_dynamic' => true,
            ],
            [
                'name' => 'Tech Industry',
                'description' => 'Customers from technology sector',
                'filter_criteria' => ['industry' => 'Tecnología'],
                'is_dynamic' => false,
            ],
        ];

        foreach ($segmentsData as $segmentData) {
            $segment = CustomerSegment::firstOrCreate(
                [
                    'account_id' => $this->account->id,
                    'name' => $segmentData['name'],
                ],
                [
                    'user_id' => $firstUser->id,
                    'description' => $segmentData['description'],
                    'filter_criteria' => $segmentData['filter_criteria'],
                    'is_dynamic' => $segmentData['is_dynamic'],
                    'customer_count' => 0,
                ]
            );
            $segments[] = $segment;

            // Attach some customers to segments using DB directly to avoid timestamp issues
            $customersToAttach = array_slice($customers, 0, rand(2, 4));
            foreach ($customersToAttach as $customer) {
                DB::table('chat_customer_segment')->insertOrIgnore([
                    'customer_segment_id' => $segment->id,
                    'customer_id' => $customer->id,
                    'added_at' => now(),
                ]);
            }
        }

        // Attach labels to some customers
        foreach (array_slice($customers, 0, 5) as $index => $customer) {
            $randomLabels = array_slice($labels, $index, 2);
            foreach ($randomLabels as $label) {
                CustomerLabel::firstOrCreate([
                    'customer_id' => $customer->id,
                    'label_id' => $label->id,
                ]);
            }
        }

        return $segments;
    }

    private function seedPageVisits(array $customers, array $customerSessions): array
    {
        $pageVisits = [];

        $pages = [
            ['url' => '/', 'title' => 'Home', 'time' => rand(10, 60), 'scroll' => rand(50, 100)],
            ['url' => '/pricing', 'title' => 'Pricing', 'time' => rand(30, 120), 'scroll' => rand(70, 100)],
            ['url' => '/features', 'title' => 'Features', 'time' => rand(20, 90), 'scroll' => rand(40, 90)],
            ['url' => '/docs', 'title' => 'Documentation', 'time' => rand(60, 300), 'scroll' => rand(30, 80)],
            ['url' => '/contact', 'title' => 'Contact Us', 'time' => rand(15, 45), 'scroll' => rand(80, 100)],
        ];

        foreach ($customerSessions as $session) {
            // Create 2-5 page visits per session
            $numVisits = rand(2, 5);
            for ($i = 0; $i < $numVisits; $i++) {
                $page = $pages[$i % count($pages)];
                $pageVisits[] = PageVisit::create([
                    'customer_id' => $session->customer_id,
                    'session_id' => $session->id,
                    'page_url' => $page['url'],
                    'page_title' => $page['title'],
                    'referrer' => $i === 0 ? 'https://google.com' : $pages[($i - 1) % count($pages)]['url'],
                    'time_spent_seconds' => $page['time'],
                    'scroll_depth' => $page['scroll'],
                    'visited_at' => $session->created_at->addMinutes($i * 2),
                ]);
            }
        }

        return $pageVisits;
    }

    private function seedConversationsAndMessages(array $inboxes, array $customers, array $users, array $teams, array $labels): array
    {
        $conversations = [];

        // Lookup status and priority IDs
        $statuses = ConversationStatus::pluck('id', 'slug');
        $priorities = ConversationPriority::pluck('id', 'slug');

        // Get some users for assignment (exclude chats, get agents)
        $agentsList = array_values(array_filter($users, fn ($user) => $user->email !== 'chats@acme.test'));

        // Conversation 1: Web Widget - Open - High Priority - With Team
        $customerInbox1 = CustomerInbox::firstOrCreate([
            'customer_id' => $customers[0]->id,
            'inbox_id' => $inboxes[0]->id,
            'source_id' => 'web_'.uniqid(),
        ]);

        $conv1 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'customer_id' => $customers[0]->id,
            'status_id' => $statuses['open'] ?? null,
            'priority_id' => $priorities['high'] ?? null,
            'assignee_id' => $agentsList[0]->id ?? null,
            'team_id' => $teams[0]->id ?? null,
            'cached_label_list' => 'Bug,Urgente',
            'last_activity_at' => now(),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'conversation_id' => $conv1->id,
            'sender_id' => $customers[0]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => '¡Hola! Estoy experimentando un error crítico en la aplicación. Cuando intento cargar mi dashboard, aparece una pantalla en blanco.',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'conversation_id' => $conv1->id,
            'sender_id' => $agentsList[0]->id ?? $users['chats']->id,
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
            'sender_id' => $customers[0]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Estoy usando Chrome, versión más reciente.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv1;

        // Conversation 2: Email - Pending - Medium Priority
        $customerInbox2 = CustomerInbox::firstOrCreate([
            'customer_id' => $customers[1]->id,
            'inbox_id' => $inboxes[1]->id,
            'source_id' => $customers[1]->email,
        ]);

        $conv2 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'customer_id' => $customers[1]->id,
            'status_id' => $statuses['pending'] ?? null,
            'priority_id' => $priorities['medium'] ?? null,
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
            'sender_id' => $customers[1]->id,
            'sender_type' => Customer::class,
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
            'sender_id' => $agentsList[1]->id ?? $users['chats']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Hola María, gracias por tu interés. Sí, ofrecemos un 20% de descuento en planes anuales Enterprise. Déjame prepararte una cotización personalizada.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv2;

        // Conversation 3: Facebook - Resolved - Low Priority
        $customerInbox3 = CustomerInbox::firstOrCreate([
            'customer_id' => $customers[2]->id,
            'inbox_id' => $inboxes[2]->id,
            'source_id' => 'fb_'.uniqid(),
        ]);

        $conv3 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[2]->id,
            'customer_id' => $customers[2]->id,
            'status_id' => $statuses['resolved'] ?? null,
            'priority_id' => $priorities['low'] ?? null,
            'assignee_id' => $agentsList[2]->id ?? null,
            'cached_label_list' => 'Pregunta',
            'last_activity_at' => now()->subDays(1),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[2]->id,
            'conversation_id' => $conv3->id,
            'sender_id' => $customers[2]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => '¿Cuál es el horario de atención?',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[2]->id,
            'conversation_id' => $conv3->id,
            'sender_id' => $agentsList[2]->id ?? $users['chats']->id,
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
            'sender_id' => $customers[2]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'No, eso es todo. ¡Gracias!',
            'status' => 'sent',
        ]);

        $conversations[] = $conv3;

        // Conversation 4: WhatsApp - Open - Urgent - VIP
        $customerInbox4 = CustomerInbox::firstOrCreate([
            'customer_id' => $customers[6]->id,
            'inbox_id' => $inboxes[4]->id,
            'source_id' => 'wa_'.uniqid(),
        ]);

        $conv4 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[4]->id,
            'customer_id' => $customers[6]->id,
            'status_id' => $statuses['open'] ?? null,
            'priority_id' => $priorities['urgent'] ?? null,
            'assignee_id' => $users['chats']->id,
            'team_id' => $teams[1]->id ?? null,
            'cached_label_list' => 'VIP,Urgente',
            'last_activity_at' => now()->subMinutes(5),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[4]->id,
            'conversation_id' => $conv4->id,
            'sender_id' => $customers[6]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Urgente: Necesito una renovación inmediata de nuestra licencia Enterprise. Expira en 2 días.',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[4]->id,
            'conversation_id' => $conv4->id,
            'sender_id' => $users['chats']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Fernando, entiendo la urgencia. Estoy procesando tu renovación ahora mismo. Te enviaré el link de pago en los próximos 10 minutos.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv4;

        // Conversation 5: Instagram - Pending - High Priority
        $customerInbox5 = CustomerInbox::firstOrCreate([
            'customer_id' => $customers[3]->id,
            'inbox_id' => $inboxes[3]->id,
            'source_id' => 'ig_'.uniqid(),
        ]);

        $conv5 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[3]->id,
            'customer_id' => $customers[3]->id,
            'status_id' => $statuses['pending'] ?? null,
            'priority_id' => $priorities['high'] ?? null,
            'assignee_id' => $agentsList[3]->id ?? null,
            'cached_label_list' => 'Sugerencia,Seguimiento',
            'last_activity_at' => now()->subHours(6),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[3]->id,
            'conversation_id' => $conv5->id,
            'sender_id' => $customers[3]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Me encantaría que agregaran integración con Zapier. ¿Está en el roadmap?',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[3]->id,
            'conversation_id' => $conv5->id,
            'sender_id' => $agentsList[3]->id ?? $users['chats']->id,
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
            'sender_id' => $agentsList[3]->id ?? $users['chats']->id,
            'sender_type' => User::class,
            'message_type' => 'outgoing',
            'content_type' => 'text',
            'content' => 'Nota: Cliente educación, buen candidato para beta testing de Zapier.',
            'status' => 'sent',
            'private' => true,
        ]);

        $conversations[] = $conv5;

        // Conversation 6: Web Widget - Open - No Assignment (Unattended)
        $customerInbox6 = CustomerInbox::firstOrCreate([
            'customer_id' => $customers[4]->id,
            'inbox_id' => $inboxes[0]->id,
            'source_id' => 'web_'.uniqid(),
        ]);

        $conv6 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'customer_id' => $customers[4]->id,
            'status_id' => $statuses['open'] ?? null,
            'priority_id' => $priorities['medium'] ?? null,
            'cached_label_list' => 'Pregunta',
            'last_activity_at' => now()->subMinutes(30),
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[0]->id,
            'conversation_id' => $conv6->id,
            'sender_id' => $customers[4]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => '¿Tienen API disponible para integraciones personalizadas?',
            'status' => 'sent',
        ]);

        $conversations[] = $conv6;

        // Conversation 7: Email - Resolved - Cancellation
        $customerInbox7 = CustomerInbox::firstOrCreate([
            'customer_id' => $customers[5]->id,
            'inbox_id' => $inboxes[1]->id,
            'source_id' => $customers[5]->email,
        ]);

        $conv7 = Conversation::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'customer_id' => $customers[5]->id,
            'status_id' => $statuses['resolved'] ?? null,
            'priority_id' => $priorities['low'] ?? null,
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
            'sender_id' => $customers[5]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'Hola, quisiera cancelar mi suscripción Basic. Ya no lo estoy usando.',
            'status' => 'sent',
        ]);

        ConversationMessage::create([
            'account_id' => $this->account->id,
            'inbox_id' => $inboxes[1]->id,
            'conversation_id' => $conv7->id,
            'sender_id' => $agentsList[0]->id ?? $users['chats']->id,
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
            'sender_id' => $customers[5]->id,
            'sender_type' => Customer::class,
            'message_type' => 'incoming',
            'content_type' => 'text',
            'content' => 'No, el servicio estuvo bien. Simplemente cambié de proyecto. Gracias.',
            'status' => 'sent',
        ]);

        $conversations[] = $conv7;

        return $conversations;
    }

    private function seedConversationSessions(array $conversations, array $customers): array
    {
        $sessions = [];

        foreach ($conversations as $conversation) {
            // Create session for each conversation
            $session = ConversationSession::create([
                'account_id' => $this->account->id,
                'customer_id' => $conversation->customer_id,
                'token' => hash('sha256', uniqid('conv_'.$conversation->id.'_', true)),
                'session_id' => 'sess_'.uniqid(),
                'session_data' => [
                    'browser' => ['type' => 'Chrome', 'version' => '120.0'],
                    'device' => 'Desktop',
                    'platform' => 'Windows',
                    'country' => 'ES',
                    'ip_address' => fake()->ipv4(),
                ],
                'last_activity_at' => $conversation->last_activity_at ?? now(),
                'active' => $conversation->status !== 'resolved',
            ]);

            $sessions[] = $session;
        }

        return $sessions;
    }
}
