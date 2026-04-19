<?php

namespace Modules\Auth\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Base TestCase for Auth module tests.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) because the project's test
 * bootstrap has pre-existing FK ordering issues with self-referencing tables
 * that break `migrate:fresh`. Each test wraps its DB ops in a transaction
 * that rolls back on teardown.
 *
 * Required Auth tables are re-created idempotently at each test startup to
 * survive across test runs that may drop them.
 */
abstract class AuthTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureAuthSchema();
    }

    private function ensureAuthSchema(): void
    {
        if (! Schema::hasTable('users')) {
            DB::statement('CREATE TABLE users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                uid VARCHAR(191) NULL UNIQUE,
                firstname VARCHAR(191) NULL,
                lastname VARCHAR(191) NULL,
                identification VARCHAR(191) NULL,
                cellphone VARCHAR(191) NULL,
                email VARCHAR(191) NOT NULL UNIQUE,
                mail_verified_at TIMESTAMP NULL,
                password VARCHAR(191) NOT NULL,
                address VARCHAR(191) NULL,
                available TINYINT(1) NOT NULL DEFAULT 1,
                verified TINYINT(1) NOT NULL DEFAULT 0,
                terms TINYINT(1) NOT NULL DEFAULT 0,
                validation VARCHAR(191) NULL,
                page VARCHAR(191) NULL,
                setting JSON NULL,
                role VARCHAR(191) NULL,
                company VARCHAR(191) NULL,
                detail JSON NULL,
                user_img VARCHAR(191) NULL,
                citie_id BIGINT UNSIGNED NULL,
                enterprise_id BIGINT UNSIGNED NULL,
                timezone VARCHAR(191) NULL,
                locale VARCHAR(10) NULL,
                voilated TINYINT(1) NOT NULL DEFAULT 0,
                last_login_at TIMESTAMP NULL,
                last_login_ip VARCHAR(191) NULL,
                last_logins_at TIMESTAMP NULL,
                two_factor_secret TEXT NULL,
                two_factor_recovery_codes TEXT NULL,
                two_factor_confirmed_at TIMESTAMP NULL,
                password_changed_at TIMESTAMP NULL,
                must_change_password TINYINT(1) NOT NULL DEFAULT 0,
                password_reset_token VARCHAR(191) NULL,
                password_reset_max_tries INT NULL,
                password_reset_last_tried_on TIMESTAMP NULL,
                remember_token VARCHAR(100) NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX (email),
                INDEX (available)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('password_histories')) {
            DB::statement('CREATE TABLE password_histories (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL,
                INDEX (user_id, created_at)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('login_attempts')) {
            DB::statement('CREATE TABLE login_attempts (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                email VARCHAR(255) NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                status VARCHAR(20) NOT NULL,
                reason VARCHAR(255) NULL,
                country VARCHAR(100) NULL,
                city VARCHAR(100) NULL,
                attempted_at TIMESTAMP NULL,
                INDEX (user_id, attempted_at),
                INDEX (email, attempted_at),
                INDEX (ip_address, attempted_at),
                INDEX (status)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('trusted_devices')) {
            DB::statement('CREATE TABLE trusted_devices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                fingerprint VARCHAR(64) NOT NULL,
                label VARCHAR(255) NULL,
                platform VARCHAR(50) NULL,
                browser VARCHAR(50) NULL,
                device_type VARCHAR(30) NULL,
                ip_address VARCHAR(45) NULL,
                country VARCHAR(100) NULL,
                city VARCHAR(100) NULL,
                trusted TINYINT(1) NOT NULL DEFAULT 0,
                first_seen_at TIMESTAMP NULL,
                last_seen_at TIMESTAMP NULL,
                trusted_until TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE (user_id, fingerprint),
                INDEX (last_seen_at)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('impersonation_logs')) {
            DB::statement('CREATE TABLE impersonation_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                impersonator_id BIGINT UNSIGNED NOT NULL,
                impersonated_id BIGINT UNSIGNED NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                reason VARCHAR(255) NULL,
                started_at TIMESTAMP NULL,
                ended_at TIMESTAMP NULL,
                INDEX (impersonator_id, started_at),
                INDEX (impersonated_id, started_at)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('settings')) {
            DB::statement('CREATE TABLE settings (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(191) NOT NULL UNIQUE,
                value TEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('permissions')) {
            DB::statement('CREATE TABLE permissions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                guard_name VARCHAR(191) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE (name, guard_name)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('roles')) {
            DB::statement('CREATE TABLE roles (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                guard_name VARCHAR(191) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE (name, guard_name)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('model_has_permissions')) {
            DB::statement('CREATE TABLE model_has_permissions (
                permission_id BIGINT UNSIGNED NOT NULL,
                model_type VARCHAR(191) NOT NULL,
                model_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (permission_id, model_id, model_type),
                INDEX (model_id, model_type)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('model_has_roles')) {
            DB::statement('CREATE TABLE model_has_roles (
                role_id BIGINT UNSIGNED NOT NULL,
                model_type VARCHAR(191) NOT NULL,
                model_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (role_id, model_id, model_type),
                INDEX (model_id, model_type)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('role_has_permissions')) {
            DB::statement('CREATE TABLE role_has_permissions (
                permission_id BIGINT UNSIGNED NOT NULL,
                role_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (permission_id, role_id)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('personal_access_tokens')) {
            DB::statement('CREATE TABLE personal_access_tokens (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                tokenable_type VARCHAR(191) NOT NULL,
                tokenable_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(191) NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                abilities TEXT NULL,
                last_used_at TIMESTAMP NULL,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX (tokenable_type, tokenable_id)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasColumn('users', 'password_changed_at')) {
            DB::statement('ALTER TABLE users
                ADD COLUMN password_changed_at TIMESTAMP NULL,
                ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0');
        }

        if (Schema::hasTable('activity_log') && ! Schema::hasColumn('activity_log', 'event')) {
            DB::statement('ALTER TABLE activity_log ADD COLUMN event VARCHAR(255) NULL AFTER batch_uuid');
        }

        if (! Schema::hasTable('seo_redirects')) {
            DB::statement('CREATE TABLE seo_redirects (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                source_path VARCHAR(500) NOT NULL,
                target_path VARCHAR(500) NOT NULL,
                status_code INT NOT NULL DEFAULT 301,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                is_regex TINYINT(1) NOT NULL DEFAULT 0,
                active_from TIMESTAMP NULL,
                active_until TIMESTAMP NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX (source_path),
                INDEX (is_active)
            ) ENGINE=InnoDB');
        } else {
            foreach (['active_from' => 'TIMESTAMP NULL', 'active_until' => 'TIMESTAMP NULL', 'is_regex' => 'TINYINT(1) NOT NULL DEFAULT 0', 'is_wildcard' => 'TINYINT(1) NOT NULL DEFAULT 0'] as $col => $def) {
                if (! Schema::hasColumn('seo_redirects', $col)) {
                    DB::statement("ALTER TABLE seo_redirects ADD COLUMN {$col} {$def}");
                }
            }
        }

        if (! Schema::hasColumn('users', 'locale')) {
            DB::statement('ALTER TABLE users ADD COLUMN locale VARCHAR(10) NULL');
        }

        if (! Schema::hasColumn('users', 'two_factor_secret')) {
            DB::statement('ALTER TABLE users
                ADD COLUMN two_factor_secret TEXT NULL,
                ADD COLUMN two_factor_recovery_codes TEXT NULL,
                ADD COLUMN two_factor_confirmed_at TIMESTAMP NULL');
        }
    }
}
