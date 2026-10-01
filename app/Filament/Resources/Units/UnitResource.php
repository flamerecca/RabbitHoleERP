<?php

namespace App\Filament\Resources\Units;

use App\Actions\Products\DeleteUnit;
use App\Actions\Products\UpdateUnit;
use App\Filament\Resources\Units\Pages\ManageUnits;
use App\Filament\Support\DomainAction;
use App\Models\Unit;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): ?string
    {
        return __('Products');
    }

    public static function getModelLabel(): string
    {
        return __('Unit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Units');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Unit category')
                    ->relationship('category', 'name')
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->scopedUnique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('ratio')
                    ->required()
                    ->numeric()
                    ->rules(['gt:0'])
                    ->default(1)
                    ->helperText(__('How many reference units one of this unit equals. The first unit of a category becomes its reference unit with a ratio of 1.')),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Unit category')
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('ratio')
                    ->numeric(),
                IconColumn::make('is_reference')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('category_id')
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (Unit $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(UpdateUnit::class)->handle($record, $data),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (Unit $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteUnit::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnits::route('/'),
        ];
    }
}
