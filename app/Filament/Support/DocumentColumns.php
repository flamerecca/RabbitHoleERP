<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Tables\Columns\TextColumn;

class DocumentColumns
{
    /**
     * Make a translated status badge column.
     */
    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->formatStateUsing(fn (BackedEnum $state): string => __("status.{$state->value}"))
            ->color(fn (BackedEnum $state): string => match ($state->value) {
                'draft' => 'gray',
                'cancelled' => 'danger',
                'confirmed', 'holding' => 'info',
                'partially_received', 'partially_shipped', 'overdue' => 'warning',
                default => 'success',
            });
    }
}
