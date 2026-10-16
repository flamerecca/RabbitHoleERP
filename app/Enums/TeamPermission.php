<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case ViewProduct = 'product:view';
    case CreateProduct = 'product:create';
    case UpdateProduct = 'product:update';
    case DeleteProduct = 'product:delete';

    case ViewProductCategory = 'product_category:view';
    case CreateProductCategory = 'product_category:create';
    case UpdateProductCategory = 'product_category:update';
    case DeleteProductCategory = 'product_category:delete';

    case ViewUnitCategory = 'unit_category:view';
    case CreateUnitCategory = 'unit_category:create';
    case UpdateUnitCategory = 'unit_category:update';
    case DeleteUnitCategory = 'unit_category:delete';

    case ViewUnit = 'unit:view';
    case CreateUnit = 'unit:create';
    case UpdateUnit = 'unit:update';
    case DeleteUnit = 'unit:delete';

    case ViewWarehouse = 'warehouse:view';
    case CreateWarehouse = 'warehouse:create';
    case UpdateWarehouse = 'warehouse:update';
    case DeleteWarehouse = 'warehouse:delete';

    case ViewSupplier = 'supplier:view';
    case CreateSupplier = 'supplier:create';
    case UpdateSupplier = 'supplier:update';
    case DeleteSupplier = 'supplier:delete';

    case ViewCustomer = 'customer:view';
    case CreateCustomer = 'customer:create';
    case UpdateCustomer = 'customer:update';
    case DeleteCustomer = 'customer:delete';

    case ViewCurrency = 'currency:view';
    case CreateCurrency = 'currency:create';
    case UpdateCurrency = 'currency:update';
    case DeleteCurrency = 'currency:delete';

    case ViewTaxRate = 'tax_rate:view';
    case CreateTaxRate = 'tax_rate:create';
    case UpdateTaxRate = 'tax_rate:update';
    case DeleteTaxRate = 'tax_rate:delete';

    case ViewProductSupplier = 'product_supplier:view';
    case CreateProductSupplier = 'product_supplier:create';
    case UpdateProductSupplier = 'product_supplier:update';
    case DeleteProductSupplier = 'product_supplier:delete';

    case ViewProductDiscount = 'product_discount:view';
    case CreateProductDiscount = 'product_discount:create';
    case UpdateProductDiscount = 'product_discount:update';
    case DeleteProductDiscount = 'product_discount:delete';

    case ViewPurchaseOrder = 'purchase_order:view';
    case CreatePurchaseOrder = 'purchase_order:create';
    case UpdatePurchaseOrder = 'purchase_order:update';
    case ConfirmPurchaseOrder = 'purchase_order:confirm';
    case CancelPurchaseOrder = 'purchase_order:cancel';

    case ViewGoodsReceipt = 'goods_receipt:view';
    case CreateGoodsReceipt = 'goods_receipt:create';
    case UpdateGoodsReceipt = 'goods_receipt:update';
    case ConfirmGoodsReceipt = 'goods_receipt:confirm';
    case CancelGoodsReceipt = 'goods_receipt:cancel';

    case ViewPurchaseReturn = 'purchase_return:view';
    case CreatePurchaseReturn = 'purchase_return:create';
    case UpdatePurchaseReturn = 'purchase_return:update';
    case ConfirmPurchaseReturn = 'purchase_return:confirm';
    case CancelPurchaseReturn = 'purchase_return:cancel';

    case ViewSalesOrder = 'sales_order:view';
    case CreateSalesOrder = 'sales_order:create';
    case UpdateSalesOrder = 'sales_order:update';
    case ConfirmSalesOrder = 'sales_order:confirm';
    case CancelSalesOrder = 'sales_order:cancel';
    case ReserveSalesOrder = 'sales_order:reserve';

    case ViewShipment = 'shipment:view';
    case CreateShipment = 'shipment:create';
    case UpdateShipment = 'shipment:update';
    case ConfirmShipment = 'shipment:confirm';
    case CancelShipment = 'shipment:cancel';

    case ViewSalesReturn = 'sales_return:view';
    case CreateSalesReturn = 'sales_return:create';
    case UpdateSalesReturn = 'sales_return:update';
    case ConfirmSalesReturn = 'sales_return:confirm';
    case CancelSalesReturn = 'sales_return:cancel';

    case ViewConsignmentHold = 'consignment_hold:view';
    case CreateConsignmentHold = 'consignment_hold:create';
    case PickupConsignmentHold = 'consignment_hold:pickup';

    case ViewQuotationTemplate = 'quotation_template:view';
    case CreateQuotationTemplate = 'quotation_template:create';
    case UpdateQuotationTemplate = 'quotation_template:update';
    case DeleteQuotationTemplate = 'quotation_template:delete';

    case ViewSalesMarginReport = 'sales_margin_report:view';

    case ViewStockBalance = 'stock_balance:view';

    case ViewStockMovement = 'stock_movement:view';

    case ViewStockLot = 'stock_lot:view';

    case ViewMaterialRequisition = 'material_requisition:view';
    case CreateMaterialRequisition = 'material_requisition:create';
    case UpdateMaterialRequisition = 'material_requisition:update';
    case ConfirmMaterialRequisition = 'material_requisition:confirm';
    case CancelMaterialRequisition = 'material_requisition:cancel';

    case ViewWarehouseTransfer = 'warehouse_transfer:view';
    case CreateWarehouseTransfer = 'warehouse_transfer:create';
    case UpdateWarehouseTransfer = 'warehouse_transfer:update';
    case ConfirmWarehouseTransfer = 'warehouse_transfer:confirm';
    case CancelWarehouseTransfer = 'warehouse_transfer:cancel';

    case ViewStockTake = 'stock_take:view';
    case CreateStockTake = 'stock_take:create';
    case UpdateStockTake = 'stock_take:update';
    case ConfirmStockTake = 'stock_take:confirm';
    case CancelStockTake = 'stock_take:cancel';

    case ViewReorderingRule = 'reordering_rule:view';
    case CreateReorderingRule = 'reordering_rule:create';
    case UpdateReorderingRule = 'reordering_rule:update';
    case DeleteReorderingRule = 'reordering_rule:delete';

    case ViewInventorySetting = 'inventory_setting:view';
    case UpdateInventorySetting = 'inventory_setting:update';

    case ViewInventoryClosing = 'inventory_closing:view';
    case CreateInventoryClosing = 'inventory_closing:create';

    case ViewChartOfAccount = 'chart_of_account:view';
    case CreateChartOfAccount = 'chart_of_account:create';
    case UpdateChartOfAccount = 'chart_of_account:update';
    case DeleteChartOfAccount = 'chart_of_account:delete';

    case ViewJournal = 'journal:view';
    case CreateJournal = 'journal:create';
    case UpdateJournal = 'journal:update';
    case DeleteJournal = 'journal:delete';

    case ViewJournalEntry = 'journal_entry:view';

    case ViewPayment = 'payment:view';
    case CreatePayment = 'payment:create';
    case UpdatePayment = 'payment:update';
    case ConfirmPayment = 'payment:confirm';
    case CancelPayment = 'payment:cancel';
    case AllocatePayment = 'payment:allocate';

    case ViewAccountingSetting = 'accounting_setting:view';
    case UpdateAccountingSetting = 'accounting_setting:update';

    case ViewAgingReport = 'aging_report:view';

    case ViewDocumentSequence = 'document_sequence:view';
    case UpdateDocumentSequence = 'document_sequence:update';

    case ViewDocumentRule = 'document_rule:view';
    case CreateDocumentRule = 'document_rule:create';
    case UpdateDocumentRule = 'document_rule:update';
    case DeleteDocumentRule = 'document_rule:delete';

    case ViewActivityLog = 'activity_log:view';

    case ViewHrSetting = 'hr_setting:view';
    case UpdateHrSetting = 'hr_setting:update';

    case ViewEmployee = 'employee:view';
    case CreateEmployee = 'employee:create';
    case UpdateEmployee = 'employee:update';
    case DeleteEmployee = 'employee:delete';

    case ViewMrpSetting = 'mrp_setting:view';
    case UpdateMrpSetting = 'mrp_setting:update';

    case ViewScrapSetting = 'scrap_setting:view';
    case UpdateScrapSetting = 'scrap_setting:update';

    case ViewBom = 'bom:view';
    case CreateBom = 'bom:create';
    case UpdateBom = 'bom:update';
    case DeleteBom = 'bom:delete';

    case ViewMrpPlanning = 'mrp_planning:view';

    case ViewManufacturingOrder = 'manufacturing_order:view';
    case CreateManufacturingOrder = 'manufacturing_order:create';
    case ConfirmManufacturingOrder = 'manufacturing_order:confirm';
    case CancelManufacturingOrder = 'manufacturing_order:cancel';
    case CompleteManufacturingOrder = 'manufacturing_order:complete';

    case ViewScrapOrder = 'scrap_order:view';
    case CreateScrapOrder = 'scrap_order:create';
    case UpdateScrapOrder = 'scrap_order:update';
    case ConfirmScrapOrder = 'scrap_order:confirm';
    case CancelScrapOrder = 'scrap_order:cancel';

    case ViewUnbuildOrder = 'unbuild_order:view';
    case CreateUnbuildOrder = 'unbuild_order:create';
    case UpdateUnbuildOrder = 'unbuild_order:update';
    case ConfirmUnbuildOrder = 'unbuild_order:confirm';
    case CancelUnbuildOrder = 'unbuild_order:cancel';

    case ViewPosConfig = 'pos_config:view';
    case CreatePosConfig = 'pos_config:create';
    case UpdatePosConfig = 'pos_config:update';
    case DeletePosConfig = 'pos_config:delete';

    case ViewPosPaymentMethod = 'pos_payment_method:view';
    case CreatePosPaymentMethod = 'pos_payment_method:create';
    case UpdatePosPaymentMethod = 'pos_payment_method:update';
    case DeletePosPaymentMethod = 'pos_payment_method:delete';

    /**
     * Get the resource part of the permission value.
     */
    public function resource(): string
    {
        return explode(':', $this->value)[0];
    }

    /**
     * Get the action part of the permission value.
     */
    public function action(): string
    {
        return explode(':', $this->value)[1];
    }

    /**
     * Determine if the permission belongs to team and member management instead of the ERP modules.
     */
    public function isTeamManagement(): bool
    {
        return in_array($this, [
            self::UpdateTeam,
            self::DeleteTeam,
            self::AddMember,
            self::UpdateMember,
            self::RemoveMember,
            self::CreateInvitation,
            self::CancelInvitation,
        ], true);
    }

    /**
     * Get every ERP permission, without the team and member management ones.
     *
     * @return list<self>
     */
    public static function erp(): array
    {
        return array_values(array_filter(self::cases(), fn (self $permission) => ! $permission->isTeamManagement()));
    }
}
