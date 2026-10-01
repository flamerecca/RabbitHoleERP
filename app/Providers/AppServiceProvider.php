<?php

namespace App\Providers;

use App\Models\ConsignmentHold;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\MaterialRequisition;
use App\Models\MaterialRequisitionItem;
use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\ReorderingRule;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use App\Services\NativeCosting;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NativeCosting::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
        $this->configurePaginatedResources();
        $this->configureDevServer();
    }

    /**
     * Serve the application with FFI enabled during `composer run dev`.
     *
     * The default `ffi.enable=preload` only allows FFI on the command line, so under `php artisan serve`
     * every request that records a stock movement fails when NativeCosting loads the native library.
     * `php artisan serve` cannot pass ini settings to its built-in server, so the server is started directly.
     */
    protected function configureDevServer(): void
    {
        DevCommands::register(sprintf(
            'cd public && %s -d ffi.enable=1 -S 127.0.0.1:8000 %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')),
        ), 'server');
    }

    /**
     * Register the stable aliases stored in polymorphic type columns, such as stock_movements.reference_type.
     */
    protected function configureMorphMap(): void
    {
        Relation::morphMap([
            'product' => Product::class,
            'product_supplier' => ProductSupplier::class,
            'reordering_rule' => ReorderingRule::class,
            'purchase_order' => PurchaseOrder::class,
            'purchase_order_item' => PurchaseOrderItem::class,
            'sales_order' => SalesOrder::class,
            'sales_order_item' => SalesOrderItem::class,
            'consignment_hold' => ConsignmentHold::class,
            'goods_receipt' => GoodsReceipt::class,
            'goods_receipt_item' => GoodsReceiptItem::class,
            'shipment' => Shipment::class,
            'shipment_item' => ShipmentItem::class,
            'purchase_return' => PurchaseReturn::class,
            'purchase_return_item' => PurchaseReturnItem::class,
            'sales_return' => SalesReturn::class,
            'sales_return_item' => SalesReturnItem::class,
            'warehouse_transfer' => WarehouseTransfer::class,
            'warehouse_transfer_item' => WarehouseTransferItem::class,
            'stock_take' => StockTake::class,
            'stock_take_item' => StockTakeItem::class,
            'material_requisition' => MaterialRequisition::class,
            'material_requisition_item' => MaterialRequisitionItem::class,
        ]);
    }

    /**
     * Limit the pagination meta of API resource collections to the fields declared by the API contract.
     */
    protected function configurePaginatedResources(): void
    {
        JsonResource::macro('paginationInformation', function (Request $request, array $paginated, array $default): array {
            $default['meta'] = Arr::only($default['meta'], ['current_page', 'from', 'last_page', 'per_page', 'to', 'total']);

            return $default;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
