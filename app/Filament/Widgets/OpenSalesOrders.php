<?php

namespace App\Filament\Widgets;

use App\Models\SalesOrder;
use App\Models\Team;
use App\Services\ProductDashboard;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Collection;

class OpenSalesOrders extends TableWidget
{
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Sales orders to ship'))
            ->description(fn (): string => trans_choice(':count open order|:count open orders', $this->openOrders()['count']))
            ->records(fn (): array => $this->openOrders()['orders']
                ->mapWithKeys(fn (SalesOrder $order) => [$order->id => [
                    'order_no' => $order->order_no,
                    'partner' => $order->customer->name,
                    'order_date' => $order->order_date->toDateString(),
                    'status' => $order->status->value,
                ]])
                ->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('order_no')
                    ->label('Order no'),
                TextColumn::make('partner')
                    ->label('Customer'),
                TextColumn::make('order_date')
                    ->label('Order date'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("status.{$state}")),
            ]);
    }

    /**
     * @return array{count: int, orders: Collection<int, SalesOrder>}
     */
    protected function openOrders(): array
    {
        /** @var Team $team */
        $team = Filament::getTenant();

        return once(fn () => app(ProductDashboard::class)->openSalesOrders($team));
    }
}
