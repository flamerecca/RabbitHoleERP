<?php

namespace App\Console\Commands;

use App\Enums\ConsignmentHoldStatus;
use App\Mail\ConsignmentHoldReminder;
use App\Models\ConsignmentHold;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

#[Signature('consignment:send-reminders')]
#[Description('Mark expired consignment holds overdue and remind the warehouse managers of holds held for ten days')]
class SendConsignmentHoldReminders extends Command
{
    /**
     * The number of days a hold is kept before the warehouse manager is reminded every day.
     */
    public const REMINDER_AFTER_DAYS = 10;

    /**
     * Mark the expired holds overdue first, then remind the managers of the holds that are still waiting.
     *
     * Holds are changed one by one because the activity log listens to model events. One mail lists all due holds
     * of a warehouse, and what happens to an overdue hold afterwards is an open question, see docs/architecture.md
     * section 5.
     */
    public function handle(): int
    {
        $today = CarbonImmutable::today();

        $overdue = $this->markExpiredHoldsOverdue($today);
        [$sent, $skipped] = $this->remindWarehouseManagers($today);

        $this->info("{$overdue} consignment holds marked overdue.");
        $this->info("{$sent} reminder mail sent.");

        if ($skipped > 0) {
            $this->warn("{$skipped} warehouse skipped without a manager email.");
        }

        return self::SUCCESS;
    }

    /**
     * Mark the holding holds whose last day has passed overdue and return how many there were.
     */
    protected function markExpiredHoldsOverdue(CarbonImmutable $today): int
    {
        $count = 0;

        ConsignmentHold::query()
            ->where('status', ConsignmentHoldStatus::Holding->value)
            ->whereDate('held_until', '<', $today->toDateString())
            ->whereHas('team')
            ->chunkById(100, function (Collection $holds) use (&$count): void {
                foreach ($holds as $hold) {
                    $hold->update(['status' => ConsignmentHoldStatus::Overdue]);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Send one mail per warehouse that lists its holds due for a reminder, and return the mails sent and the
     * warehouses skipped for lack of a manager email.
     *
     * @return array{0: int, 1: int}
     */
    protected function remindWarehouseManagers(CarbonImmutable $today): array
    {
        $dueHolds = ConsignmentHold::query()
            ->with(['warehouse', 'shipment.salesOrder.customer'])
            ->where('status', ConsignmentHoldStatus::Holding->value)
            ->whereDate('held_from', '<=', $today->subDays(self::REMINDER_AFTER_DAYS)->toDateString())
            ->whereDate('held_until', '>=', $today->toDateString())
            ->whereHas('team')
            ->orderBy('warehouse_id')
            ->orderBy('id')
            ->get()
            ->groupBy('warehouse_id');

        $sent = 0;
        $skipped = 0;

        foreach ($dueHolds as $holds) {
            $warehouse = $holds->first()->warehouse;

            if (blank($warehouse->manager_email)) {
                $skipped++;

                continue;
            }

            Mail::to($warehouse->manager_email)->send(new ConsignmentHoldReminder($warehouse, $holds->values()));
            $sent++;
        }

        return [$sent, $skipped];
    }
}
