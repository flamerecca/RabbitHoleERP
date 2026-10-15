<?php

namespace App\Filament\Resources\QuotationTemplates;

use App\Actions\Sales\DeleteQuotationTemplate;
use App\Actions\Sales\SaveQuotationTemplate;
use App\Filament\Resources\QuotationTemplates\Pages\ManageQuotationTemplates;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use App\Models\QuotationTemplate;
use App\Models\QuotationTemplateItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class QuotationTemplateResource extends Resource
{
    protected static ?string $model = QuotationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Sales');
    }

    public static function getModelLabel(): string
    {
        return __('Quotation template');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Quotation templates');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('team_id', TeamOptions::team()->id)),
                Toggle::make('is_active')
                    ->default(true),
                Repeater::make('items')
                    ->label(__('Lines'))
                    ->addActionLabel(__('Add line'))
                    ->minItems(1)
                    ->required()
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn () => TeamOptions::products())
                            ->searchable()
                            ->required()
                            ->distinct(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->rules(['gt:0'])
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label(__('Lines'))
                    ->counts('items'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->mountUsing(fn (Schema $schema, QuotationTemplate $record) => $schema->fill([
                        'name' => $record->name,
                        'is_active' => $record->is_active,
                        'items' => $record->items->map(fn (QuotationTemplateItem $item) => [
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                        ])->all(),
                    ]))
                    ->using(fn (QuotationTemplate $record, array $data, EditAction $action) => DomainAction::run(
                        fn () => app(SaveQuotationTemplate::class)->handle(TeamOptions::team(), $data, $record),
                        fn () => $action->halt(),
                    )),
                DeleteAction::make()
                    ->using(fn (QuotationTemplate $record, DeleteAction $action) => DomainAction::run(
                        fn () => app(DeleteQuotationTemplate::class)->handle($record),
                        fn () => $action->halt(),
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQuotationTemplates::route('/'),
        ];
    }
}
