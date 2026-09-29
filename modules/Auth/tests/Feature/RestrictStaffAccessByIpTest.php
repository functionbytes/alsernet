<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Auth\Http\Middleware\RestrictStaffAccessByIp;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Services\StaffIpAllowlist;
use Modules\Auth\Tests\AuthTestCase;

/**
 * Filtro por IP del login y del panel (29-sep-2026).
 *
 * IP externa simulada: 8.8.8.8. Lista de prueba: 203.0.113.0/24 (TEST-NET-3).
 */
class RestrictStaffAccessByIpTest extends AuthTestCase
{
    private const OUTSIDE = '8.8.8.8';

    private const INSIDE = '203.0.113.10';

    /** Rutas públicas que nunca deben filtrarse (plan, sección 1, derecha). */
    private const PUBLIC_ROUTES = [
        ['GET', '/portal/login'],
        ['GET', '/hd/widget-loader.js'],
        ['GET', '/hd/api/settings'],
        ['GET', '/widget'],
        ['GET', '/build-helpdesklivechat/index.html'],
        ['GET', '/api/documents/verify'],
        ['POST', '/api/documents/webhooks/prestashop/order-paid'],
        ['POST', '/api/documents/webhooks/erp/order-status'],
        ['GET', '/api/erp/health'],
        ['GET', '/up'],
        ['GET', '/helpcenter'],
    ];

    /** Rutas del personal que sí se filtran. */
    private const STAFF_ROUTES = [
        ['GET', '/login'],
        ['GET', '/forgot-password'],
        ['GET', '/magic-link'],
        ['GET', '/two-factor/challenge'],
        ['GET', '/lock'],
        ['GET', '/panel/settings/auth/profile'],
        ['POST', '/broadcasting/auth'],
        ['POST', '/api/auth/login'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('users', 'remote_access_enabled')) {
            DB::statement('ALTER TABLE users ADD COLUMN remote_access_enabled TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN remote_access_until TIMESTAMP NULL');
        }

        config([
            'cache.default' => 'array',
            'auth.auth-policy.staff_ip_filter.force_off' => false,
        ]);
        $this->withoutMiddleware(\Modules\Core\Http\Middleware\VerifyCsrfToken::class);

        $filter = app(StaffIpAllowlist::class);
        $filter->saveEntries([['ip' => '203.0.113.0/24', 'description' => 'Oficina de prueba']]);
    }

    private function mode(string $mode): void
    {
        app(StaffIpAllowlist::class)->setMode($mode);
    }

    private function from(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    private function remoteUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'available' => true,
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
            'remote_access_enabled' => true,
            'remote_access_until' => null,
        ], $attrs));
    }

    public function test_public_routes_are_never_filtered_even_in_enforce(): void
    {
        $this->mode('enforce');

        foreach (self::PUBLIC_ROUTES as [$method, $uri]) {
            $response = $this->from(self::OUTSIDE)->call($method, $uri);

            $this->assertFalse(
                $response->getStatusCode() === 403 && str_contains((string) $response->getContent(), 'Acceso no disponible'),
                "{$method} {$uri} no debería pasar por el filtro"
            );
        }

        $this->assertSame(0, LoginAttempt::where('status', RestrictStaffAccessByIp::STATUS_NOT_ALLOWED)->count());
    }

    public function test_staff_routes_are_blocked_from_outside_in_enforce(): void
    {
        $this->mode('enforce');

        foreach (self::STAFF_ROUTES as [$method, $uri]) {
            $this->from(self::OUTSIDE)->call($method, $uri)->assertStatus(403);
        }

        $this->assertGreaterThan(0, LoginAttempt::where('status', RestrictStaffAccessByIp::STATUS_NOT_ALLOWED)->count());
    }

    public function test_monitor_logs_but_does_not_block(): void
    {
        $this->mode('monitor');

        $this->from(self::OUTSIDE)->get('/login')->assertOk();

        $this->assertDatabaseHas('login_attempts', [
            'ip_address' => self::OUTSIDE,
            'status' => RestrictStaffAccessByIp::STATUS_NOT_ALLOWED,
        ]);
    }

    public function test_off_does_nothing(): void
    {
        $this->mode('off');

        $this->from(self::OUTSIDE)->get('/login')->assertOk();
        $this->assertSame(0, LoginAttempt::count());
    }

    public function test_force_off_config_has_priority(): void
    {
        $this->mode('enforce');
        config(['auth.auth-policy.staff_ip_filter.force_off' => true]);

        $this->from(self::OUTSIDE)->get('/login')->assertOk();
    }

    public function test_allowlisted_ip_and_localhost_pass_in_enforce(): void
    {
        $this->mode('enforce');

        $this->from(self::INSIDE)->get('/login')->assertOk();
        $this->from('127.0.0.1')->get('/login')->assertOk();
        $this->from('::1')->get('/login')->assertOk();
    }

    public function test_valid_session_from_outside_is_closed_in_enforce(): void
    {
        $this->mode('enforce');
        $user = User::factory()->create(['available' => true]);

        $this->actingAs($user)->from(self::OUTSIDE)
            ->get('/panel/settings/auth/profile')
            ->assertStatus(403);

        $this->assertGuest();
    }

    public function test_remote_exception_requires_2fa_verified_in_session(): void
    {
        $this->mode('enforce');
        $user = $this->remoteUser();

        $this->actingAs($user)->from(self::OUTSIDE)
            ->get('/panel/settings/auth/profile')
            ->assertStatus(403);

        $this->actingAs($user)->withSession(['two_factor_passed' => true])->from(self::OUTSIDE)
            ->get('/panel/settings/auth/profile')
            ->assertStatus(200);
    }

    public function test_remote_exception_without_2fa_has_no_effect(): void
    {
        $this->mode('enforce');
        $user = $this->remoteUser(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);

        $this->actingAs($user)->withSession(['two_factor_passed' => true])->from(self::OUTSIDE)
            ->get('/panel/settings/auth/profile')
            ->assertStatus(403);
    }

    public function test_expired_remote_exception_is_rejected(): void
    {
        $this->mode('enforce');
        $user = $this->remoteUser(['remote_access_until' => now()->subMinute()]);

        $this->actingAs($user)->withSession(['two_factor_passed' => true])->from(self::OUTSIDE)
            ->get('/panel/settings/auth/profile')
            ->assertStatus(403);
    }

    public function test_login_form_opens_from_outside_only_when_a_remote_exception_exists(): void
    {
        $this->mode('enforce');
        $this->from(self::OUTSIDE)->get('/login')->assertStatus(403);

        $this->remoteUser();
        app(StaffIpAllowlist::class)->clearCache();

        $this->from(self::OUTSIDE)->get('/login')->assertOk();
        $this->from(self::OUTSIDE)->get('/forgot-password')->assertStatus(403);
    }

    public function test_self_lockout_protection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(StaffIpAllowlist::class)->saveEntries([['ip' => '10.0.0.0/8', 'description' => 'x']], self::OUTSIDE);
    }

    public function test_cidr_ipv6_and_validation(): void
    {
        $filter = app(StaffIpAllowlist::class);

        $this->assertTrue($filter->isAllowed('203.0.113.200'));
        $this->assertFalse($filter->isAllowed(self::OUTSIDE));
        $this->assertTrue($filter->isAllowed('2001:db8::1', [['ip' => '2001:db8::/32']]));
        $this->assertTrue(StaffIpAllowlist::isValidIpOrCidr('192.168.1.0/24'));
        $this->assertFalse(StaffIpAllowlist::isValidIpOrCidr('192.168.1.0/33'));
        $this->assertFalse(StaffIpAllowlist::isValidIpOrCidr('300.1.1.1'));
    }
}
