<?php

namespace App\Support\Rbac;

use App\Enums\TeamPermission;
use App\Models\ConsignmentHold;
use App\Models\GoodsReceipt;
use App\Models\ManufacturingOrder;
use App\Models\MaterialRequisition;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\ScrapOrder;
use App\Models\Shipment;
use App\Models\StockTake;
use App\Models\UnbuildOrder;
use App\Models\WarehouseTransfer;
use LogicException;

class DocumentPermissions
{
    /**
     * The permission resource of each document the admin panel runs state transitions on.
     *
     * @var array<class-string, string>
     */
    protected const RESOURCES = [
        PurchaseOrder::class => 'purchase_order',
        GoodsReceipt::class => 'goods_receipt',
        PurchaseReturn::class => 'purchase_return',
        SalesOrder::class => 'sales_order',
        Shipment::class => 'shipment',
        SalesReturn::class => 'sales_return',
        ConsignmentHold::class => 'consignment_hold',
        MaterialRequisition::class => 'material_requisition',
        WarehouseTransfer::class => 'warehouse_transfer',
        StockTake::class => 'stock_take',
        ManufacturingOrder::class => 'manufacturing_order',
        ScrapOrder::class => 'scrap_order',
        UnbuildOrder::class => 'unbuild_order',
    ];

    /**
     * The permission action of each transition button name.
     *
     * @var array<string, string>
     */
    protected const ACTIONS = [
        'confirm' => 'confirm',
        'cancel' => 'cancel',
        'complete' => 'complete',
        'reserve' => 'reserve',
        'unreserve' => 'reserve',
        'pickup' => 'pickup',
    ];

    /**
     * Get the team permission a state transition button of the document needs.
     *
     * @param  class-string  $recordClass
     */
    public static function for(string $recordClass, string $name): TeamPermission
    {
        $resource = self::RESOURCES[$recordClass] ?? throw new LogicException("No team permission resource is mapped for {$recordClass}.");
        $action = self::ACTIONS[$name] ?? throw new LogicException("No team permission action is mapped for the {$name} button.");

        return TeamPermission::tryFrom("{$resource}:{$action}")
            ?? throw new LogicException("The permission {$resource}:{$action} does not exist.");
    }
}
