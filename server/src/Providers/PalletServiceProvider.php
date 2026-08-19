<?php

namespace Fleetbase\Pallet\Providers;

use Fleetbase\FleetOps\Providers\FleetOpsServiceProvider;
use Fleetbase\Providers\CoreServiceProvider;

if (!class_exists(CoreServiceProvider::class)) {
    throw new \Exception('Pallet cannot be loaded without `fleetbase/core-api` installed!');
}

if (!class_exists(FleetOpsServiceProvider::class)) {
    throw new \Exception('Pallet cannot be loaded without `fleetbase/fleetops-api` installed!');
}

/**
 * Pallet WMS extension service provider.
 */
class PalletServiceProvider extends CoreServiceProvider
{
    /**
     * The observers registered with the service provider.
     *
     * @var array
     */
    public $observers = [];

    /**
     * The console commands registered with the service provider.
     *
     * @var array
     */
    public $commands = [
        \Fleetbase\Pallet\Console\Commands\ReleaseExpiredReservations::class,
    ];

    /**
     * Register any application services.
     *
     * Within the register method, you should only bind things into the
     * service container. You should never attempt to register any event
     * listeners, routes, or any other piece of functionality within the
     * register method.
     *
     * More information on this can be found in the Laravel documentation:
     * https://laravel.com/docs/8.x/providers
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(CoreServiceProvider::class);
        $this->app->register(FleetOpsServiceProvider::class);
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     *
     * @throws \Exception if the `fleetbase/core-api` package is not installed
     */
    public function boot()
    {
        $this->registerCommands();
        $this->scheduleCommands(function ($schedule) {
            // abandoned checkouts otherwise hold reserved stock forever
            $schedule->command('pallet:release-expired-reservations')->everyFiveMinutes()->storeOutputInDb();
        });
        $this->registerObservers();
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }
}
