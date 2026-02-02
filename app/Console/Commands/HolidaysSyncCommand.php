<?php

namespace App\Console\Commands;

use App\Services\HolidayCacheService;
use Illuminate\Console\Command;

class HolidaysSyncCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'holidays:sync {year? : The year to sync (defaults to current and next year)}';

    /**
     * @var string
     */
    protected $description = 'Sync holidays from New Taipei City Open Data API';

    public function handle(HolidayCacheService $cacheService): int
    {
        $year = $this->argument('year');

        if ($year) {
            $this->syncYear((int) $year, $cacheService);
        } else {
            $currentYear = (int) now()->year;
            $this->syncYear($currentYear, $cacheService);
            $this->syncYear($currentYear + 1, $cacheService);
        }

        return self::SUCCESS;
    }

    private function syncYear(int $year, HolidayCacheService $cacheService): void
    {
        $this->info("Syncing holidays for year {$year}...");

        $cacheService->refreshYear($year);

        $this->info("Holidays for {$year} synced successfully.");
    }
}
