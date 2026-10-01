<?php

use App\Services\ProductDashboard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * @return array{count: int, orders: \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder>}
     */
    #[Computed]
    public function purchaseOrders(): array
    {
        return app(ProductDashboard::class)->openPurchaseOrders(Auth::user()->currentTeam);
    }

    /**
     * @return array{count: int, orders: \Illuminate\Database\Eloquent\Collection<int, \App\Models\SalesOrder>}
     */
    #[Computed]
    public function salesOrders(): array
    {
        return app(ProductDashboard::class)->openSalesOrders(Auth::user()->currentTeam);
    }
};
?>

<div class="grid gap-4 md:grid-cols-2">
    <flux:card>
        <flux:heading>{{ __('Purchase orders to receive') }}</flux:heading>
        <flux:text data-test="purchase-orders-count">{{ trans_choice(':count open order|:count open orders', $this->purchaseOrders['count']) }}</flux:text>

        <div class="mt-2 overflow-x-auto">
        <flux:table>
            <flux:table.rows>
                @foreach ($this->purchaseOrders['orders'] as $order)
                    <flux:table.row wire:key="purchase-order-{{ $order->id }}">
                        <flux:table.cell>{{ $order->order_no }}</flux:table.cell>
                        <flux:table.cell>{{ $order->supplier->name }}</flux:table.cell>
                        <flux:table.cell>{{ $order->order_date->toDateString() }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ $order->status->value }}</flux:badge></flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading>{{ __('Sales orders to ship') }}</flux:heading>
        <flux:text data-test="sales-orders-count">{{ trans_choice(':count open order|:count open orders', $this->salesOrders['count']) }}</flux:text>

        <div class="mt-2 overflow-x-auto">
        <flux:table>
            <flux:table.rows>
                @foreach ($this->salesOrders['orders'] as $order)
                    <flux:table.row wire:key="sales-order-{{ $order->id }}">
                        <flux:table.cell>{{ $order->order_no }}</flux:table.cell>
                        <flux:table.cell>{{ $order->customer->name }}</flux:table.cell>
                        <flux:table.cell>{{ $order->order_date->toDateString() }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ $order->status->value }}</flux:badge></flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        </div>
    </flux:card>
</div>
