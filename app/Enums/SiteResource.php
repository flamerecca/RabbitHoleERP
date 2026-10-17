<?php

namespace App\Enums;

use App\Models\ActivityLog;
use App\Models\Bom;
use App\Models\ConsignmentHold;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\ManufacturingOrder;
use App\Models\MaterialRequisition;
use App\Models\PosConfig;
use App\Models\PosOrder;
use App\Models\PosPaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\QuotationTemplate;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\ScrapOrder;
use App\Models\Shipment;
use App\Models\StockBalance;
use App\Models\StockLotBalance;
use App\Models\StockMovement;
use App\Models\StockTake;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\UnbuildOrder;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;

enum SiteResource: string
{
    case ActivityLogs = 'activity_logs';
    case Boms = 'boms';
    case ConsignmentHolds = 'consignment_holds';
    case Currencies = 'currencies';
    case Customers = 'customers';
    case Employees = 'employees';
    case GoodsReceipts = 'goods_receipts';
    case ManufacturingOrders = 'manufacturing_orders';
    case MaterialRequisitions = 'material_requisitions';
    case PosOrders = 'pos_orders';
    case PosConfigs = 'pos_configs';
    case PosPaymentMethods = 'pos_payment_methods';
    case PosSessions = 'pos_sessions';
    case ProductCategories = 'product_categories';
    case Products = 'products';
    case PurchaseOrders = 'purchase_orders';
    case PurchaseReturns = 'purchase_returns';
    case QuotationTemplates = 'quotation_templates';
    case SalesOrders = 'sales_orders';
    case SalesReturns = 'sales_returns';
    case ScrapOrders = 'scrap_orders';
    case Shipments = 'shipments';
    case StockBalances = 'stock_balances';
    case StockLots = 'stock_lots';
    case StockMovements = 'stock_movements';
    case StockTakes = 'stock_takes';
    case Suppliers = 'suppliers';
    case TaxRates = 'tax_rates';
    case UnbuildOrders = 'unbuild_orders';
    case UnitCategories = 'unit_categories';
    case Units = 'units';
    case Warehouses = 'warehouses';
    case WarehouseTransfers = 'warehouse_transfers';

    /**
     * Get the Eloquent model class the Filament resource of this entry manages.
     *
     * @return class-string
     */
    public function model(): string
    {
        return match ($this) {
            self::ActivityLogs => ActivityLog::class,
            self::Boms => Bom::class,
            self::ConsignmentHolds => ConsignmentHold::class,
            self::Currencies => Currency::class,
            self::Customers => Customer::class,
            self::Employees => Employee::class,
            self::GoodsReceipts => GoodsReceipt::class,
            self::ManufacturingOrders => ManufacturingOrder::class,
            self::MaterialRequisitions => MaterialRequisition::class,
            self::PosOrders => PosOrder::class,
            self::PosConfigs => PosConfig::class,
            self::PosPaymentMethods => PosPaymentMethod::class,
            self::PosSessions => PosSession::class,
            self::ProductCategories => ProductCategory::class,
            self::Products => Product::class,
            self::PurchaseOrders => PurchaseOrder::class,
            self::PurchaseReturns => PurchaseReturn::class,
            self::QuotationTemplates => QuotationTemplate::class,
            self::SalesOrders => SalesOrder::class,
            self::SalesReturns => SalesReturn::class,
            self::ScrapOrders => ScrapOrder::class,
            self::Shipments => Shipment::class,
            self::StockBalances => StockBalance::class,
            self::StockLots => StockLotBalance::class,
            self::StockMovements => StockMovement::class,
            self::StockTakes => StockTake::class,
            self::Suppliers => Supplier::class,
            self::TaxRates => TaxRate::class,
            self::UnbuildOrders => UnbuildOrder::class,
            self::UnitCategories => UnitCategory::class,
            self::Units => Unit::class,
            self::Warehouses => Warehouse::class,
            self::WarehouseTransfers => WarehouseTransfer::class,
        };
    }

    /**
     * Find the entry whose model is the given class, or null when the model is not a site resource.
     */
    public static function tryFromModel(string $model): ?self
    {
        foreach (self::cases() as $resource) {
            if ($resource->model() === $model) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * Get the permission name that allows the action on this resource.
     */
    public function permission(SiteAction $action): string
    {
        return "{$this->value}.{$action->value}";
    }
}
