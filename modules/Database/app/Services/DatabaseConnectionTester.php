<?php

namespace Modules\Database\Services;

class DatabaseConnectionTester
{
    /**
     * Test a MySQL/MariaDB connection using the provided settings.
     *
     * @param  array<string, mixed>  $settings
     * @return array{success: bool, version: string}
     */
    public function testMySQL(array $settings): array
    {
        $conn = @mysqli_connect(
            $settings['db_host'],
            $settings['db_username'],
            $settings['db_password'],
            $settings['db_database'],
            (int) $settings['db_port']
        );

        if (! $conn) {
            throw new \Exception(mysqli_connect_error() ?: 'Error desconocido en MySQL');
        }

        $result = mysqli_query($conn, 'SELECT VERSION() as version');
        $versionInfo = mysqli_fetch_assoc($result);
        mysqli_close($conn);

        return [
            'success' => true,
            'version' => $versionInfo['version'] ?? 'Desconocida',
        ];
    }

    /**
     * Test a PostgreSQL connection using the provided settings.
     *
     * @param  array<string, mixed>  $settings
     * @return array{success: bool, version: string}
     */
    public function testPostgreSQL(array $settings): array
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $settings['db_host'],
            $settings['db_port'] ?? 5432,
            $settings['db_database']
        );

        try {
            $pdo = new \PDO($dsn, $settings['db_username'], $settings['db_password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Error de conexión PostgreSQL: '.$e->getMessage());
        }

        $result = $pdo->query('SELECT version() as version');
        $versionInfo = $result->fetch(\PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'version' => $versionInfo['version'] ?? 'Desconocida',
        ];
    }

    /**
     * Test a SQLite connection using the provided settings.
     *
     * @param  array<string, mixed>  $settings
     * @return array{success: bool, version: string}
     */
    public function testSQLite(array $settings): array
    {
        $dbPath = $settings['db_database'] ?? database_path('database.sqlite');

        try {
            $pdo = new \PDO('sqlite:'.$dbPath, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Error de conexión SQLite: '.$e->getMessage());
        }

        $result = $pdo->query('SELECT sqlite_version() as version');
        $versionInfo = $result->fetch(\PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'version' => $versionInfo['version'] ?? 'Desconocida',
        ];
    }
}
