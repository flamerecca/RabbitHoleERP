<?php

use App\Enums\DocumentType;
use App\Enums\PermissionProfile;
use App\Enums\TeamPermission;
use App\Http\Controllers\AccountingSettingController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AgingReportController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ConsignmentHoldController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentRuleController;
use App\Http\Controllers\DocumentSequenceController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventoryClosingController;
use App\Http\Controllers\InventorySettingController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\MaterialRequisitionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\QuotationTemplateController;
use App\Http\Controllers\SalesMarginReportController;
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
use App\Http\Middleware\EnsureTeamPermission;
use Illuminate\Support\Facades\Route;

/**
 * The team scoped API routes, registered once for every API version.
 *
 * The profile decides which role to permission mapping the permission middleware checks, see
 * docs/specs/team-permission-v2-design.md section 5.
 */
return function (PermissionProfile $profile): void {
    $can = fn (TeamPermission ...$permissions): string => EnsureTeamPermission::class.':'.$profile->value.','
        .implode(',', array_map(fn (TeamPermission $permission) => $permission->value, $permissions));

    Route::controller(ProductController::class)->group(function () use ($can) {
        Route::get('unit-categories', 'listUnitCategories')->name('unit-categories.index')->middleware($can(TeamPermission::ViewUnitCategory));
        Route::post('unit-categories', 'storeUnitCategory')->name('unit-categories.store')->middleware($can(TeamPermission::CreateUnitCategory));
        Route::patch('unit-categories/{unitCategory}', 'updateUnitCategory')->name('unit-categories.update')->middleware($can(TeamPermission::UpdateUnitCategory));
        Route::delete('unit-categories/{unitCategory}', 'destroyUnitCategory')->name('unit-categories.destroy')->middleware($can(TeamPermission::DeleteUnitCategory));

        Route::get('units', 'listUnits')->name('units.index')->middleware($can(TeamPermission::ViewUnit));
        Route::post('units', 'storeUnit')->name('units.store')->middleware($can(TeamPermission::CreateUnit));
        Route::get('units/{unit}', 'showUnit')->name('units.show')->middleware($can(TeamPermission::ViewUnit));
        Route::patch('units/{unit}', 'updateUnit')->name('units.update')->middleware($can(TeamPermission::UpdateUnit));
        Route::delete('units/{unit}', 'destroyUnit')->name('units.destroy')->middleware($can(TeamPermission::DeleteUnit));

        Route::get('product-categories', 'listProductCategories')->name('product-categories.index')->middleware($can(TeamPermission::ViewProductCategory));
        Route::post('product-categories', 'storeProductCategory')->name('product-categories.store')->middleware($can(TeamPermission::CreateProductCategory));
        Route::get('product-categories/{productCategory}', 'showProductCategory')->name('product-categories.show')->middleware($can(TeamPermission::ViewProductCategory));
        Route::patch('product-categories/{productCategory}', 'updateProductCategory')->name('product-categories.update')->middleware($can(TeamPermission::UpdateProductCategory));
        Route::delete('product-categories/{productCategory}', 'destroyProductCategory')->name('product-categories.destroy')->middleware($can(TeamPermission::DeleteProductCategory));

        Route::get('products', 'listProducts')->name('products.index')->middleware($can(TeamPermission::ViewProduct));
        Route::post('products', 'storeProduct')->name('products.store')->middleware($can(TeamPermission::CreateProduct));
        Route::get('products/replenishment', 'listReplenishment')->name('products.replenishment.index')->middleware($can(TeamPermission::ViewReorderingRule));
        Route::post('products/replenishment/purchase-orders', 'storeReplenishmentPurchaseOrders')->name('products.replenishment.purchase-orders.store')
            ->middleware($can(TeamPermission::ViewReorderingRule, TeamPermission::CreatePurchaseOrder));
    });

    Route::put('inventory-settings', [InventorySettingController::class, 'update'])->name('inventory-settings.update')->middleware($can(TeamPermission::UpdateInventorySetting));
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index')->middleware($can(TeamPermission::ViewActivityLog));
    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show')->middleware($can(TeamPermission::ViewActivityLog));
    Route::get('document-sequences', [DocumentSequenceController::class, 'index'])->name('document-sequences.index')->middleware($can(TeamPermission::ViewDocumentSequence));
    Route::put('document-sequences/{documentType}', [DocumentSequenceController::class, 'update'])
        ->whereIn('documentType', array_column(DocumentType::cases(), 'value'))
        ->name('document-sequences.update')
        ->middleware($can(TeamPermission::UpdateDocumentSequence));

    Route::apiResource('document-rules', DocumentRuleController::class)->except('show')->parameters(['document-rules' => 'documentRule'])
        ->middlewareFor('index', $can(TeamPermission::ViewDocumentRule))
        ->middlewareFor('store', $can(TeamPermission::CreateDocumentRule))
        ->middlewareFor('update', $can(TeamPermission::UpdateDocumentRule))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteDocumentRule));

    Route::put('accounting-settings', [AccountingSettingController::class, 'update'])->name('accounting-settings.update')->middleware($can(TeamPermission::UpdateAccountingSetting));
    Route::post('inventory-closings', [InventoryClosingController::class, 'store'])->name('inventory-closings.store')->middleware($can(TeamPermission::CreateInventoryClosing));
    Route::apiResource('chart-of-accounts', ChartOfAccountController::class)->except(['index', 'show'])->parameters(['chart-of-accounts' => 'chartOfAccount'])
        ->middlewareFor('store', $can(TeamPermission::CreateChartOfAccount))
        ->middlewareFor('update', $can(TeamPermission::UpdateChartOfAccount))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteChartOfAccount));
    Route::apiResource('journals', JournalController::class)->except(['index', 'show'])
        ->middlewareFor('store', $can(TeamPermission::CreateJournal))
        ->middlewareFor('update', $can(TeamPermission::UpdateJournal))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteJournal));

    Route::get('inventory-settings', [InventorySettingController::class, 'show'])->name('inventory-settings.show')->middleware($can(TeamPermission::ViewInventorySetting));
    Route::get('accounting-settings', [AccountingSettingController::class, 'show'])->name('accounting-settings.show')->middleware($can(TeamPermission::ViewAccountingSetting));
    Route::get('inventory-closings/{period}', [InventoryClosingController::class, 'show'])->name('inventory-closings.show')->middleware($can(TeamPermission::ViewInventoryClosing));

    Route::apiResource('warehouses', WarehouseController::class)
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewWarehouse))
        ->middlewareFor('store', $can(TeamPermission::CreateWarehouse))
        ->middlewareFor('update', $can(TeamPermission::UpdateWarehouse))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteWarehouse));
    Route::apiResource('suppliers', SupplierController::class)
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewSupplier))
        ->middlewareFor('store', $can(TeamPermission::CreateSupplier))
        ->middlewareFor('update', $can(TeamPermission::UpdateSupplier))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteSupplier));
    Route::apiResource('customers', CustomerController::class)
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewCustomer))
        ->middlewareFor('store', $can(TeamPermission::CreateCustomer))
        ->middlewareFor('update', $can(TeamPermission::UpdateCustomer))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteCustomer));
    Route::apiResource('currencies', CurrencyController::class)
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewCurrency))
        ->middlewareFor('store', $can(TeamPermission::CreateCurrency))
        ->middlewareFor('update', $can(TeamPermission::UpdateCurrency))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteCurrency));
    Route::apiResource('tax-rates', TaxRateController::class)->parameters(['tax-rates' => 'taxRate'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewTaxRate))
        ->middlewareFor('store', $can(TeamPermission::CreateTaxRate))
        ->middlewareFor('update', $can(TeamPermission::UpdateTaxRate))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteTaxRate));
    Route::apiResource('quotation-templates', QuotationTemplateController::class)->parameters(['quotation-templates' => 'quotationTemplate'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewQuotationTemplate))
        ->middlewareFor('store', $can(TeamPermission::CreateQuotationTemplate))
        ->middlewareFor('update', $can(TeamPermission::UpdateQuotationTemplate))
        ->middlewareFor('destroy', $can(TeamPermission::DeleteQuotationTemplate));

    Route::apiResource('purchase-orders', PurchaseOrderController::class)->except('destroy')->parameters(['purchase-orders' => 'purchaseOrder'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewPurchaseOrder))
        ->middlewareFor('store', $can(TeamPermission::CreatePurchaseOrder))
        ->middlewareFor('update', $can(TeamPermission::UpdatePurchaseOrder));
    Route::post('purchase-orders/{purchaseOrder}/items', [PurchaseOrderController::class, 'addItem'])->name('purchase-orders.items.store')->middleware($can(TeamPermission::UpdatePurchaseOrder));
    Route::post('purchase-orders/{purchaseOrder}/copy', [PurchaseOrderController::class, 'copy'])->name('purchase-orders.copy')->middleware($can(TeamPermission::CreatePurchaseOrder));
    Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])->name('purchase-orders.confirm')->middleware($can(TeamPermission::ConfirmPurchaseOrder));
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel')->middleware($can(TeamPermission::CancelPurchaseOrder));

    Route::apiResource('goods-receipts', GoodsReceiptController::class)->except('destroy')->parameters(['goods-receipts' => 'goodsReceipt'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewGoodsReceipt))
        ->middlewareFor('store', $can(TeamPermission::CreateGoodsReceipt))
        ->middlewareFor('update', $can(TeamPermission::UpdateGoodsReceipt));
    Route::post('goods-receipts/{goodsReceipt}/confirm', [GoodsReceiptController::class, 'confirm'])->name('goods-receipts.confirm')->middleware($can(TeamPermission::ConfirmGoodsReceipt));
    Route::post('goods-receipts/{goodsReceipt}/cancel', [GoodsReceiptController::class, 'cancel'])->name('goods-receipts.cancel')->middleware($can(TeamPermission::CancelGoodsReceipt));

    Route::apiResource('sales-orders', SalesOrderController::class)->except('destroy')->parameters(['sales-orders' => 'salesOrder'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewSalesOrder))
        ->middlewareFor('store', $can(TeamPermission::CreateSalesOrder))
        ->middlewareFor('update', $can(TeamPermission::UpdateSalesOrder));
    Route::post('sales-orders/{salesOrder}/items', [SalesOrderController::class, 'addItem'])->name('sales-orders.items.store')->middleware($can(TeamPermission::UpdateSalesOrder));
    Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->name('sales-orders.confirm')->middleware($can(TeamPermission::ConfirmSalesOrder));
    Route::post('sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel')->middleware($can(TeamPermission::CancelSalesOrder));
    Route::post('sales-orders/{salesOrder}/reserve', [SalesOrderController::class, 'reserve'])->name('sales-orders.reserve')->middleware($can(TeamPermission::ReserveSalesOrder));
    Route::post('sales-orders/{salesOrder}/unreserve', [SalesOrderController::class, 'unreserve'])->name('sales-orders.unreserve')->middleware($can(TeamPermission::ReserveSalesOrder));

    Route::apiResource('shipments', ShipmentController::class)->except('destroy')->parameters(['shipments' => 'shipment'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewShipment))
        ->middlewareFor('store', $can(TeamPermission::CreateShipment))
        ->middlewareFor('update', $can(TeamPermission::UpdateShipment));
    Route::post('shipments/{shipment}/confirm', [ShipmentController::class, 'confirm'])->name('shipments.confirm')->middleware($can(TeamPermission::ConfirmShipment));
    Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel')->middleware($can(TeamPermission::CancelShipment));

    Route::apiResource('purchase-returns', PurchaseReturnController::class)->except('destroy')->parameters(['purchase-returns' => 'purchaseReturn'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewPurchaseReturn))
        ->middlewareFor('store', $can(TeamPermission::CreatePurchaseReturn))
        ->middlewareFor('update', $can(TeamPermission::UpdatePurchaseReturn));
    Route::post('purchase-returns/{purchaseReturn}/confirm', [PurchaseReturnController::class, 'confirm'])->name('purchase-returns.confirm')->middleware($can(TeamPermission::ConfirmPurchaseReturn));
    Route::post('purchase-returns/{purchaseReturn}/cancel', [PurchaseReturnController::class, 'cancel'])->name('purchase-returns.cancel')->middleware($can(TeamPermission::CancelPurchaseReturn));

    Route::apiResource('sales-returns', SalesReturnController::class)->except('destroy')->parameters(['sales-returns' => 'salesReturn'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewSalesReturn))
        ->middlewareFor('store', $can(TeamPermission::CreateSalesReturn))
        ->middlewareFor('update', $can(TeamPermission::UpdateSalesReturn));
    Route::post('sales-returns/{salesReturn}/confirm', [SalesReturnController::class, 'confirm'])->name('sales-returns.confirm')->middleware($can(TeamPermission::ConfirmSalesReturn));
    Route::post('sales-returns/{salesReturn}/cancel', [SalesReturnController::class, 'cancel'])->name('sales-returns.cancel')->middleware($can(TeamPermission::CancelSalesReturn));

    Route::apiResource('consignment-holds', ConsignmentHoldController::class)->only(['index', 'store', 'show'])->parameters(['consignment-holds' => 'consignmentHold'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewConsignmentHold))
        ->middlewareFor('store', $can(TeamPermission::CreateConsignmentHold));
    Route::post('consignment-holds/{consignmentHold}/pickup', [ConsignmentHoldController::class, 'pickup'])->name('consignment-holds.pickup')->middleware($can(TeamPermission::PickupConsignmentHold));

    Route::apiResource('warehouse-transfers', WarehouseTransferController::class)->except('destroy')->parameters(['warehouse-transfers' => 'warehouseTransfer'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewWarehouseTransfer))
        ->middlewareFor('store', $can(TeamPermission::CreateWarehouseTransfer))
        ->middlewareFor('update', $can(TeamPermission::UpdateWarehouseTransfer));
    Route::post('warehouse-transfers/{warehouseTransfer}/confirm', [WarehouseTransferController::class, 'confirm'])->name('warehouse-transfers.confirm')->middleware($can(TeamPermission::ConfirmWarehouseTransfer));
    Route::post('warehouse-transfers/{warehouseTransfer}/cancel', [WarehouseTransferController::class, 'cancel'])->name('warehouse-transfers.cancel')->middleware($can(TeamPermission::CancelWarehouseTransfer));

    Route::apiResource('stock-takes', StockTakeController::class)->except('destroy')->parameters(['stock-takes' => 'stockTake'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewStockTake))
        ->middlewareFor('store', $can(TeamPermission::CreateStockTake))
        ->middlewareFor('update', $can(TeamPermission::UpdateStockTake));
    Route::post('stock-takes/{stockTake}/confirm', [StockTakeController::class, 'confirm'])->name('stock-takes.confirm')->middleware($can(TeamPermission::ConfirmStockTake));
    Route::post('stock-takes/{stockTake}/cancel', [StockTakeController::class, 'cancel'])->name('stock-takes.cancel')->middleware($can(TeamPermission::CancelStockTake));

    Route::apiResource('material-requisitions', MaterialRequisitionController::class)->except('destroy')->parameters(['material-requisitions' => 'materialRequisition'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewMaterialRequisition))
        ->middlewareFor('store', $can(TeamPermission::CreateMaterialRequisition))
        ->middlewareFor('update', $can(TeamPermission::UpdateMaterialRequisition));
    Route::post('material-requisitions/{materialRequisition}/confirm', [MaterialRequisitionController::class, 'confirm'])->name('material-requisitions.confirm')->middleware($can(TeamPermission::ConfirmMaterialRequisition));
    Route::post('material-requisitions/{materialRequisition}/cancel', [MaterialRequisitionController::class, 'cancel'])->name('material-requisitions.cancel')->middleware($can(TeamPermission::CancelMaterialRequisition));

    Route::apiResource('chart-of-accounts', ChartOfAccountController::class)->only(['index', 'show'])->parameters(['chart-of-accounts' => 'chartOfAccount'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewChartOfAccount));
    Route::apiResource('journals', JournalController::class)->only(['index', 'show'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewJournal));
    Route::apiResource('journal-entries', JournalEntryController::class)->only(['index', 'show'])->parameters(['journal-entries' => 'journalEntry'])
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewJournalEntry));

    Route::post('payments/quick-receipt', [PaymentController::class, 'quickReceipt'])->name('payments.quick-receipt')
        ->middleware($can(TeamPermission::CreatePayment, TeamPermission::ConfirmPayment));
    Route::apiResource('payments', PaymentController::class)->except('destroy')
        ->middlewareFor(['index', 'show'], $can(TeamPermission::ViewPayment))
        ->middlewareFor('store', $can(TeamPermission::CreatePayment))
        ->middlewareFor('update', $can(TeamPermission::UpdatePayment));
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm')->middleware($can(TeamPermission::ConfirmPayment));
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel')->middleware($can(TeamPermission::CancelPayment));
    Route::get('payments/{payment}/allocations', [PaymentController::class, 'allocations'])->name('payments.allocations.index')->middleware($can(TeamPermission::ViewPayment));
    Route::post('payments/{payment}/allocations', [PaymentController::class, 'allocate'])->name('payments.allocations.store')->middleware($can(TeamPermission::AllocatePayment));
    Route::get('reports/aging', AgingReportController::class)->name('reports.aging')->middleware($can(TeamPermission::ViewAgingReport));
    Route::get('reports/sales-margin', [SalesMarginReportController::class, 'show'])->name('reports.sales-margin')->middleware($can(TeamPermission::ViewSalesMarginReport));
    Route::get('reports/sales-margin/pdf', [SalesMarginReportController::class, 'pdf'])->name('reports.sales-margin.pdf')->middleware($can(TeamPermission::ViewSalesMarginReport));

    Route::get('stock-balances', [StockController::class, 'balances'])->name('stock-balances.index')->middleware($can(TeamPermission::ViewStockBalance));
    Route::get('stock-balances/{stockBalance}', [StockController::class, 'balance'])->name('stock-balances.show')->middleware($can(TeamPermission::ViewStockBalance));
    Route::get('stock-lots', [StockLotController::class, 'index'])->name('stock-lots.index')->middleware($can(TeamPermission::ViewStockLot));
    Route::get('stock-lots/{stockLot}', [StockLotController::class, 'show'])->name('stock-lots.show')->middleware($can(TeamPermission::ViewStockLot));
    Route::get('stock-movements', [StockController::class, 'movements'])->name('stock-movements.index')->middleware($can(TeamPermission::ViewStockMovement));
    Route::get('stock-movements/{stockMovement}', [StockController::class, 'movement'])->name('stock-movements.show')->middleware($can(TeamPermission::ViewStockMovement));

    Route::controller(ProductController::class)
        ->prefix('products/{product}')
        ->whereNumber('product')
        ->name('products.')
        ->group(function () use ($can) {
            Route::get('/', 'showProduct')->name('show')->middleware($can(TeamPermission::ViewProduct));
            Route::patch('/', 'updateProduct')->name('update')->middleware($can(TeamPermission::UpdateProduct));
            Route::delete('/', 'destroyProduct')->name('destroy')->middleware($can(TeamPermission::DeleteProduct));
            Route::get('stock', 'showStock')->name('stock')->middleware($can(TeamPermission::ViewProduct));
            Route::get('goods-receipts', 'listGoodsReceipts')->name('goods-receipts')->middleware($can(TeamPermission::ViewProduct));
            Route::get('shipments', 'listShipments')->name('shipments')->middleware($can(TeamPermission::ViewProduct));
            Route::get('stock-movements', 'listStockMovements')->name('stock-movements')->middleware($can(TeamPermission::ViewProduct));
            Route::get('returns', 'listReturns')->name('returns')->middleware($can(TeamPermission::ViewProduct));

            Route::get('suppliers', 'listSuppliers')->name('suppliers.index')->middleware($can(TeamPermission::ViewProductSupplier));
            Route::post('suppliers', 'storeSupplier')->name('suppliers.store')->middleware($can(TeamPermission::CreateProductSupplier));
            Route::patch('suppliers/{productSupplier}', 'updateSupplier')->name('suppliers.update')->middleware($can(TeamPermission::UpdateProductSupplier));
            Route::delete('suppliers/{productSupplier}', 'destroySupplier')->name('suppliers.destroy')->middleware($can(TeamPermission::DeleteProductSupplier));
            Route::get('purchase-price', 'showPurchasePrice')->name('purchase-price')->middleware($can(TeamPermission::ViewProduct));

            Route::get('discounts', 'listDiscounts')->name('discounts.index')->middleware($can(TeamPermission::ViewProductDiscount));
            Route::post('discounts', 'storeDiscount')->name('discounts.store')->middleware($can(TeamPermission::CreateProductDiscount));
            Route::patch('discounts/{discount}', 'updateDiscount')->name('discounts.update')->middleware($can(TeamPermission::UpdateProductDiscount));
            Route::delete('discounts/{discount}', 'destroyDiscount')->name('discounts.destroy')->middleware($can(TeamPermission::DeleteProductDiscount));

            Route::get('reordering-rules', 'listReorderingRules')->name('reordering-rules.index')->middleware($can(TeamPermission::ViewReorderingRule));
            Route::post('reordering-rules', 'storeReorderingRule')->name('reordering-rules.store')->middleware($can(TeamPermission::CreateReorderingRule));
            Route::patch('reordering-rules/{reorderingRule}', 'updateReorderingRule')->name('reordering-rules.update')->middleware($can(TeamPermission::UpdateReorderingRule));
            Route::delete('reordering-rules/{reorderingRule}', 'destroyReorderingRule')->name('reordering-rules.destroy')->middleware($can(TeamPermission::DeleteReorderingRule));
        });
};
