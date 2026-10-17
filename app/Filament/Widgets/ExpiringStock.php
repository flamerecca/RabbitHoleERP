<?php

namespace App\Filament\Widgets;

use App\Models\Team;
use App\Services\ProductDashboard;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringStock extends TableWidget
{
    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Expiring stock'))
            ->emptyStateHeading(__('Nothing is about to expire.'))
            ->records(fn (): array => collect($this->rows())
                ->mapWithKeys(fn (array $row, int $index) => [$index => $row])
                ->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('label')
                    ->label(__('Lot or SKU')),
                TextColumn::make('product')
                    ->label(__('Product')),
                TextColumn::make('quantity')
                    ->label(__('On hand'))
                    ->numeric(),
                TextColumn::make('expiry_date')
                    ->label(__('Expiry date')),
                TextColumn::make('days_left')
                    ->label(__('Days left')),
                TextColumn::make('status')
                    ->label(__('Expiry status'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'expired' ? 'danger' : 'warning')
                    ->formatStateUsing(fn (string $state): string => __("expiry.{$state}")),
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function rows(): array
    {
        /** @var Team $team */
        $team = Filament::getTenant();

        return once(fn () => app(ProductDashboard::class)->expiringStock($team));
    }
}
