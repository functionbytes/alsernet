<?php

namespace Modules\HelpdeskSocial\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialRule;
use Modules\HelpdeskSocial\Models\SocialTemplate;

class HelpdeskSocialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if ($user === null) {
            $this->command->warn('No users found. Skipping HelpdeskSocial demo seeder.');

            return;
        }

        $this->seedAccounts($user);
        $this->seedTemplates($user);
        $this->seedRules($user);
        $this->seedComments();

        $this->command->info('HelpdeskSocial demo data seeded successfully.');
    }

    private function seedAccounts(User $user): void
    {
        $accounts = [
            [
                'name' => 'Alsernet Oficial',
                'platform' => 'facebook',
                'account_type' => 'page',
                'external_id' => '1023456789012',
                'username' => 'alsernet',
                'profile_url' => 'https://facebook.com/alsernet',
                'is_active' => true,
                'comments_enabled' => true,
                'messages_enabled' => true,
                'auto_reply_enabled' => true,
            ],
            [
                'name' => 'Alsernet Instagram',
                'platform' => 'instagram',
                'account_type' => 'business',
                'external_id' => '17841401234567890',
                'username' => 'alsernet',
                'profile_url' => 'https://instagram.com/alsernet',
                'is_active' => true,
                'comments_enabled' => true,
                'messages_enabled' => true,
                'auto_reply_enabled' => false,
            ],
            [
                'name' => 'Soporte WhatsApp',
                'platform' => 'whatsapp',
                'account_type' => 'business',
                'external_id' => '5215512345678',
                'username' => 'alsernet_soporte',
                'profile_url' => 'https://wa.me/5215512345678',
                'is_active' => true,
                'comments_enabled' => false,
                'messages_enabled' => true,
                'auto_reply_enabled' => true,
            ],
            [
                'name' => 'Alsernet TikTok',
                'platform' => 'tiktok',
                'account_type' => 'business',
                'external_id' => 'tiktok_9876543210',
                'username' => 'alsernet',
                'profile_url' => 'https://tiktok.com/@alsernet',
                'is_active' => false,
                'comments_enabled' => true,
                'messages_enabled' => false,
                'auto_reply_enabled' => false,
            ],
        ];

        foreach ($accounts as $data) {
            SocialAccount::firstOrCreate(
                ['external_id' => $data['external_id'], 'platform' => $data['platform']],
                array_merge($data, ['connected_by_user_id' => $user->id])
            );
        }
    }

    private function seedTemplates(User $user): void
    {
        $templates = [
            [
                'name' => 'Saludo inicial',
                'description' => 'Respuesta de bienvenida genérica.',
                'platform' => null,
                'body' => 'Hola {{author_name}}, gracias por contactarnos. En breve un agente te atenderá.',
                'variables' => ['author_name'],
                'quick_replies' => ['Ver catálogo', 'Hablar con agente'],
                'category' => 'greeting',
                'is_active' => true,
                'is_default' => true,
                'usage_count' => 124,
            ],
            [
                'name' => 'Seguimiento de pedido',
                'description' => 'Solicitar número de pedido para dar seguimiento.',
                'platform' => 'facebook',
                'body' => 'Hola {{author_name}}, con gusto te ayudo. ¿Podrías compartirme tu número de pedido?',
                'variables' => ['author_name'],
                'quick_replies' => ['No tengo pedido', 'Ver políticas'],
                'category' => 'support',
                'is_active' => true,
                'is_default' => false,
                'usage_count' => 67,
            ],
            [
                'name' => 'Escalamiento a técnico',
                'description' => 'Notificar que el caso fue escalado.',
                'platform' => 'instagram',
                'body' => 'Hola {{author_name}}, tu caso fue escalado a nuestro equipo técnico. Te contactaremos en menos de 2 horas.',
                'variables' => ['author_name'],
                'quick_replies' => [],
                'category' => 'support',
                'is_active' => true,
                'is_default' => false,
                'usage_count' => 34,
            ],
            [
                'name' => 'Promoción activa',
                'description' => 'Informar sobre promociones vigentes.',
                'platform' => 'whatsapp',
                'body' => '¡Hola {{author_name}}! Tenemos una promoción especial para ti. ¿Te gustaría conocer más detalles?',
                'variables' => ['author_name'],
                'quick_replies' => ['Sí, cuéntame', 'No gracias'],
                'category' => 'sales',
                'is_active' => true,
                'is_default' => false,
                'usage_count' => 89,
            ],
            [
                'name' => 'Agradecimiento por feedback',
                'description' => 'Responder a comentarios de satisfacción.',
                'platform' => null,
                'body' => 'Gracias por tus comentarios, {{author_name}}. Nos alegra saber que tuviste una buena experiencia.',
                'variables' => ['author_name'],
                'quick_replies' => [],
                'category' => 'feedback',
                'is_active' => false,
                'is_default' => false,
                'usage_count' => 12,
            ],
        ];

        foreach ($templates as $data) {
            SocialTemplate::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['created_by_user_id' => $user->id])
            );
        }
    }

    private function seedRules(User $user): void
    {
        $rules = [
            [
                'name' => 'Escalar quejas de alto urgencia',
                'description' => 'Cuando el intent es queja y la urgencia es alta, escalar inmediatamente.',
                'platform' => 'facebook',
                'conditions' => [
                    ['field' => 'intent', 'operator' => 'equals', 'value' => 'complaint'],
                    ['field' => 'urgency', 'operator' => 'equals', 'value' => 'high'],
                ],
                'actions' => [
                    ['type' => 'escalate', 'params' => []],
                    ['type' => 'add_tag', 'params' => ['tag' => 'urgente']],
                ],
                'priority' => 1,
                'is_active' => true,
                'stop_processing' => true,
                'trigger_count' => 45,
            ],
            [
                'name' => 'Auto-responder preguntas frecuentes',
                'description' => 'Responder automáticamente cuando se detecta una pregunta simple.',
                'platform' => null,
                'conditions' => [
                    ['field' => 'intent', 'operator' => 'equals', 'value' => 'question'],
                ],
                'actions' => [
                    ['type' => 'reply', 'params' => ['template_name' => 'Saludo inicial']],
                ],
                'priority' => 10,
                'is_active' => true,
                'stop_processing' => false,
                'trigger_count' => 312,
            ],
            [
                'name' => 'Marcar spam en comentarios repetidos',
                'description' => 'Detectar posible spam basado en keywords.',
                'platform' => 'instagram',
                'conditions' => [
                    ['field' => 'body', 'operator' => 'contains', 'value' => 'gratis'],
                    ['field' => 'body', 'operator' => 'contains', 'value' => 'click aqui'],
                ],
                'actions' => [
                    ['type' => 'mark_spam', 'params' => []],
                ],
                'priority' => 5,
                'is_active' => true,
                'stop_processing' => true,
                'trigger_count' => 28,
            ],
            [
                'name' => 'Asignar ventas a equipo comercial',
                'description' => 'Cuando el intent es de ventas, asignar al usuario comercial.',
                'platform' => 'whatsapp',
                'conditions' => [
                    ['field' => 'intent', 'operator' => 'equals', 'value' => 'sales'],
                ],
                'actions' => [
                    ['type' => 'assign', 'params' => ['user_id' => $user->id]],
                    ['type' => 'add_tag', 'params' => ['tag' => 'ventas']],
                ],
                'priority' => 20,
                'is_active' => false,
                'stop_processing' => false,
                'trigger_count' => 0,
            ],
        ];

        foreach ($rules as $data) {
            SocialRule::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'valid_from' => now()->subDays(30)->toDateString(),
                    'valid_until' => null,
                    'created_by_user_id' => $user->id,
                ])
            );
        }
    }

    private function seedComments(): void
    {
        $account = SocialAccount::where('platform', 'facebook')->first();

        if ($account === null) {
            return;
        }

        $comments = [
            [
                'platform' => 'facebook',
                'external_comment_id' => 'fb_comment_001',
                'external_post_id' => 'fb_post_001',
                'external_user_id' => 'fb_user_001',
                'author_name' => 'Juan Pérez',
                'author_username' => 'juanperez',
                'body' => 'Hola, ¿tienen envíos a domicilio?',
                'intent' => 'question',
                'intent_confidence' => 0.95,
                'urgency' => 'low',
                'status' => 'pending',
                'is_spam' => false,
                'posted_at' => now()->subHours(2),
            ],
            [
                'platform' => 'facebook',
                'external_comment_id' => 'fb_comment_002',
                'external_post_id' => 'fb_post_001',
                'external_user_id' => 'fb_user_002',
                'author_name' => 'María García',
                'author_username' => 'mariagarcia',
                'body' => 'Mi pedido llegó dañado, necesito una solución urgente.',
                'intent' => 'complaint',
                'intent_confidence' => 0.92,
                'urgency' => 'high',
                'status' => 'escalated',
                'is_spam' => false,
                'posted_at' => now()->subHours(5),
            ],
            [
                'platform' => 'facebook',
                'external_comment_id' => 'fb_comment_003',
                'external_post_id' => 'fb_post_002',
                'external_user_id' => 'fb_user_003',
                'author_name' => 'Carlos López',
                'author_username' => 'carloslopez',
                'body' => 'Gracias por la atención, quedé muy satisfecho.',
                'intent' => 'feedback',
                'intent_confidence' => 0.88,
                'urgency' => 'low',
                'status' => 'replied',
                'reply_type' => 'manual',
                'reply_body' => 'Nos alegra mucho, Carlos. ¡Gracias por tu preferencia!',
                'replied_at' => now()->subHour(),
                'is_spam' => false,
                'posted_at' => now()->subHours(8),
            ],
            [
                'platform' => 'facebook',
                'external_comment_id' => 'fb_comment_004',
                'external_post_id' => 'fb_post_003',
                'external_user_id' => 'fb_user_004',
                'author_name' => 'Spam Bot',
                'author_username' => 'free_stuff_bot',
                'body' => '¡Consigue todo gratis! click aqui www.example.com',
                'intent' => null,
                'intent_confidence' => null,
                'urgency' => 'low',
                'status' => 'spam',
                'is_spam' => true,
                'posted_at' => now()->subMinutes(30),
            ],
            [
                'platform' => 'instagram',
                'external_comment_id' => 'ig_comment_001',
                'external_post_id' => 'ig_post_001',
                'external_user_id' => 'ig_user_001',
                'author_name' => 'Ana Torres',
                'author_username' => 'anatorres',
                'body' => '¿Cuándo llegan las nuevas colecciones?',
                'intent' => 'sales',
                'intent_confidence' => 0.85,
                'urgency' => 'medium',
                'status' => 'pending',
                'is_spam' => false,
                'posted_at' => now()->subHours(1),
            ],
        ];

        foreach ($comments as $data) {
            SocialComment::firstOrCreate(
                ['external_comment_id' => $data['external_comment_id']],
                array_merge($data, ['social_account_id' => $account->id])
            );
        }
    }
}
