<?php

namespace Tests\Feature\Baseline;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class TestDatabaseGuardTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function inputs(array $overrides = []): array
    {
        return array_replace_recursive([
            'environment' => 'testing',
            'config_cached' => false,
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => ['driver' => 'sqlite', 'url' => null, 'database' => ':memory:'],
                'mariadb' => ['driver' => 'mariadb', 'url' => null, 'host' => '203.0.113.9', 'database' => 'laravel'],
            ],
            'auxiliary' => [],
            'testing_sqlite_directory' => '/app/storage/framework/testing',
        ], $overrides);
    }

    public function test_in_memory_sqlite_is_allowed(): void
    {
        $this->assertSame([], TestDatabaseGuard::violations($this->inputs()));
    }

    public function test_sqlite_file_is_allowed_only_inside_the_testing_directory(): void
    {
        $inside = $this->inputs(['connections' => ['sqlite' => ['database' => '/app/storage/framework/testing/run.sqlite']]]);
        $outside = $this->inputs(['connections' => ['sqlite' => ['database' => '/app/database/database.sqlite']]]);

        $this->assertSame([], TestDatabaseGuard::violations($inside));
        $this->assertNotSame([], TestDatabaseGuard::violations($outside));
    }

    #[DataProvider('unsafeMariaDbConnections')]
    public function test_non_test_mariadb_connections_are_rejected(array $connection): void
    {
        $violations = TestDatabaseGuard::violations($this->inputs([
            'default' => 'mariadb',
            'connections' => ['mariadb' => $connection],
        ]));

        $this->assertNotSame([], $violations);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function unsafeMariaDbConnections(): array
    {
        return [
            'remote host with test-like name' => [['host' => 'db.example.com', 'database' => 'worship_test']],
            'loopback host with live-like name' => [['host' => '127.0.0.1', 'database' => 'worshipplanner']],
            'ssh tunnel to live database' => [['host' => '127.0.0.1', 'port' => 3307, 'database' => 'worshipplanner_prod']],
            'url overrides host and database' => [['host' => '127.0.0.1', 'database' => 'x_test', 'url' => 'mysql://u:p@db.example.com:3306/worshipplanner']],
        ];
    }

    public function test_loopback_mariadb_test_database_is_allowed(): void
    {
        $violations = TestDatabaseGuard::violations($this->inputs([
            'default' => 'mariadb',
            'connections' => ['mariadb' => ['host' => '127.0.0.1', 'database' => 'worshipplanner_test']],
        ]));

        $this->assertSame([], $violations);
    }

    public function test_cached_config_and_wrong_environment_are_rejected(): void
    {
        $this->assertNotSame([], TestDatabaseGuard::violations($this->inputs(['config_cached' => true])));
        $this->assertNotSame([], TestDatabaseGuard::violations($this->inputs(['environment' => 'local'])));
    }

    public function test_extra_custom_connections_and_auxiliary_overrides_are_rejected(): void
    {
        $extra = $this->inputs(['connections' => ['hhn' => ['driver' => 'mariadb', 'host' => '127.0.0.1', 'database' => 'ppp']]]);
        $queue = $this->inputs(['auxiliary' => ['queue.connections.database.connection' => 'mariadb']]);

        $this->assertNotSame([], TestDatabaseGuard::violations($extra));
        $this->assertNotSame([], TestDatabaseGuard::violations($queue));
    }

    public function test_unapproved_drivers_are_rejected(): void
    {
        $violations = TestDatabaseGuard::violations($this->inputs([
            'default' => 'pgsql',
            'connections' => ['pgsql' => ['driver' => 'pgsql', 'host' => '127.0.0.1', 'database' => 'x_test']],
        ]));

        $this->assertNotSame([], $violations);
    }

    public function test_the_running_test_suite_uses_in_memory_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
    }

    public function test_polluted_shell_environment_cannot_redirect_the_suite(): void
    {
        $process = new Process(
            [PHP_BINARY, base_path('vendor/bin/phpunit'), '--filter', 'test_probe_confirms_forced_test_environment', __FILE__],
            base_path(),
            [
                'WP_GUARD_PROBE' => '1',
                'APP_ENV' => 'production',
                'DB_CONNECTION' => 'mariadb',
                'DB_HOST' => 'db.live.invalid',
                'DB_DATABASE' => 'worshipplanner',
                'DB_URL' => 'mysql://live:secret@db.live.invalid:3306/worshipplanner',
                'MAIL_MAILER' => 'smtp',
                'SESSION_DRIVER' => 'database',
                'CACHE_STORE' => 'database',
            ],
        );
        $process->setTimeout(120)->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
    }

    public function test_probe_confirms_forced_test_environment(): void
    {
        if (getenv('WP_GUARD_PROBE') !== '1') {
            $this->markTestSkipped('Only runs inside the polluted-environment subprocess.');
        }

        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('array', config('session.driver'));
        $this->assertSame('array', config('cache.default'));
    }
}
