<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ReorderingRule;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class ReorderingRulesRelationManager extends RelationManager
{
    // validation duplicated from ProductController::storeReorderingRule and updateReorderingRule, see docs/architecture.md section 4.8

    protected static string $relationship = 'reorderingRules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Reordering rules');
    }

    public static function getModelLabel(): string
    {
        return __('Reordering rule');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'code', fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->getKey()))
                    ->required()
                    ->unique(
                        table: ReorderingRule::class,
                        column: 'warehouse_id',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('product_id', $this->getOwnerRecord()->getKey()),
                    ),
                Toggle::make('is_active')
                    ->default(true)
                    ->inline(false),
                TextInput::make('min_quantity')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText(__('In the stock unit.')),
                TextInput::make('max_quantity')
                    ->numeric()
                    ->required()
                    ->gte('min_quantity'),
                TextInput::make('multiple_quantity')
                    ->numeric()
                    ->rules(['gt:0'])
                    ->default(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.code')
                    ->label('Warehouse'),
                TextColumn::make('min_quantity')
                    ->numeric(),
                TextColumn::make('max_quantity')
                    ->numeric(),
                TextColumn::make('multiple_quantity')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data) => [...$data, 'team_id' => Filament::getTenant()?->getKey()]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
