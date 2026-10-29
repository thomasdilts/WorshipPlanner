<?php

namespace Tests\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConfigurationUrlParser;
use RuntimeException;
use Throwable;

/**
 * Fails closed before any test touches a database that is not an explicitly designated test database.
 *
 * Allowed: in-memory SQLite, a SQLite file under storage/framework/testing, or a loopback
 * MySQL/MariaDB database whose name ends in `_test` or `_testing`. Everything else aborts the run.
 */
final class TestDatabaseGuard
{
    private const STOCK_CONNECTIONS = ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'];

    private const LOOPBACK_HOSTS = ['127.0.0.1', 'localhost', '::1'];

    public static function assertSafe(Application $app): void
    {
        $config = $app->make('config');

        $violations = self::violations([
            'environment' => (string) $app->environment(),
            'config_cached' => $app->configurationIsCached(),
            'default' => (string) $config->get('database.default'),
            'connections' => (array) $config->get('database.connections', []),
            'auxiliary' => [
                'queue.connections.database.connection' => $config->get('queue.connections.database.connection'),
                'cache.stores.database.connection' => $config->get('cache.stores.database.connection'),
                'session.connection' => $config->get('session.connection'),
            ],
            'testing_sqlite_directory' => storage_path('framework/testing'),
        ]);

        if ($violations !== []) {
            throw new RuntimeException(
                "Refusing to run tests against a non-test database:\n - ".implode("\n - ", $violations)
            );
        }
    }

    /**
     * @param  array{
     *     environment: string,
     *     config_cached: bool,
     *     default: string,
     *     connections: array<string, array<string, mixed>>,
     *     auxiliary: array<string, string|null>,
     *     testing_sqlite_directory: string,
     * }  $inputs
     * @return list<string>
     */
    public static function violations(array $inputs): array
    {
        $violations = [];

        if ($inputs['environment'] !== 'testing') {
            $violations[] = "APP_ENV is '{$inputs['environment']}', expected 'testing'.";
        }

        if ($inputs['config_cached']) {
            $violations[] = 'Configuration is cached; cached config ignores phpunit.xml. Run `php artisan config:clear`.';
        }

        $default = $inputs['default'];

        if (! isset($inputs['connections'][$default])) {
            $violations[] = "Default connection '{$default}' is not defined.";
        }

        foreach ($inputs['connections'] as $name => $connection) {
            $isDefault = $name === $default;
            $isStock = in_array($name, self::STOCK_CONNECTIONS, true);

            if (! $isDefault && $isStock) {
                continue;
            }

            foreach (self::connectionViolations($name, $connection, $inputs['testing_sqlite_directory']) as $violation) {
                $violations[] = $violation;
            }
        }

        foreach ($inputs['auxiliary'] as $setting => $connectionName) {
            if ($connectionName !== null && $connectionName !== '' && $connectionName !== $default) {
                $violations[] = "{$setting} points at '{$connectionName}' instead of the default test connection.";
            }
        }

        return $violations;
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return list<string>
     */
    private static function connectionViolations(string $name, array $connection, string $sqliteDirectory): array
    {
        try {
            $resolved = (new ConfigurationUrlParser)->parseConfiguration($connection);
        } catch (Throwable $e) {
            return ["Connection '{$name}' has an unparseable URL: {$e->getMessage()}"];
        }

        $driver = (string) ($resolved['driver'] ?? '');
        $database = (string) ($resolved['database'] ?? '');

        if ($driver === 'sqlite') {
            if ($database === ':memory:' || str_starts_with($database, rtrim($sqliteDirectory, '/').'/')) {
                return [];
            }

            return ["Connection '{$name}' uses SQLite file '{$database}', which is not in-memory or under {$sqliteDirectory}."];
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $violations = [];
            $host = (string) ($resolved['host'] ?? '');
            $usesSocket = filled($resolved['unix_socket'] ?? null);

            if (! $usesSocket && ! in_array($host, self::LOOPBACK_HOSTS, true)) {
                $violations[] = "Connection '{$name}' host '{$host}' is not loopback.";
            }

            if (preg_match('/_(test|testing)$/', $database) !== 1) {
                $violations[] = "Connection '{$name}' database '{$database}' does not end in _test or _testing.";
            }

            return $violations;
        }

        return ["Connection '{$name}' uses driver '{$driver}', which is not an approved test driver."];
    }
}
