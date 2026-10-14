<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductDiscount;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductDiscountsRelationManager extends RelationManager
{
    // validation duplicated from ProductController::storeDiscount and updateDiscount, see docs/architecture.md section 4.8

    protected static string $relationship = 'discounts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Discounts');
    }

    public static function getModelLabel(): string
    {
        return __('Discount');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('discount_rate')
                    ->numeric()
                    ->minValue(0.0001)
                    ->maxValue(100)
                    ->suffix('%')
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
                DatePicker::make('starts_on')
                    ->required()
                    ->rules([
                        fn (RelationManager $livewire, Get $get, ?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($livewire, $get, $record): void {
                            if ($get('is_active') && filled($get('ends_on')) && ProductDiscount::query()
                                ->overlapping((int) $livewire->getOwnerRecord()->getKey(), (string) $value, (string) $get('ends_on'), $record?->getKey())
                                ->exists()) {
                                $fail(__('The discount period overlaps another active discount of the product.'));
                            }
                        },
                    ]),
                DatePicker::make('ends_on')
                    ->required()
                    ->afterOrEqual('starts_on'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('discount_rate')
                    ->numeric()
                    ->suffix('%'),
                TextColumn::make('starts_on')
                    ->date(),
                TextColumn::make('ends_on')
                    ->date(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('starts_on', 'desc')
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
