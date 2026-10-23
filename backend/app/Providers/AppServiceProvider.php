<?php

namespace App\Providers;

use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\Nota;
use App\Observers\DashboardCacheObserver;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use PDO;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Workaround para libpq antiguos (Windows) que no envían SNI:
     * Neon exige el endpoint ID como parámetro `options` del DSN.
     * Ver https://neon.com/sni
     */
    private const NEON_ENDPOINT_ID = 'ep-silent-frog-b4nnxlg5';

    public function register(): void
    {
        DB::extend('pgsql', function (array $config, string $name) {
            return $this->esNeon($config)
                ? $this->makeNeonConnection($config, $name)
                : $this->makePgsqlConnection($config, $name);
        });

        DB::extend('pgsql_connection', function (array $config, string $name) {
            return $this->esNeon($config)
                ? $this->makeNeonConnection($config, $name)
                : $this->makePgsqlConnection($config, $name);
        });
    }

    private function esNeon(array $config): bool
    {
        return str_contains((string) ($config['host'] ?? ''), 'neon.tech');
    }

    private function makePgsqlConnection(array $config, string $name): PostgresConnection
    {
        $config['name'] ??= $name;

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $config['host'],
            $config['port'] ?? 5432,
            $config['database'],
            $config['sslmode'] ?? 'prefer'
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return new PostgresConnection($pdo, $config['database'], '', $config);
    }

    public function boot(): void
    {
        Asistencia::observe(DashboardCacheObserver::class);
        Licencia::observe(DashboardCacheObserver::class);
        Nota::observe(DashboardCacheObserver::class);
    }

    private function makeNeonConnection(array $config, string $name): PostgresConnection
    {
        $config['name'] ??= $name;

        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname='%s';options='endpoint=%s';sslmode=require",
            $config['host'],
            $config['port'] ?? 5432,
            $config['database'],
            self::NEON_ENDPOINT_ID
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return new PostgresConnection($pdo, $config['database'], '', $config);
    }
}
