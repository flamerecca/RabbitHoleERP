<?php

namespace App\Filament\Resources\MaterialRequisitions;

use App\Enums\DocumentStatus;
use App\Filament\Resources\MaterialRequisitions\Pages\CreateMaterialRequisition;
use App\Filament\Resources\MaterialRequisitions\Pages\EditMaterialRequisition;
use App\Filament\Resources\MaterialRequisitions\Pages\ListMaterialRequisitions;
use App\Filament\Support\DocumentColumns;
use App\Filament\Support\DocumentForm;
use App\Filament\Support\TeamOptions;
use App\Models\MaterialRequisition;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaterialRequisitionResource extends Resource
{
    protected static ?string $model = MaterialRequisition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'requisition_no';

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getModelLabel(): string
    {
        return __('Material requisition');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Material requisitions');
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema, 'requisition_no', [
            Select::make('warehouse_id')
                ->label('Warehouse')
                ->options(fn () => TeamOptions::warehouses())
                ->required()
                ->disabledOn('edit'),
            DatePicker::make('requisition_date')
                ->default(now())
                ->required(),
            TextInput::make('purpose')
                ->required()
                ->maxLength(255),
            Select::make('requested_by')
                ->label('Requested by')
                ->options(fn () => TeamOptions::members())
                ->default(fn () => auth()->id())
                ->required(),
        ], [
            Repeater::make('items')
                ->label(__('Lines'))
                ->addActionLabel(__('Add line'))
                ->hiddenLabel()
                ->columns(4)
                ->minItems(1)
                ->required()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn () => TeamOptions::products())
                        ->searchable()
                        ->required()
                        ->columnSpan(2),
                    TextInput::make('quantity')
                        ->numeric()
                        ->rules(['gt:0'])
                        ->required(),
                    TextInput::make('note')
                        ->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('requisition_no')
                    ->label('Document no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Warehouse'),
                TextColumn::make('requisition_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('purpose'),
                DocumentColumns::status(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => TeamOptions::statuses(DocumentStatus::class)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('Open')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterialRequisitions::route('/'),
            'create' => CreateMaterialRequisition::route('/create'),
            'edit' => EditMaterialRequisition::route('/{record}/edit'),
        ];
    }
}
