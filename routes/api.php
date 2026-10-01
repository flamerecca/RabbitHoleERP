<?php

use App\Enums\DocumentType;
use App\Enums\TeamRole;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ConsignmentHoldController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentRuleController;
use App\Http\Controllers\DocumentSequenceController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventorySettingController;
use App\Http\Controllers\MaterialRequisitionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockLotController;
use App\Http\Controllers\StockTakeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseTransferController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/{team}')
    ->middleware(['auth:sanctum', EnsureTeamMembership::class])
    ->scopeBindings()
    ->name('api.v1.')
    ->group(function () {
        Route::controller(ProductController::class)->group(function () {
            Route::get('unit-categories', 'listUnitCategories')->name('unit-categories.index');
            Route::post('unit-categories', 'storeUnitCategory')->name('unit-categories.store');
            Route::patch('unit-categories/{unitCategory}', 'updateUnitCategory')->name('unit-categories.update');
            Route::delete('unit-categories/{unitCategory}', 'destroyUnitCategory')->name('unit-categories.destroy');

            Route::get('units', 'listUnits')->name('units.index');
            Route::post('units', 'storeUnit')->name('units.store');
            Route::get('units/{unit}', 'showUnit')->name('units.show');
            Route::patch('units/{unit}', 'updateUnit')->name('units.update');
            Route::delete('units/{unit}', 'destroyUnit')->name('units.destroy');

            Route::get('product-categories', 'listProductCategories')->name('product-categories.index');
            Route::post('product-categories', 'storeProductCategory')->name('product-categories.store');
            Route::get('product-categories/{productCategory}', 'showProductCategory')->name('product-categories.show');
            Route::patch('product-categories/{productCategory}', 'updateProductCategory')->name('product-categories.update');
            Route::delete('product-categories/{productCategory}', 'destroyProductCategory')->name('product-categories.destroy');

            Route::get('products', 'listProducts')->name('products.index');
            Route::post('products', 'storeProduct')->name('products.store');
            Route::get('products/replenishment', 'listReplenishment')->name('products.replenishment.index');
            Route::post('products/replenishment/purchase-orders', 'storeReplenishmentPurchaseOrders')->name('products.replenishment.purchase-orders.store');
        });

        Route::middleware(EnsureTeamMembership::class.':'.TeamRole::Admin->value)->group(function () {
            Route::put('inventory-settings', [InventorySettingController::class, 'update'])->name('inventory-settings.update');
            Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
            Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
            Route::get('document-sequences', [DocumentSequenceController::class, 'index'])->name('document-sequences.index');
            Route::put('document-sequences/{documentType}', [DocumentSequenceController::class, 'update'])
                ->whereIn('documentType', array_column(DocumentType::cases(), 'value'))
                ->name('document-sequences.update');

            Route::apiResource('document-rules', DocumentRuleController::class)->except('show')->parameters(['document-rules' => 'documentRule']);
        });

        Route::get('inventory-settings', [InventorySettingController::class, 'show'])->name('inventory-settings.show');

        Route::apiResource('warehouses', WarehouseController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('currencies', CurrencyController::class);
        Route::apiResource('tax-rates', TaxRateController::class)->parameters(['tax-rates' => 'taxRate']);

        Route::apiResource('purchase-orders', PurchaseOrderController::class)->except('destroy')->parameters(['purchase-orders' => 'purchaseOrder']);
        Route::post('purchase-orders/{purchaseOrder}/items', [PurchaseOrderController::class, 'addItem'])->name('purchase-orders.items.store');
        Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])->name('purchase-orders.confirm');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

        Route::apiResource('goods-receipts', GoodsReceiptController::class)->except('destroy')->parameters(['goods-receipts' => 'goodsReceipt']);
        Route::post('goods-receipts/{goodsReceipt}/confirm', [GoodsReceiptController::class, 'confirm'])->name('goods-receipts.confirm');
        Route::post('goods-receipts/{goodsReceipt}/cancel', [GoodsReceiptController::class, 'cancel'])->name('goods-receipts.cancel');

        Route::apiResource('sales-orders', SalesOrderController::class)->except('destroy')->parameters(['sales-orders' => 'salesOrder']);
        Route::post('sales-orders/{salesOrder}/items', [SalesOrderController::class, 'addItem'])->name('sales-orders.items.store');
        Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm');
        Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');
        Route::post('sales-orders/{salesOrder}/reserve', [SalesOrderController::class, 'reserve'])->name('sales-orders.reserve');
        Route::post('sales-orders/{salesOrder}/unreserve', [SalesOrderController::class, 'unreserve'])->name('sales-orders.unreserve');

        Route::apiResource('shipments', ShipmentController::class)->except('destroy')->parameters(['shipments' => 'shipment']);
        Route::post('shipments/{shipment}/confirm', [ShipmentController::class, 'confirm'])->name('shipments.confirm');
        Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');

        Route::apiResource('purchase-returns', PurchaseReturnController::class)->except('destroy')->parameters(['purchase-returns' => 'purchaseReturn']);
        Route::post('purchase-returns/{purchaseReturn}/confirm', [PurchaseReturnController::class, 'confirm'])->name('purchase-returns.confirm');
        Route::post('purchase-returns/{purchaseReturn}/cancel', [PurchaseReturnController::class, 'cancel'])->name('purchase-returns.cancel');

        Route::apiResource('sales-returns', SalesReturnController::class)->except('destroy')->parameters(['sales-returns' => 'salesReturn']);
        Route::post('sales-returns/{salesReturn}/confirm', [SalesReturnController::class, 'confirm'])->name('sales-returns.confirm');
        Route::post('sales-returns/{salesReturn}/cancel', [SalesReturnController::class, 'cancel'])->name('sales-returns.cancel');

        Route::apiResource('consignment-holds', ConsignmentHoldController::class)->only(['index', 'store', 'show'])->parameters(['consignment-holds' => 'consignmentHold']);
        Route::post('consignment-holds/{consignmentHold}/pickup', [ConsignmentHoldController::class, 'pickup'])->name('consignment-holds.pickup');

        Route::apiResource('warehouse-transfers', WarehouseTransferController::class)->except('destroy')->parameters(['warehouse-transfers' => 'warehouseTransfer']);
        Route::post('warehouse-transfers/{warehouseTransfer}/confirm', [WarehouseTransferController::class, 'confirm'])->name('warehouse-transfers.confirm');
        Route::post('warehouse-transfers/{warehouseTransfer}/cancel', [WarehouseTransferController::class, 'cancel'])->name('warehouse-transfers.cancel');

        Route::apiResource('stock-takes', StockTakeController::class)->except('destroy')->parameters(['stock-takes' => 'stockTake']);
        Route::post('stock-takes/{stockTake}/confirm', [StockTakeController::class, 'confirm'])->name('stock-takes.confirm');
        Route::post('stock-takes/{stockTake}/cancel', [StockTakeController::class, 'cancel'])->name('stock-takes.cancel');

        Route::apiResource('material-requisitions', MaterialRequisitionController::class)->except('destroy')->parameters(['material-requisitions' => 'materialRequisition']);
        Route::post('material-requisitions/{materialRequisition}/confirm', [MaterialRequisitionController::class, 'confirm'])->name('material-requisitions.confirm');
        Route::post('material-requisitions/{materialRequisition}/cancel', [MaterialRequisitionController::class, 'cancel'])->name('material-requisitions.cancel');

        Route::get('stock-balances', [StockController::class, 'balances'])->name('stock-balances.index');
        Route::get('stock-balances/{stockBalance}', [StockController::class, 'balance'])->name('stock-balances.show');
        Route::get('stock-lots', [StockLotController::class, 'index'])->name('stock-lots.index');
        Route::get('stock-lots/{stockLot}', [StockLotController::class, 'show'])->name('stock-lots.show');
        Route::get('stock-movements', [StockController::class, 'movements'])->name('stock-movements.index');
        Route::get('stock-movements/{stockMovement}', [StockController::class, 'movement'])->name('stock-movements.show');

        Route::controller(ProductController::class)
            ->prefix('products/{product}')
            ->whereNumber('product')
            ->name('products.')
            ->group(function () {
                Route::get('/', 'showProduct')->name('show');
                Route::patch('/', 'updateProduct')->name('update');
                Route::delete('/', 'destroyProduct')->name('destroy');
                Route::get('stock', 'showStock')->name('stock');
                Route::get('goods-receipts', 'listGoodsReceipts')->name('goods-receipts');
                Route::get('shipments', 'listShipments')->name('shipments');
                Route::get('stock-movements', 'listStockMovements')->name('stock-movements');
                Route::get('returns', 'listReturns')->name('returns');

                Route::get('suppliers', 'listSuppliers')->name('suppliers.index');
                Route::post('suppliers', 'storeSupplier')->name('suppliers.store');
                Route::patch('suppliers/{productSupplier}', 'updateSupplier')->name('suppliers.update');
                Route::delete('suppliers/{productSupplier}', 'destroySupplier')->name('suppliers.destroy');
                Route::get('purchase-price', 'showPurchasePrice')->name('purchase-price');

                Route::get('reordering-rules', 'listReorderingRules')->name('reordering-rules.index');
                Route::post('reordering-rules', 'storeReorderingRule')->name('reordering-rules.store');
                Route::patch('reordering-rules/{reorderingRule}', 'updateReorderingRule')->name('reordering-rules.update');
                Route::delete('reordering-rules/{reorderingRule}', 'destroyReorderingRule')->name('reordering-rules.destroy');
            });
    });
