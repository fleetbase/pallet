<?php

namespace Fleetbase\Pallet\Console\Commands;

use Fleetbase\Pallet\Models\InventoryReservation;
use Illuminate\Console\Command;

class ReleaseExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pallet:release-expired-reservations {--limit=500 : Maximum reservations to release in one run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release stock held by active inventory reservations that have passed their expiry';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit    = max(1, (int) $this->option('limit'));
        $released = 0;
        $failed   = 0;

        InventoryReservation::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at')
            ->limit($limit)
            ->get()
            ->each(function (InventoryReservation $reservation) use (&$released, &$failed) {
                // release() re-reads the row under lock and returns false when a
                // concurrent release/fulfill already transitioned it
                if ($reservation->release()) {
                    $released++;

                    return;
                }

                $failed++;
            });

        $this->info("Released {$released} expired inventory reservations.");

        if ($failed > 0) {
            $this->warn("{$failed} expired reservations could not be released and were left untouched.");
        }

        return Command::SUCCESS;
    }
}
