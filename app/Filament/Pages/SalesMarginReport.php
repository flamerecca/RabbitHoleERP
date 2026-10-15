<?php

namespace App\Filament\Pages;

use App\Actions\Sales\BuildSalesMarginReport;
use App\Actions\Sales\RenderSalesMarginReportPdf;
use App\Filament\Support\TeamOptions;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesMarginReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return __('Sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('Sales margin report');
    }

    public function getTitle(): string
    {
        return __('Sales margin report');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('Returns are attributed to the period of the original shipment.').' '.__('Amounts are in the base currency.');
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
            ->records(fn (array $filters): array => $this->report($filters['period'] ?? [])['rows'] ?? [])
            ->paginated(false)
            ->filters([
                Filter::make('period')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label(__('From'))
                            ->default(now()->startOfMonth())
                            ->required(),
                        DatePicker::make('date_to')
                            ->label(__('To'))
                            ->default(now())
                            ->required(),
                        Select::make('group_by')
                            ->label(__('Group by'))
                            ->options([
                                'product' => __('Product'),
                                'customer' => __('Customer'),
                                'sales_order' => __('Sales order'),
                                'month' => __('Month'),
                            ])
                            ->default('product')
                            ->required(),
                        Select::make('customer_id')
                            ->label(__('Customer'))
                            ->options(fn () => TeamOptions::customers())
                            ->searchable(),
                        Select::make('product_id')
                            ->label(__('Product'))
                            ->options(fn () => TeamOptions::products())
                            ->searchable(),
                        Select::make('warehouse_id')
                            ->label(__('Warehouse'))
                            ->options(fn () => TeamOptions::warehouses()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ], layout: FiltersLayout::AboveContent)
            ->columns([
                TextColumn::make('label')
                    ->label(__('Group')),
                TextColumn::make('shipped_revenue')
                    ->label(__('Shipped revenue'))
                    ->numeric(4),
                TextColumn::make('returned_revenue')
                    ->label(__('Returned revenue'))
                    ->numeric(4),
                TextColumn::make('net_revenue')
                    ->label(__('Net revenue'))
                    ->numeric(4),
                TextColumn::make('net_cost')
                    ->label(__('Net cost'))
                    ->numeric(4),
                TextColumn::make('gross_margin')
                    ->label(__('Gross margin'))
                    ->numeric(4),
                TextColumn::make('margin_rate')
                    ->label(__('Margin rate'))
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '-' : number_format((float) $state * 100, 2).'%'),
            ])
            ->emptyStateHeading(__('No data in the period.'))
            ->headerActions([
                Action::make('downloadPdf')
                    ->label(__('Download PDF'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (): ?StreamedResponse => $this->downloadPdf()),
            ]);
    }

    /**
     * Build the report of the chosen period, or tell the user why it cannot be built.
     *
     * @param  array<string, mixed>  $period
     * @return array{rows: array<string, array<string, string|null>>, totals: array<string, string|null>, criteria: array{from: CarbonImmutable, to: CarbonImmutable, group_by: string}}|null
     */
    protected function report(array $period): ?array
    {
        if (blank($period['date_from'] ?? null) || blank($period['date_to'] ?? null)) {
            return null;
        }

        $criteria = [
            'from' => CarbonImmutable::parse($period['date_from']),
            'to' => CarbonImmutable::parse($period['date_to']),
            'group_by' => (string) ($period['group_by'] ?? 'product'),
        ];

        try {
            $result = app(BuildSalesMarginReport::class)->handle(TeamOptions::team(), $criteria['from'], $criteria['to'], $criteria['group_by'], [
                'customer_id' => filled($period['customer_id'] ?? null) ? (int) $period['customer_id'] : null,
                'product_id' => filled($period['product_id'] ?? null) ? (int) $period['product_id'] : null,
                'warehouse_id' => filled($period['warehouse_id'] ?? null) ? (int) $period['warehouse_id'] : null,
            ]);
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title((string) collect($exception->errors())->flatten()->first())->send();

            return null;
        }

        return [
            'rows' => collect($result['rows'])->keyBy('key')->all(),
            'totals' => $result['totals'],
            'criteria' => $criteria,
        ];
    }

    /**
     * Download the report of the current filters as a PDF.
     */
    protected function downloadPdf(): ?StreamedResponse
    {
        $report = $this->report($this->tableFilters['period'] ?? []);

        if ($report === null) {
            return null;
        }

        $render = app(RenderSalesMarginReportPdf::class);
        $pdf = $render->handle(TeamOptions::team(), $report['criteria'], ['rows' => array_values($report['rows']), 'totals' => $report['totals']]);

        return response()->streamDownload(fn () => print($pdf), $render->filename($report['criteria']), ['Content-Type' => 'application/pdf']);
    }
}
