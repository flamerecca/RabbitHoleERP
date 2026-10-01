<?php

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductDashboard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * @return array{count: int, items: list<array{id: int, product_id: int, sku: string, product: string, warehouse: string, forecasted: float|int, suggested: float, supplier: string|null}>}
     */
    #[Computed]
    public function suggestions(): array
    {
        $items = app(ProductDashboard::class)->replenishmentSuggestions(Auth::user()->currentTeam);

        return ['count' => count($items), 'items' => array_slice($items, 0, 5)];
    }
};
?>

<flux:card>
    <flux:heading>{{ __('Replenishment suggestions') }}</flux:heading>
    <flux:text data-test="replenishment-count">{{ trans_choice(':count item to replenish|:count items to replenish', $this->suggestions['count']) }}</flux:text>

    @if ($this->suggestions['count'] === 0)
        <flux:text class="mt-2">{{ __('No products need to be replenished.') }}</flux:text>
    @else
        <div class="mt-2 overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Product') }}</flux:table.column>
                <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Forecasted') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Suggested') }}</flux:table.column>
                <flux:table.column>{{ __('Supplier') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->suggestions['items'] as $item)
                    <flux:table.row wire:key="replenishment-{{ $item['id'] }}">
                        <flux:table.cell>
                            <flux:link :href="ProductResource::getUrl('edit', ['record' => $item['product_id']], panel: 'admin', tenant: Auth::user()->currentTeam)">{{ $item['sku'] }} {{ $item['product'] }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $item['warehouse'] }}</flux:table.cell>
                        <flux:table.cell align="end">{{ rtrim(rtrim(number_format($item['forecasted'], 4), '0'), '.') }}</flux:table.cell>
                        <flux:table.cell align="end">{{ rtrim(rtrim(number_format($item['suggested'], 4), '0'), '.') }}</flux:table.cell>
                        <flux:table.cell>{{ $item['supplier'] ?? __('No supplier') }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        </div>
    @endif
</flux:card>
