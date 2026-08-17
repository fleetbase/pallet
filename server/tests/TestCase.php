<?php

namespace Fleetbase\Pallet\Tests;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * Base test case: a Testbench application on SQLite with Pallet's own
 * schema migrated.
 *
 * - Both the default and the `mysql` connection point at one shared SQLite
 *   file (Fleetbase core models pin `$connection = 'mysql'` explicitly, and
 *   two `:memory:` configs would be two different databases). The file is
 *   recreated once per PHP process; tests reuse the migrated schema.
 * - Only Pallet's migrations run here: the Fleetbase core migration set is
 *   MySQL-only (raw MODIFY COLUMN, DATE_FORMAT, spatial columns) and can
 *   never run on SQLite. Core tables Pallet code touches directly get
 *   minimal shims. The full, real migration chain — and every MySQL-only
 *   contract (locking, strict mode, spatial, collations) — is validated in
 *   the MySQL test lane.
 */
abstract class TestCase extends TestbenchTestCase
{
    private static bool $freshDatabasePrepared = false;

    protected function getPackageProviders($app): array
    {
        return [
            \Spatie\ResponseCache\ResponseCacheServiceProvider::class,
            \Spatie\Activitylog\ActivitylogServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $dbFile = self::databaseFile();

        if (!self::$freshDatabasePrepared) {
            @unlink($dbFile);
            self::$freshDatabasePrepared = true;
        }

        if (!file_exists($dbFile)) {
            touch($dbFile);
        }

        $sqlite = [
            'driver'                  => 'sqlite',
            'database'                => $dbFile,
            'prefix'                  => '',
            'foreign_key_constraints' => false,
        ];

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $sqlite);
        $app['config']->set('database.connections.mysql', $sqlite);

        $permissionPath = InstalledVersions::getInstallPath('spatie/laravel-permission');
        $app['config']->set('permission', require $permissionPath . '/config/permission.php');

        // model saves flush the response cache and log activity; keep both inert in tests
        $app['config']->set('responsecache.enabled', false);
        $app['config']->set('activitylog.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        self::registerCoreExpansions();
        $this->createCoreTableShims();

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
    }

    /**
     * Apply fleetbase/core-api's class expansions (Str::humanize etc.) the
     * same way CoreServiceProvider::registerExpansionsFrom() does, without
     * booting the full core provider.
     */
    private static function registerCoreExpansions(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $corePath = InstalledVersions::getInstallPath('fleetbase/core-api');

        foreach (glob($corePath . '/src/Expansions/*.php') as $file) {
            $class = 'Fleetbase\\Expansions\\' . basename($file, '.php');

            if (!class_exists($class)) {
                continue;
            }

            $target = $class::target();

            if (!is_string($target) || !class_exists($target)) {
                continue;
            }

            try {
                $target::expand(new $class());
            } catch (\Throwable) {
                try {
                    $target::mixin(new $class());
                } catch (\Throwable) {
                }
            }
        }
    }

    /**
     * Minimal SQLite stand-ins for core Fleetbase tables that Pallet code
     * references (relations, FK targets). The real tables are created by
     * core/FleetOps MySQL migrations, exercised in the MySQL lane.
     */
    private function createCoreTableShims(): void
    {
        $shims = [
            'companies' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'users' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'places' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('name')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'files' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('uploader_uuid', 191)->nullable()->index();
                $table->string('subject_uuid', 191)->nullable()->index();
                $table->string('subject_type', 191)->nullable();
                $table->string('disk', 191)->nullable();
                $table->string('path')->nullable();
                $table->string('original_filename')->nullable();
                $table->string('content_type')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'transactions' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('status', 191)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'contacts' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'vendors' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('name')->nullable();
                $table->string('type', 191)->nullable();
                $table->string('status', 191)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'categories' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
        ];

        foreach ($shims as $name => $definition) {
            if (!Schema::hasTable($name)) {
                Schema::create($name, $definition);
            }
        }
    }

    private static function databaseFile(): string
    {
        return sys_get_temp_dir() . '/pallet-test-' . getmypid() . '.sqlite';
    }
}
