<?php

namespace App\Filament\Pages;

use App\Actions\Mrp\CalculateRequirements;
use App\Data\RequirementLine;
use App\Filament\Support\TeamOptions;
use App\Models\Product;
use App\Models\Team;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use LogicException;

class MrpPlanning extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Planning');
    }

    public static function getNavigationLabel(): string
    {
        return __('MRP planning');
    }

    public function getTitle(): string
    {
        return __('MRP planning');
    }

    /**
     * Only teams that enabled the MRP module may open the planning.
     */
    public static function canAccess(): bool
    {
        $team = Filament::getTenant();

        return $team instanceof Team && $team->isMrpEnabled();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->requirements())
            ->paginated(false)
            ->emptyStateHeading(__('No requirements.'))
            ->columns([
                TextColumn::make('product')
                    ->label(__('Product')),
                TextColumn::make('level')
                    ->label(__('Level')),
                TextColumn::make('gross')
                    ->label(__('Gross requirement'))
                    ->numeric(maxDecimalPlaces: 4),
                TextColumn::make('on_hand')
                    ->label(__('On hand'))
                    ->numeric(maxDecimalPlaces: 4),
                TextColumn::make('on_order')
                    ->label(__('On order'))
                    ->numeric(maxDecimalPlaces: 4),
                TextColumn::make('net')
                    ->label(__('Net requirement'))
                    ->numeric(maxDecimalPlaces: 4),
            ]);
    }

    /**
     * @return array<int, array{id: int, product: string, level: int, gross: string, on_hand: string, on_order: string, net: string}>
     */
    protected function requirements(): array
    {
        $team = TeamOptions::team();

        try {
            $lines = app(CalculateRequirements::class)->handle($team);
        } catch (LogicException) {
            Notification::make()->danger()->title(__('The active bills of materials contain a cycle. Fix them and try again.'))->send();

            return [];
        }

        $products = Product::query()->where('team_id', $team->id)->whereIn('id', $lines->pluck('productId'))->get()->keyBy('id');

        return $lines->mapWithKeys(fn (RequirementLine $line): array => [$line->productId => [
            'id' => $line->productId,
            'product' => "{$products[$line->productId]->sku} {$products[$line->productId]->name}",
            'level' => $line->level,
            'gross' => $line->grossRequirement,
            'on_hand' => $line->quantityOnHand,
            'on_order' => $line->quantityOnOrder,
            'net' => $line->netRequirement,
        ]])->all();
    }
}
