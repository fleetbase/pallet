<?php

namespace Fleetbase\Pallet\Tests;

use Composer\InstalledVersions;
use Fleetbase\Pallet\Providers\PalletServiceProvider;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
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

        // default MUST be the same connection NAME the models pin
        // (Fleetbase models set $connection = 'mysql'): connection names get
        // their own PDO handles, and a DB::transaction() on the default
        // connection would not cover model writes made on another handle
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', $sqlite);
        $app['config']->set('database.connections.testing', $sqlite);

        $permissionPath = InstalledVersions::getInstallPath('spatie/laravel-permission');
        $app['config']->set('permission', require $permissionPath . '/config/permission.php');

        // model saves flush the response cache and log activity; keep both inert in tests
        $app['config']->set('responsecache.enabled', false);
        $app['config']->set('activitylog.enabled', false);
    }

    /**
     * The full PalletServiceProvider cannot boot on the SQLite lane, so its
     * loadRoutesFrom() never runs and no Pallet route is registered. Load the route
     * file directly instead — the consumable API's prefix and middleware are part of
     * its contract, and without this there is nothing to assert them against.
     */
    protected function defineRoutes($router): void
    {
        // routes.php calls the fleetbaseRoutes() macro, which arrives as a core
        // expansion — register those first or the require fails on the first call.
        self::registerCoreExpansions();

        require __DIR__ . '/../src/routes.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // the full PalletServiceProvider pulls in the Core/FleetOps providers,
        // which the SQLite lane cannot boot; register the commands it declares
        // directly so console behavior is still exercised
        $kernel = $this->app->make(ConsoleKernel::class);

        foreach ((new PalletServiceProvider($this->app))->commands as $command) {
            $kernel->registerCommand($this->app->make($command));
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        self::registerCoreExpansions();
        $this->mapSpatialTypesForSqlite();
        $this->createCoreTableShims();

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
    }

    /**
     * Several Pallet tables carry MySQL spatial columns (the layout `area`
     * polygons). Doctrine has no SQLite mapping for them, so any schema
     * change touching those tables blows up during introspection; treat them
     * as text for the SQLite lane.
     */
    private function mapSpatialTypesForSqlite(): void
    {
        $platform = DB::connection()->getDoctrineConnection()->getDatabasePlatform();

        foreach (['polygon', 'point', 'geometry', 'linestring', 'multipolygon'] as $spatialType) {
            if (!$platform->hasDoctrineTypeMappingFor($spatialType)) {
                $platform->registerDoctrineTypeMapping($spatialType, 'text');
            }
        }

        // Writing a Place emits ST_GeomFromText(...) as raw SQL. SQLite has no such
        // function, so any code path that saves a location died with "no such
        // function" rather than being testable at all. Stub the spatial functions
        // to store and return the WKT text — enough to assert that a row was or was
        // not written, which is what these tests are about. Anything that depends on
        // real geometry semantics belongs in the MySQL lane.
        $pdo = DB::connection()->getPdo();

        if (method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('ST_GeomFromText', fn ($wkt = null) => $wkt, -1);
            $pdo->sqliteCreateFunction('ST_AsText', fn ($geom = null) => $geom, -1);
        }
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
            // Pallet writes a Place when a warehouse is given an address, so the shim
            // needs the columns it actually writes — with only name/uuid present the
            // insert failed and the warehouse create silently produced nothing.
            'places' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('created_by_uuid', 191)->nullable()->index();
                $table->string('_key', 191)->nullable();
                $table->string('name')->nullable();
                $table->string('type')->nullable();
                $table->string('street1')->nullable();
                $table->string('street2')->nullable();
                $table->string('city')->nullable();
                $table->string('province')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('country')->nullable();
                $table->string('neighborhood')->nullable();
                $table->string('district')->nullable();
                $table->string('building')->nullable();
                $table->text('location')->nullable();
                $table->boolean('is_3pl')->default(false);
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
                $table->string('user_uuid', 191)->nullable()->index();
                $table->string('photo_uuid', 191)->nullable();
                $table->string('internal_id', 191)->nullable();
                $table->string('_key', 191)->nullable();
                $table->string('name')->nullable();
                $table->string('title')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('type', 191)->nullable();
                $table->string('slug')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'vendors' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('public_id', 191)->nullable()->index();
                $table->string('internal_id', 191)->nullable()->index();
                $table->string('_key', 191)->nullable()->index();
                $table->string('company_uuid', 191)->nullable()->index();
                $table->string('connect_company_uuid', 191)->nullable();
                $table->string('place_uuid', 191)->nullable();
                $table->string('type_uuid', 191)->nullable();
                $table->string('logo_uuid', 191)->nullable();
                $table->string('name')->nullable();
                $table->string('slug', 191)->nullable();
                $table->string('email', 191)->nullable();
                $table->string('phone', 191)->nullable();
                $table->string('website_url', 191)->nullable();
                $table->string('country', 191)->nullable();
                $table->string('business_id', 191)->nullable();
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
            // Authorization directives are consulted on every listing via
            // HasApiModelBehavior::applyDirectivesToQuery(). Pallet defines none, but
            // the query still runs, so the table has to exist for a listing to be
            // testable end to end.
            'directives' => function ($table) {
                $table->increments('id');
                $table->string('uuid', 191)->nullable()->index();
                $table->string('permission_uuid', 191)->nullable()->index();
                $table->string('subject_uuid', 191)->nullable()->index();
                $table->string('subject_type', 191)->nullable();
                $table->string('key')->nullable();
                $table->text('value')->nullable();
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
