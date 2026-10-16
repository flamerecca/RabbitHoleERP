<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                ...TeamPermission::erp(),
                TeamPermission::UpdateTeam,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
            ],
            self::Member => self::memberPermissions(),
        };
    }

    /**
     * Get the permissions for this role under the given profile.
     *
     * The legacy profile keeps what a member could do in version one, which was everything except a few settings
     * and accounting maintenance routes that were limited to admins.
     *
     * @return array<TeamPermission>
     */
    public function permissionsFor(PermissionProfile $profile): array
    {
        if ($profile === PermissionProfile::Current || $this !== self::Member) {
            return $this->permissions();
        }

        $adminOnly = self::legacyAdminOnlyPermissions();

        return array_values(array_filter(TeamPermission::erp(), fn (TeamPermission $permission) => ! in_array($permission, $adminOnly, true)));
    }

    /**
     * Determine if the role has the given permission under the given profile.
     */
    public function hasPermission(TeamPermission $permission, PermissionProfile $profile = PermissionProfile::Current): bool
    {
        return in_array($permission, $this->permissionsFor($profile), true);
    }

    /**
     * Get the ERP permissions of a member, the ones marked M in docs/architecture/permissions.md.
     *
     * @return list<TeamPermission>
     */
    protected static function memberPermissions(): array
    {
        return [
            TeamPermission::ViewPosConfig,
            TeamPermission::ViewPosPaymentMethod,
            TeamPermission::ViewProduct,
            TeamPermission::ViewProductCategory,
            TeamPermission::ViewUnitCategory,
            TeamPermission::ViewUnit,
            TeamPermission::ViewWarehouse,
            TeamPermission::ViewSupplier,
            TeamPermission::ViewCustomer,
            TeamPermission::ViewCurrency,
            TeamPermission::ViewTaxRate,
            TeamPermission::ViewProductSupplier,
            TeamPermission::ViewProductDiscount,
            TeamPermission::ViewPurchaseOrder,
            TeamPermission::CreatePurchaseOrder,
            TeamPermission::UpdatePurchaseOrder,
            TeamPermission::ViewGoodsReceipt,
            TeamPermission::CreateGoodsReceipt,
            TeamPermission::UpdateGoodsReceipt,
            TeamPermission::ConfirmGoodsReceipt,
            TeamPermission::ViewPurchaseReturn,
            TeamPermission::CreatePurchaseReturn,
            TeamPermission::UpdatePurchaseReturn,
            TeamPermission::ViewSalesOrder,
            TeamPermission::CreateSalesOrder,
            TeamPermission::UpdateSalesOrder,
            TeamPermission::ReserveSalesOrder,
            TeamPermission::ViewShipment,
            TeamPermission::CreateShipment,
            TeamPermission::UpdateShipment,
            TeamPermission::ConfirmShipment,
            TeamPermission::ViewSalesReturn,
            TeamPermission::CreateSalesReturn,
            TeamPermission::UpdateSalesReturn,
            TeamPermission::ViewConsignmentHold,
            TeamPermission::CreateConsignmentHold,
            TeamPermission::PickupConsignmentHold,
            TeamPermission::ViewQuotationTemplate,
            TeamPermission::ViewStockBalance,
            TeamPermission::ViewStockMovement,
            TeamPermission::ViewStockLot,
            TeamPermission::ViewMaterialRequisition,
            TeamPermission::CreateMaterialRequisition,
            TeamPermission::UpdateMaterialRequisition,
            TeamPermission::ConfirmMaterialRequisition,
            TeamPermission::ViewWarehouseTransfer,
            TeamPermission::CreateWarehouseTransfer,
            TeamPermission::UpdateWarehouseTransfer,
            TeamPermission::ConfirmWarehouseTransfer,
            TeamPermission::ViewStockTake,
            TeamPermission::CreateStockTake,
            TeamPermission::UpdateStockTake,
            TeamPermission::ViewReorderingRule,
            TeamPermission::ViewInventorySetting,
            TeamPermission::ViewBom,
            TeamPermission::ViewMrpPlanning,
            TeamPermission::ViewManufacturingOrder,
            TeamPermission::ViewScrapOrder,
            TeamPermission::CreateScrapOrder,
            TeamPermission::UpdateScrapOrder,
            TeamPermission::ViewUnbuildOrder,
            TeamPermission::CreateUnbuildOrder,
            TeamPermission::UpdateUnbuildOrder,
        ];
    }

    /**
     * Get the permissions of the operations version one limited to admins with a route level role check.
     *
     * @return list<TeamPermission>
     */
    protected static function legacyAdminOnlyPermissions(): array
    {
        return [
            TeamPermission::UpdateInventorySetting,
            TeamPermission::ViewActivityLog,
            TeamPermission::ViewDocumentSequence,
            TeamPermission::UpdateDocumentSequence,
            TeamPermission::ViewDocumentRule,
            TeamPermission::CreateDocumentRule,
            TeamPermission::UpdateDocumentRule,
            TeamPermission::DeleteDocumentRule,
            TeamPermission::UpdateAccountingSetting,
            TeamPermission::CreateInventoryClosing,
            TeamPermission::CreateChartOfAccount,
            TeamPermission::UpdateChartOfAccount,
            TeamPermission::DeleteChartOfAccount,
            TeamPermission::CreateJournal,
            TeamPermission::UpdateJournal,
            TeamPermission::DeleteJournal,
        ];
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Admin => 2,
            self::Member => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
