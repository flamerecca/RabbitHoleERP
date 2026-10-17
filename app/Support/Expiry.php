<?php

namespace App\Support;

use App\Enums\ExpiryStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class Expiry
{
    /**
     * Get the expiry status of a date: expired the day after it, near within the warning days, otherwise ok.
     *
     * The expiry date itself is still usable, and the time of day never matters.
     */
    public static function statusOf(?CarbonInterface $expiryDate, int $warningDays, ?CarbonInterface $today = null): ExpiryStatus
    {
        if ($expiryDate === null) {
            return ExpiryStatus::Ok;
        }

        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $expiry = CarbonImmutable::instance($expiryDate)->startOfDay();

        if ($expiry->lt($today)) {
            return ExpiryStatus::Expired;
        }

        return $expiry->lte($today->addDays($warningDays)) ? ExpiryStatus::Near : ExpiryStatus::Ok;
    }

    /**
     * Get the whole days between today and the expiry date, negative once the date has passed.
     */
    public static function daysLeft(CarbonInterface $expiryDate, ?CarbonInterface $today = null): int
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return (int) $today->diffInDays(CarbonImmutable::instance($expiryDate)->startOfDay(), false);
    }

    /**
     * Narrow a query to the rows of an expiry status, the single place the status boundaries live in SQL.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function applyStatusFilter(Builder $query, ExpiryStatus $status, int $warningDays, string $column = 'expiry_date'): Builder
    {
        $today = CarbonImmutable::today();
        $limit = $today->addDays($warningDays)->toDateString();

        return match ($status) {
            ExpiryStatus::Expired => $query->whereNotNull($column)->whereDate($column, '<', $today->toDateString()),
            ExpiryStatus::Near => $query->whereDate($column, '>=', $today->toDateString())->whereDate($column, '<=', $limit),
            ExpiryStatus::Ok => $query->where(fn (Builder $inner) => $inner->whereNull($column)->orWhereDate($column, '>', $limit)),
        };
    }
}
