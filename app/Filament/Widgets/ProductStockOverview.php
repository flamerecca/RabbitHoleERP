<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Team;
use App\Services\ProductDashboard;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStockOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        /** @var Team $team */
        $team = Filament::getTenant();
        $overview = app(ProductDashboard::class)->overview($team);

        return [
            Stat::make(__('Active products'), number_format($overview['active_products'])),
            Stat::make(__('Stock value'), trim(($overview['currency'] ?? '').' '.number_format($overview['stock_value'], 2))),
            Stat::make(__('Products below reorder point'), number_format($overview['below_reorder_point']))
                ->color($overview['below_reorder_point'] > 0 ? 'danger' : 'success')
                ->description(__('Manage products'))
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
