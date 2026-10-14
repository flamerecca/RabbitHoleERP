<?php

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductDashboard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * @return array{active_products: int, stock_value: float, below_reorder_point: int, currency: string|null}
     */
    #[Computed]
    public function overview(): array
    {
        return app(ProductDashboard::class)->overview(Auth::user()->currentTeam);
    }
};
?>

<div class="grid auto-rows-min gap-4 md:grid-cols-3">
    <flux:card>
        <flux:text>{{ __('Active products') }}</flux:text>
        <flux:heading size="xl" data-test="active-products">{{ number_format($this->overview['active_products']) }}</flux:heading>
    </flux:card>

    <flux:card>
        <flux:text>{{ __('Stock value') }}</flux:text>
        <flux:heading size="xl" data-test="stock-value">
            {{ $this->overview['currency'] }} {{ number_format($this->overview['stock_value'], 2) }}
        </flux:heading>
    </flux:card>

    <flux:card>
        <flux:text>{{ __('Products below reorder point') }}</flux:text>
        <flux:heading size="xl" data-test="below-reorder-point">{{ number_format($this->overview['below_reorder_point']) }}</flux:heading>
        <flux:link class="text-sm" :href="ProductResource::getUrl('index', panel: 'admin', tenant: Auth::user()->currentTeam)">{{ __('Manage products') }}</flux:link>
    </flux:card>
</div>
