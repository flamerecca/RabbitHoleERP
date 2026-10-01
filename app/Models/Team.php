<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property bool $require_lot_tracking
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TeamInvitation> $invitations
 * @property-read Collection<int, Membership> $memberships
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, UnitCategory> $unitCategories
 * @property-read Collection<int, GoodsReceipt> $goodsReceipts
 * @property-read Collection<int, Warehouse> $warehouses
 * @property-read Collection<int, Supplier> $suppliers
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Currency> $currencies
 * @property-read Collection<int, TaxRate> $taxRates
 * @property-read Collection<int, PurchaseReturn> $purchaseReturns
 * @property-read Collection<int, SalesReturn> $salesReturns
 * @property-read Collection<int, ConsignmentHold> $consignmentHolds
 * @property-read Collection<int, WarehouseTransfer> $warehouseTransfers
 * @property-read Collection<int, StockTake> $stockTakes
 * @property-read Collection<int, MaterialRequisition> $materialRequisitions
 * @property-read Collection<int, StockBalance> $stockBalances
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property-read Collection<int, ActivityLog> $activityLogs
 * @property-read Collection<int, StockLot> $stockLots
 * @property-read Collection<int, StockLotBalance> $stockLotBalances
 * @property-read Collection<int, Shipment> $shipments
 * @property-read Collection<int, DocumentRule> $documentRules
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read Collection<int, SalesOrder> $salesOrders
 * @property-read Collection<int, Unit> $units
 * @property-read Collection<int, ProductCategory> $productCategories
 */
#[Fillable(['name', 'slug', 'is_personal', 'require_lot_tracking'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all products of this team.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all unit categories of this team.
     *
     * @return HasMany<UnitCategory, $this>
     */
    public function unitCategories(): HasMany
    {
        return $this->hasMany(UnitCategory::class);
    }

    /**
     * Get all goods receipts of this team.
     *
     * @return HasMany<GoodsReceipt, $this>
     */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /**
     * Get all shipments of this team.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get all document rules of this team.
     *
     * @return HasMany<DocumentRule, $this>
     */
    public function documentRules(): HasMany
    {
        return $this->hasMany(DocumentRule::class);
    }

    /**
     * Get all purchase orders of this team.
     *
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get all sales orders of this team.
     *
     * @return HasMany<SalesOrder, $this>
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Get all warehouses of this team.
     *
     * @return HasMany<Warehouse, $this>
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Get all suppliers of this team.
     *
     * @return HasMany<Supplier, $this>
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Get all customers of this team.
     *
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get all currencies of this team.
     *
     * @return HasMany<Currency, $this>
     */
    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    /**
     * Get all tax rates of this team.
     *
     * @return HasMany<TaxRate, $this>
     */
    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    /**
     * Get all purchase returns of this team.
     *
     * @return HasMany<PurchaseReturn, $this>
     */
    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * Get all sales returns of this team.
     *
     * @return HasMany<SalesReturn, $this>
     */
    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    /**
     * Get all consignment holds of this team.
     *
     * @return HasMany<ConsignmentHold, $this>
     */
    public function consignmentHolds(): HasMany
    {
        return $this->hasMany(ConsignmentHold::class);
    }

    /**
     * Get all warehouse transfers of this team.
     *
     * @return HasMany<WarehouseTransfer, $this>
     */
    public function warehouseTransfers(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class);
    }

    /**
     * Get all stock takes of this team.
     *
     * @return HasMany<StockTake, $this>
     */
    public function stockTakes(): HasMany
    {
        return $this->hasMany(StockTake::class);
    }

    /**
     * Get all material requisitions of this team.
     *
     * @return HasMany<MaterialRequisition, $this>
     */
    public function materialRequisitions(): HasMany
    {
        return $this->hasMany(MaterialRequisition::class);
    }

    /**
     * Get all stock balances of this team.
     *
     * @return HasMany<StockBalance, $this>
     */
    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    /**
     * Get all stock movements of this team.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the activity log of this team.
     *
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'c1');
    }

    /**
     * Get all stock lots of this team.
     *
     * @return HasMany<StockLot, $this>
     */
    public function stockLots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }

    /**
     * Get all stock lot balances of this team.
     *
     * @return HasMany<StockLotBalance, $this>
     */
    public function stockLotBalances(): HasMany
    {
        return $this->hasMany(StockLotBalance::class);
    }

    /**
     * Get all units of this team.
     *
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get all product categories of this team.
     *
     * @return HasMany<ProductCategory, $this>
     */
    public function productCategories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
            'require_lot_tracking' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
