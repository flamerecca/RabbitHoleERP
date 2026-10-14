<?php

namespace App\Filament\Resources\UnitCategories;

use App\Actions\Products\DeleteUnitCategory;
use App\Filament\Resources\UnitCategories\Pages\ManageUnitCategories;
use App\Filament\Support\DomainAction;
use App\Models\UnitCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitCategoryResource extends Resource
{
    protected static ?string $model = UnitCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Products');
    }

    public static function getModelLabel(): string
    {
        return __('Unit category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Unit categories');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->scopedUnique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('units_count')
                    ->counts('units')
                    ->label('Units'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (UnitCategory $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteUnitCategory::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnitCategories::route('/'),
        ];
    }
}
