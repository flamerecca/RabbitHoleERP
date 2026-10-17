<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ExpiryStatus;
use App\Filament\Support\TeamOptions;
use App\Support\Expiry;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('expiry_date')
                    ->label(__('Expiry date'))
                    ->date('Y-m-d')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('sku')
            ->filters([
                TernaryFilter::make('is_active'),
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
                SelectFilter::make('expiry_status')
                    ->label(__('Expiry status'))
                    ->options(fn (): array => collect(ExpiryStatus::cases())->mapWithKeys(fn (ExpiryStatus $status) => [$status->value => __("expiry.{$status->value}")])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? Expiry::applyStatusFilter($query, ExpiryStatus::from($data['value']), TeamOptions::team()->expiry_warning_days)
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
