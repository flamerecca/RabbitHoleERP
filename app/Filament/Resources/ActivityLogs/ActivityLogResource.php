<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Enums\ActivityEvent;
use App\Enums\TeamRole;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Support\TeamOptions;
use App\Models\ActivityLog;
use App\Models\Team;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return __('Master data');
    }

    public static function getModelLabel(): string
    {
        return __('Activity log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Activity logs');
    }

    /**
     * Only team owners and admins may read the activity log.
     */
    public static function canAccess(): bool
    {
        $team = Filament::getTenant();
        $role = $team instanceof Team ? auth()->user()?->teamRole($team) : null;

        return $role !== null && $role->isAtLeast(TeamRole::Admin);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('causer'))
            ->columns([
                TextColumn::make('c10')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->placeholder(__('System')),
                TextColumn::make('c3')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (ActivityEvent $state): string => __("activity.{$state->value}"))
                    ->color(fn (ActivityEvent $state): string => match ($state) {
                        ActivityEvent::Created => 'success',
                        ActivityEvent::Updated => 'info',
                        ActivityEvent::Deleted => 'danger',
                    }),
                TextColumn::make('c6')
                    ->label('Record')
                    ->formatStateUsing(fn (string $state, ActivityLog $record): string => __("record.{$state}").' '.$record->c8),
                TextColumn::make('c4')
                    ->label('Changed item')
                    ->formatStateUsing(fn (string $state): string => __("record.{$state}")),
                TextColumn::make('c9')
                    ->label('Changes')
                    ->state(fn (ActivityLog $record): array => static::describeChanges($record))
                    ->listWithLineBreaks()
                    ->limitList(5)
                    ->expandableLimitedList(),
            ])
            ->defaultSort('c10', 'desc')
            ->filters([
                SelectFilter::make('c6')
                    ->label('Record')
                    ->options(fn () => collect(['product', 'purchase_order', 'goods_receipt', 'purchase_return', 'sales_order', 'shipment', 'sales_return', 'consignment_hold', 'warehouse_transfer', 'stock_take', 'material_requisition'])->mapWithKeys(fn (string $type) => [$type => __("record.{$type}")])->all()),
                Filter::make('document')
                    ->schema([
                        Hidden::make('document_type'),
                        Hidden::make('document_id'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['document_type'] ?? null, fn (Builder $query, string $type) => $query->where('c6', $type)->where('c7', $data['document_id'] ?? 0))),
                SelectFilter::make('c2')
                    ->label('User')
                    ->options(fn () => TeamOptions::members()),
                SelectFilter::make('c3')
                    ->label('Event')
                    ->options(fn () => collect(ActivityEvent::cases())->mapWithKeys(fn (ActivityEvent $event) => [$event->value => __("activity.{$event->value}")])->all()),
                Filter::make('c10')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('c10', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('c10', '<=', $date))),
            ]);
    }

    /**
     * Describe each changed attribute as "attribute: old → new".
     *
     * @return list<string>
     */
    public static function describeChanges(ActivityLog $record): array
    {
        $lines = [];

        foreach ($record->c9 as $attribute => $change) {
            $old = static::formatValue($change['old'] ?? null);
            $new = static::formatValue($change['new'] ?? null);
            $label = __(str($attribute)->replace('_', ' ')->ucfirst()->toString());

            $lines[] = match ($record->c3) {
                ActivityEvent::Created => "{$label}: {$new}",
                ActivityEvent::Deleted => "{$label}: {$old}",
                ActivityEvent::Updated => "{$label}: {$old} → {$new}",
            };
        }

        return $lines;
    }

    /**
     * Format a raw attribute value for display.
     */
    protected static function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null => '—',
            is_bool($value) => $value ? __('Yes') : __('No'),
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
        };
    }

    /**
     * Get the activity log URL filtered to one product or document.
     */
    public static function urlFor(string $documentType, int $documentId): string
    {
        return static::getUrl('index', ['filters' => ['document' => ['document_type' => $documentType, 'document_id' => $documentId]]]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
