<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookSettingsRepository;
use Tests\TestCase;

/**
 * Un adapter por proveedor, cada uno con su propio mecanismo de
 * verificación real (token compartido para Mailrelay/Postmark, HMAC firmado
 * para Mailgun, RSA firmado por SNS para SES) — se prueba cada uno contra su
 * mecanismo real, no contra un doble genérico, porque la verificación ES la
 * parte con más riesgo de este conector (un fallo aquí deja procesar
 * payloads no auténticos, o al revés, rechaza los legítimos).
 */
class EmailProviderWebhookControllerTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: ProviderWebhookSettingsRepository escribe vía
    // Modules\Core\Models\Setting (conexión default = mysql en este entorno)
    // — mismo gotcha documentado en BounceMailboxesControllerTest.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private function configureProvider(string $provider, string $secret, array $overrides = []): void
    {
        app(ProviderWebhookSettingsRepository::class)->save(array_merge([
            'provider' => $provider,
            'secret' => $secret,
            'process_bounces' => true,
            'process_complaints' => true,
        ], $overrides));
    }

    public function test_unknown_provider_returns_404(): void
    {
        $this->configureProvider('mailrelay', 'shared-secret');

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'not-a-real-provider']))
            ->assertNotFound();
    }

    public function test_request_for_a_provider_that_is_not_the_active_one_returns_404(): void
    {
        $this->configureProvider('mailrelay', 'shared-secret');

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'postmark']), [], [
            'X-Postmark-Webhook-Token' => 'whatever',
        ])->assertNotFound();
    }

    // --- Mailrelay: token compartido en cabecera ---

    public function test_mailrelay_rejects_an_invalid_token(): void
    {
        $this->configureProvider('mailrelay', 'shared-secret');

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailrelay']), [
            'type' => 'hard_bounce', 'email' => 'x@example.test',
        ], ['X-Mailrelay-Token' => 'wrong-token'])
            ->assertStatus(401);
    }

    public function test_mailrelay_marks_a_hard_bounce_by_message_id(): void
    {
        $this->configureProvider('mailrelay', 'shared-secret');
        $log = EmailLog::factory()->create(['message_id' => 'mailrelay-msg-1@webadmin.test', 'status' => EmailStatus::Sent]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailrelay']), [
            'type' => 'hard_bounce',
            'message_id' => 'mailrelay-msg-1@webadmin.test',
            'email' => 'x@example.test',
            'reason' => '550 mailbox unavailable',
        ], ['X-Mailrelay-Token' => 'shared-secret'])
            ->assertOk()
            ->assertJson(['processed' => 1, 'skipped' => 0]);

        $log->refresh();
        $this->assertSame(EmailStatus::Bounced, $log->status);
        $this->assertSame('hard', $log->bounceType());
    }

    public function test_mailrelay_processing_the_same_event_twice_stays_idempotent(): void
    {
        $this->configureProvider('mailrelay', 'shared-secret');
        $log = EmailLog::factory()->create(['message_id' => 'mailrelay-msg-2@webadmin.test', 'status' => EmailStatus::Sent]);

        $payload = [
            'type' => 'hard_bounce',
            'message_id' => 'mailrelay-msg-2@webadmin.test',
            'email' => 'x@example.test',
        ];
        $headers = ['X-Mailrelay-Token' => 'shared-secret'];

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailrelay']), $payload, $headers)->assertOk();
        $firstBouncedAt = $log->fresh()->bounced_at;

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailrelay']), $payload, $headers)->assertOk();

        $log->refresh();
        $this->assertSame(EmailStatus::Bounced, $log->status);
        $this->assertEquals($firstBouncedAt, $log->bounced_at);
    }

    // --- Postmark: token compartido en cabecera propia ---

    public function test_postmark_rejects_a_missing_token(): void
    {
        $this->configureProvider('postmark', 'pm-secret');

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'postmark']), [
            'RecordType' => 'Bounce', 'Type' => 'HardBounce', 'MessageID' => 'x', 'Email' => 'x@example.test',
        ])->assertStatus(401);
    }

    public function test_postmark_marks_a_spam_complaint_by_message_id(): void
    {
        $this->configureProvider('postmark', 'pm-secret');
        $log = EmailLog::factory()->create(['message_id' => 'postmark-abc-123', 'status' => EmailStatus::Sent]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'postmark']), [
            'RecordType' => 'SpamComplaint',
            'MessageID' => 'postmark-abc-123',
            'Email' => 'x@example.test',
        ], ['X-Postmark-Webhook-Token' => 'pm-secret'])
            ->assertOk()
            ->assertJson(['processed' => 1, 'skipped' => 0]);

        $this->assertSame(EmailStatus::Complained, $log->fresh()->status);
    }

    // --- Mailgun: HMAC-SHA256 sobre timestamp+token ---

    private function mailgunSignature(string $secret, int $timestamp, string $token): array
    {
        return [
            'timestamp' => (string) $timestamp,
            'token' => $token,
            'signature' => hash_hmac('sha256', $timestamp.$token, $secret),
        ];
    }

    public function test_mailgun_rejects_an_invalid_hmac_signature(): void
    {
        $this->configureProvider('mailgun', 'mg-signing-key');

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailgun']), [
            'signature' => ['timestamp' => (string) time(), 'token' => 'tok', 'signature' => 'not-a-real-hmac'],
            'event-data' => ['event' => 'failed', 'severity' => 'permanent', 'recipient' => 'x@example.test'],
        ])->assertStatus(401);
    }

    public function test_mailgun_marks_a_hard_bounce_from_permanent_severity(): void
    {
        $this->configureProvider('mailgun', 'mg-signing-key');
        $log = EmailLog::factory()->create(['message_id' => 'mailgun-msg-1@webadmin.test', 'status' => EmailStatus::Sent]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailgun']), [
            'signature' => $this->mailgunSignature('mg-signing-key', time(), 'tok-1'),
            'event-data' => [
                'event' => 'failed',
                'severity' => 'permanent',
                'recipient' => 'x@example.test',
                'message' => ['headers' => ['message-id' => 'mailgun-msg-1@webadmin.test']],
            ],
        ])->assertOk()->assertJson(['processed' => 1, 'skipped' => 0]);

        $log->refresh();
        $this->assertSame(EmailStatus::Bounced, $log->status);
        $this->assertSame('hard', $log->bounceType());
    }

    public function test_mailgun_soft_bounce_from_temporary_severity_is_not_hard(): void
    {
        $this->configureProvider('mailgun', 'mg-signing-key');
        $log = EmailLog::factory()->create(['message_id' => 'mailgun-msg-2@webadmin.test', 'status' => EmailStatus::Sent]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailgun']), [
            'signature' => $this->mailgunSignature('mg-signing-key', time(), 'tok-2'),
            'event-data' => [
                'event' => 'failed',
                'severity' => 'temporary',
                'recipient' => 'x@example.test',
                'message' => ['headers' => ['message-id' => 'mailgun-msg-2@webadmin.test']],
            ],
        ])->assertOk();

        $log->refresh();
        $this->assertSame(EmailStatus::Bounced, $log->status);
        $this->assertSame('soft', $log->bounceType());
    }

    public function test_mailgun_rejects_a_stale_timestamp_outside_the_replay_window(): void
    {
        $this->configureProvider('mailgun', 'mg-signing-key');

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailgun']), [
            'signature' => $this->mailgunSignature('mg-signing-key', time() - 3600, 'tok-3'),
            'event-data' => ['event' => 'failed', 'severity' => 'permanent', 'recipient' => 'x@example.test'],
        ])->assertStatus(401);
    }

    // --- SES/SNS: firma RSA verificada contra un certificado descargado ---

    /**
     * @return array{0: \OpenSSLAsymmetricKey, 1: string} [clave privada, PEM del certificado]
     */
    private function generateSigningKeyPair(): array
    {
        $privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'helpdeskemaillog-test'], $privateKey);
        $cert = openssl_csr_sign($csr, null, $privateKey, 365);
        openssl_x509_export($cert, $certPem);

        return [$privateKey, $certPem];
    }

    private function signSnsPayload(array $payload, $privateKey): array
    {
        $fields = $payload['Type'] === 'Notification'
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $canonical = '';
        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                $canonical .= $field."\n".$payload[$field]."\n";
            }
        }

        openssl_sign($canonical, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        $payload['Signature'] = base64_encode($signature);
        $payload['SignatureVersion'] = '1';

        return $payload;
    }

    public function test_ses_rejects_a_signing_cert_url_on_a_non_amazon_host(): void
    {
        $this->configureProvider('ses', '');
        [$privateKey, $certPem] = $this->generateSigningKeyPair();

        $payload = $this->signSnsPayload([
            'Type' => 'Notification',
            'MessageId' => 'sns-msg-1',
            'TopicArn' => 'arn:aws:sns:us-east-1:123:topic',
            'Timestamp' => now()->toIso8601String(),
            'SigningCertURL' => 'https://evil.test/cert.pem',
            'Message' => json_encode(['eventType' => 'Bounce']),
        ], $privateKey);

        Http::fake(['evil.test/*' => Http::response($certPem, 200)]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'ses']), $payload)
            ->assertStatus(401);
    }

    public function test_ses_confirms_a_subscription_by_fetching_the_subscribe_url(): void
    {
        $this->configureProvider('ses', '');
        [$privateKey, $certPem] = $this->generateSigningKeyPair();

        $payload = $this->signSnsPayload([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => 'sns-msg-2',
            'Token' => 'tok',
            'TopicArn' => 'arn:aws:sns:us-east-1:123:topic',
            'Timestamp' => now()->toIso8601String(),
            'SubscribeURL' => 'https://sns.us-east-1.amazonaws.com/confirm?Token=tok',
            'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
            'Message' => 'You have chosen to subscribe to the topic.',
        ], $privateKey);

        Http::fake([
            'sns.us-east-1.amazonaws.com/cert.pem' => Http::response($certPem, 200),
            'sns.us-east-1.amazonaws.com/confirm*' => Http::response('confirmed', 200),
        ]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'ses']), $payload)
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'confirm'));
    }

    public function test_ses_marks_a_permanent_bounce_from_a_verified_notification(): void
    {
        $this->configureProvider('ses', '');
        [$privateKey, $certPem] = $this->generateSigningKeyPair();
        $log = EmailLog::factory()->create(['message_id' => 'ses-msg-1@webadmin.test', 'status' => EmailStatus::Sent]);

        $message = json_encode([
            'eventType' => 'Bounce',
            'mail' => ['messageId' => 'ses-internal-id', 'commonHeaders' => ['messageId' => 'ses-msg-1@webadmin.test']],
            'bounce' => [
                'bounceType' => 'Permanent',
                'bouncedRecipients' => [['emailAddress' => 'x@example.test', 'diagnosticCode' => '550 5.1.1']],
            ],
        ]);

        $payload = $this->signSnsPayload([
            'Type' => 'Notification',
            'MessageId' => 'sns-msg-3',
            'TopicArn' => 'arn:aws:sns:us-east-1:123:topic',
            'Timestamp' => now()->toIso8601String(),
            'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
            'Message' => $message,
        ], $privateKey);

        Http::fake(['sns.us-east-1.amazonaws.com/cert.pem' => Http::response($certPem, 200)]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'ses']), $payload)
            ->assertOk()
            ->assertJson(['processed' => 1, 'skipped' => 0]);

        $log->refresh();
        $this->assertSame(EmailStatus::Bounced, $log->status);
        $this->assertSame('hard', $log->bounceType());
    }

    public function test_ses_rejects_a_tampered_signature(): void
    {
        $this->configureProvider('ses', '');
        [$privateKey, $certPem] = $this->generateSigningKeyPair();

        $payload = $this->signSnsPayload([
            'Type' => 'Notification',
            'MessageId' => 'sns-msg-4',
            'TopicArn' => 'arn:aws:sns:us-east-1:123:topic',
            'Timestamp' => now()->toIso8601String(),
            'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
            'Message' => json_encode(['eventType' => 'Bounce']),
        ], $privateKey);

        // Tras firmar, se altera el cuerpo del mensaje — la firma ya no
        // corresponde al contenido, exactamente el escenario que debe
        // rechazar una verificación real (no un doble que siempre dice sí).
        $payload['Message'] = json_encode(['eventType' => 'Bounce', 'tampered' => true]);

        Http::fake(['sns.us-east-1.amazonaws.com/cert.pem' => Http::response($certPem, 200)]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'ses']), $payload)
            ->assertStatus(401);
    }

    public function test_disabled_event_types_are_skipped_even_when_correlation_would_succeed(): void
    {
        $this->configureProvider('mailrelay', 'shared-secret', ['process_complaints' => false]);
        $log = EmailLog::factory()->create(['message_id' => 'mailrelay-msg-3@webadmin.test', 'status' => EmailStatus::Sent]);

        $this->postJson(route('helpdeskemaillog.webhooks.receive', ['provider' => 'mailrelay']), [
            'type' => 'complaint',
            'message_id' => 'mailrelay-msg-3@webadmin.test',
            'email' => 'x@example.test',
        ], ['X-Mailrelay-Token' => 'shared-secret'])
            ->assertOk()
            ->assertJson(['processed' => 0, 'skipped' => 1]);

        $this->assertSame(EmailStatus::Sent, $log->fresh()->status);
    }
}
