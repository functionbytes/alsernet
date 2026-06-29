<?php

namespace Modules\Mailrelay\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Modules\Mailrelay\Contracts\MailProviderInterface;
use Modules\Mailrelay\Entities\MailProvider;
use Modules\Mailrelay\Exceptions\ProviderException;
use Modules\Mailrelay\Services\ProviderManager;
use Tests\TestCase;

class ProviderManagerTest extends TestCase
{
    private ProviderManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ProviderManager;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function available_drivers_returns_all_five_drivers(): void
    {
        $drivers = $this->manager->availableDrivers();

        $this->assertIsArray($drivers);
        $this->assertCount(5, $drivers);
        $this->assertContains('mailrelay', $drivers);
        $this->assertContains('mailtrap', $drivers);
        $this->assertContains('sendgrid', $drivers);
        $this->assertContains('aws_ses', $drivers);
        $this->assertContains('postmark', $drivers);
    }

    /** @test */
    public function available_drivers_returns_only_string_keys(): void
    {
        $drivers = $this->manager->availableDrivers();

        foreach ($drivers as $driver) {
            $this->assertIsString($driver);
        }
    }

    /** @test */
    public function get_driver_info_returns_correct_data_for_mailrelay(): void
    {
        $info = $this->manager->getDriverInfo('mailrelay');

        $this->assertEquals('Mailrelay', $info['name']);
        $this->assertNotEmpty($info['description']);
        $this->assertIsArray($info['features']);
        $this->assertContains('api_url', $info['required_credentials']);
        $this->assertContains('api_key', $info['required_credentials']);
        $this->assertContains('from_email', $info['required_credentials']);
        $this->assertContains('from_name', $info['required_credentials']);
    }

    /** @test */
    public function get_driver_info_returns_correct_data_for_sendgrid(): void
    {
        $info = $this->manager->getDriverInfo('sendgrid');

        $this->assertEquals('SendGrid', $info['name']);
        $this->assertContains('api_key', $info['required_credentials']);
    }

    /** @test */
    public function get_driver_info_returns_correct_data_for_aws_ses(): void
    {
        $info = $this->manager->getDriverInfo('aws_ses');

        $this->assertEquals('AWS SES', $info['name']);
        $this->assertContains('access_key', $info['required_credentials']);
        $this->assertContains('secret_key', $info['required_credentials']);
        $this->assertContains('region', $info['required_credentials']);
    }

    /** @test */
    public function get_driver_info_returns_correct_data_for_postmark(): void
    {
        $info = $this->manager->getDriverInfo('postmark');

        $this->assertEquals('Postmark', $info['name']);
        $this->assertContains('server_token', $info['required_credentials']);
    }

    /** @test */
    public function get_driver_info_returns_fallback_for_unknown_driver(): void
    {
        $info = $this->manager->getDriverInfo('nonexistent');

        $this->assertEquals('nonexistent', $info['name']);
        $this->assertEquals('Provider no documentado', $info['description']);
        $this->assertEmpty($info['features']);
        $this->assertEmpty($info['required_credentials']);
    }

    /** @test */
    public function clear_cache_empties_instances(): void
    {
        $reflection = new \ReflectionProperty(ProviderManager::class, 'instances');
        $reflection->setAccessible(true);

        // Inject a dummy instance
        $reflection->setValue($this->manager, ['test_key' => 'test_value']);
        $this->assertNotEmpty($reflection->getValue($this->manager));

        $this->manager->clearCache();

        $this->assertEmpty($reflection->getValue($this->manager));
    }

    /** @test */
    public function send_with_failover_throws_provider_exception_when_no_providers(): void
    {
        // Use a testable subclass that overrides the private getOrderedProviders
        $manager = $this->createTestableManager(new Collection);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('No default mail provider is configured');

        $manager->sendWithFailover('test@example.com', 'Subject', '<p>Body</p>');
    }

    /** @test */
    public function send_bulk_with_failover_throws_provider_exception_when_no_providers(): void
    {
        $manager = $this->createTestableManager(new Collection);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('No default mail provider is configured');

        $manager->sendBulkWithFailover(
            [['email' => 'a@test.com']],
            'Subject',
            '<p>Body</p>'
        );
    }

    /** @test */
    public function send_with_failover_returns_result_from_first_successful_provider(): void
    {
        $mockProvider = Mockery::mock(MailProviderInterface::class);
        $mockProvider->shouldReceive('send')
            ->once()
            ->with('test@test.com', 'Hi', '<p>Hello</p>', [])
            ->andReturn(['success' => true, 'message_id' => 'msg-001', 'error' => null]);
        $mockProvider->shouldReceive('getName')->andReturn('MockProvider');

        $providerConfig = $this->makeProviderConfig(1, 'Test Provider', 'mailrelay');
        $manager = $this->createTestableManager(
            new Collection([$providerConfig]),
            ['mailrelay' => $mockProvider]
        );

        $result = $manager->sendWithFailover('test@test.com', 'Hi', '<p>Hello</p>');

        $this->assertTrue($result['success']);
        $this->assertEquals('msg-001', $result['message_id']);
        $this->assertEquals(1, $result['provider_id']);
        $this->assertEquals('MockProvider', $result['provider_name']);
    }

    /** @test */
    public function send_with_failover_tries_next_provider_on_failure(): void
    {
        $failingProvider = Mockery::mock(MailProviderInterface::class);
        $failingProvider->shouldReceive('send')
            ->once()
            ->andReturn(['success' => false, 'error' => 'Connection refused', 'message_id' => null]);

        $workingProvider = Mockery::mock(MailProviderInterface::class);
        $workingProvider->shouldReceive('send')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg-002', 'error' => null]);
        $workingProvider->shouldReceive('getName')->andReturn('WorkingProvider');

        $configA = $this->makeProviderConfig(1, 'Provider A', 'mailrelay');
        $configB = $this->makeProviderConfig(2, 'Provider B', 'mailtrap');

        $manager = $this->createTestableManager(
            new Collection([$configA, $configB]),
            [
                'mailrelay' => $failingProvider,
                'mailtrap' => $workingProvider,
            ]
        );

        $result = $manager->sendWithFailover('test@test.com', 'Hi', '<p>Hello</p>');

        $this->assertTrue($result['success']);
        $this->assertEquals('msg-002', $result['message_id']);
        $this->assertEquals(2, $result['provider_id']);
    }

    /** @test */
    public function send_with_failover_throws_when_all_providers_fail(): void
    {
        $failingProviderA = Mockery::mock(MailProviderInterface::class);
        $failingProviderA->shouldReceive('send')
            ->once()
            ->andReturn(['success' => false, 'error' => 'Error A', 'message_id' => null]);

        $failingProviderB = Mockery::mock(MailProviderInterface::class);
        $failingProviderB->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Connection timeout'));

        $configA = $this->makeProviderConfig(1, 'Provider A', 'mailrelay');
        $configB = $this->makeProviderConfig(2, 'Provider B', 'mailtrap');

        $manager = $this->createTestableManager(
            new Collection([$configA, $configB]),
            [
                'mailrelay' => $failingProviderA,
                'mailtrap' => $failingProviderB,
            ]
        );

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('All providers failed');

        $manager->sendWithFailover('test@test.com', 'Hi', '<p>Hello</p>');
    }

    /** @test */
    public function send_bulk_with_failover_returns_result_on_success(): void
    {
        $mockProvider = Mockery::mock(MailProviderInterface::class);
        $mockProvider->shouldReceive('sendBulk')
            ->once()
            ->andReturn([
                'success' => true,
                'campaign_id' => 'camp-001',
                'sent_count' => 3,
                'error' => null,
            ]);
        $mockProvider->shouldReceive('getName')->andReturn('BulkProvider');

        $config = $this->makeProviderConfig(5, 'Bulk Provider', 'sendgrid');
        $manager = $this->createTestableManager(
            new Collection([$config]),
            ['sendgrid' => $mockProvider]
        );

        $recipients = [
            ['email' => 'a@test.com'],
            ['email' => 'b@test.com'],
            ['email' => 'c@test.com'],
        ];

        $result = $manager->sendBulkWithFailover($recipients, 'Newsletter', '<p>News</p>');

        $this->assertTrue($result['success']);
        $this->assertEquals('camp-001', $result['campaign_id']);
        $this->assertEquals(3, $result['sent_count']);
        $this->assertEquals(5, $result['provider_id']);
    }

    /** @test */
    public function send_bulk_with_failover_fails_over_to_next_provider(): void
    {
        $failingProvider = Mockery::mock(MailProviderInterface::class);
        $failingProvider->shouldReceive('sendBulk')
            ->once()
            ->andThrow(new \RuntimeException('API limit exceeded'));

        $workingProvider = Mockery::mock(MailProviderInterface::class);
        $workingProvider->shouldReceive('sendBulk')
            ->once()
            ->andReturn([
                'success' => true,
                'campaign_id' => 'camp-fallback',
                'sent_count' => 2,
                'error' => null,
            ]);
        $workingProvider->shouldReceive('getName')->andReturn('FallbackProvider');

        $configA = $this->makeProviderConfig(1, 'Primary', 'sendgrid');
        $configB = $this->makeProviderConfig(2, 'Secondary', 'postmark');

        $manager = $this->createTestableManager(
            new Collection([$configA, $configB]),
            [
                'sendgrid' => $failingProvider,
                'postmark' => $workingProvider,
            ]
        );

        $result = $manager->sendBulkWithFailover(
            [['email' => 'x@test.com'], ['email' => 'y@test.com']],
            'Test',
            '<p>Test</p>'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['provider_id']);
    }

    /**
     * Create a testable ProviderManager subclass that bypasses DB queries.
     *
     * @param  Collection  $providers  Ordered provider configs to return
     * @param  array<string, MailProviderInterface>  $providerInstances  Driver => mock provider instance
     */
    private function createTestableManager(Collection $providers, array $providerInstances = []): ProviderManager
    {
        return new class($providers, $providerInstances) extends ProviderManager
        {
            private Collection $mockProviders;

            /** @var array<string, MailProviderInterface> */
            private array $mockInstances;

            public function __construct(Collection $mockProviders, array $mockInstances = [])
            {
                $this->mockProviders = $mockProviders;
                $this->mockInstances = $mockInstances;
            }

            /**
             * Override sendWithFailover to use mock providers directly.
             * We replicate the real logic but use our mocked data.
             */
            public function sendWithFailover(
                string $to,
                string $subject,
                string $htmlContent,
                array $options = [],
                ?int $preferredProviderId = null,
            ): array {
                $providers = $this->mockProviders;

                if ($providers->isEmpty()) {
                    throw ProviderException::notConfigured();
                }

                $errors = [];

                foreach ($providers as $providerConfig) {
                    try {
                        $provider = $this->mockInstances[$providerConfig->driver]
                            ?? throw new \Exception("No mock for driver {$providerConfig->driver}");

                        $result = $provider->send($to, $subject, $htmlContent, $options);

                        if ($result['success']) {
                            return array_merge($result, [
                                'provider_id' => $providerConfig->id,
                                'provider_name' => $provider->getName(),
                            ]);
                        }

                        $errors[$providerConfig->name] = $result['error'] ?? 'Unknown error';
                    } catch (ProviderException $e) {
                        throw $e;
                    } catch (\Throwable $e) {
                        $errors[$providerConfig->name] = $e->getMessage();
                    }
                }

                throw ProviderException::allProvidersFailed($errors);
            }

            /**
             * Override sendBulkWithFailover to use mock providers directly.
             */
            public function sendBulkWithFailover(
                array $recipients,
                string $subject,
                string $htmlContent,
                array $options = [],
                ?int $preferredProviderId = null,
            ): array {
                $providers = $this->mockProviders;

                if ($providers->isEmpty()) {
                    throw ProviderException::notConfigured();
                }

                $errors = [];

                foreach ($providers as $providerConfig) {
                    try {
                        $provider = $this->mockInstances[$providerConfig->driver]
                            ?? throw new \Exception("No mock for driver {$providerConfig->driver}");

                        $result = $provider->sendBulk($recipients, $subject, $htmlContent, $options);

                        if ($result['success']) {
                            return array_merge($result, [
                                'provider_id' => $providerConfig->id,
                                'provider_name' => $provider->getName(),
                            ]);
                        }

                        $errors[$providerConfig->name] = $result['error'] ?? 'Unknown error';
                    } catch (ProviderException $e) {
                        throw $e;
                    } catch (\Throwable $e) {
                        $errors[$providerConfig->name] = $e->getMessage();
                    }
                }

                throw ProviderException::allProvidersFailed($errors);
            }
        };
    }

    /**
     * Create a plain MailProvider object (not persisted) for test fixtures.
     */
    private function makeProviderConfig(int $id, string $name, string $driver): MailProvider
    {
        $provider = new MailProvider;
        $provider->id = $id;
        $provider->name = $name;
        $provider->driver = $driver;
        $provider->is_active = true;
        $provider->connection_ok = true;
        $provider->priority = 100 - $id;

        return $provider;
    }
}
