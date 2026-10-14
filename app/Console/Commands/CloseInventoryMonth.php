<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

#[Signature('inventory:close-month {--period= : The month to close as YYYY-MM, the previous month by default}')]
#[Description('Close the inventory of every team for a month with the close_inventory_month procedure')]
class CloseInventoryMonth extends Command
{
    /**
     * Close the month for every team, carrying on when a team fails.
     *
     * The closing rules live in the database on purpose, see docs/architecture.md section 4.11.
     */
    public function handle(): int
    {
        $period = (string) ($this->option('period') ?? now()->subMonthNoOverflow()->format('Y-m'));

        foreach (Team::query()->orderBy('id')->get() as $team) {
            try {
                DB::transaction(fn () => DB::statement('CALL close_inventory_month(?, ?, ?)', [$team->id, $period, null]));
                $this->info("Closed {$period} for team {$team->slug}.");
            } catch (QueryException $exception) {
                preg_match('/RH\d{3}: (.+?)(?:\n|\s+CONTEXT:|\s+\(Connection:|$)/', $exception->getMessage(), $matches);
                $this->warn("Could not close {$period} for team {$team->slug}: ".($matches[1] ?? $exception->getMessage()));
                report($exception);
            }
        }

        return self::SUCCESS;
    }
}
