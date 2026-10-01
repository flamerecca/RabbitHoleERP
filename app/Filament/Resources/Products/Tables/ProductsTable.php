<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category'),
                TextColumn::make('unit.code')
                    ->label('Stock unit'),
                TextColumn::make('reorder_point')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('sku')
            ->filters([
                TernaryFilter::make('is_active'),
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
