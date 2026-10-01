<?php

use App\Filament\Resources\Products\ProductResource;
use App\Models\StockMovement;
use App\Services\ProductDashboard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, StockMovement>
     */
    #[Computed]
    public function movements(): \Illuminate\Database\Eloquent\Collection
    {
        return app(ProductDashboard::class)->recentMovements(Auth::user()->currentTeam);
    }

    /**
     * Get the number of the document the movement came from, or its note for manual corrections.
     */
    public function source(StockMovement $movement): ?string
    {
        return app(ProductDashboard::class)->movementSource($movement);
    }
};
?>

<flux:card>
    <flux:heading>{{ __('Recent stock movements') }}</flux:heading>

    @if ($this->movements->isEmpty())
        <flux:text class="mt-2">{{ __('No stock movements yet.') }}</flux:text>
    @else
        <div class="mt-2 overflow-x-auto">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Time') }}</flux:table.column>
                <flux:table.column>{{ __('Product') }}</flux:table.column>
                <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Quantity') }}</flux:table.column>
                <flux:table.column>{{ __('Source') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->movements as $movement)
                    <flux:table.row wire:key="movement-{{ $movement->id }}">
                        <flux:table.cell>{{ $movement->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="ProductResource::getUrl('edit', ['record' => $movement->product_id], panel: 'admin', tenant: Auth::user()->currentTeam)">{{ $movement->product->sku }} {{ $movement->product->name }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $movement->warehouse->code }}</flux:table.cell>
                        <flux:table.cell>{{ $movement->type->value }}</flux:table.cell>
                        <flux:table.cell align="end">{{ rtrim(rtrim(number_format((float) $movement->quantity, 4), '0'), '.') }}</flux:table.cell>
                        <flux:table.cell>{{ $this->source($movement) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        </div>
    @endif
</flux:card>
