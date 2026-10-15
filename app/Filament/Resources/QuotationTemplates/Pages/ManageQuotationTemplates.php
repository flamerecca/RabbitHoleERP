<?php

namespace App\Filament\Resources\QuotationTemplates\Pages;

use App\Actions\Sales\SaveQuotationTemplate;
use App\Filament\Resources\QuotationTemplates\QuotationTemplateResource;
use App\Filament\Support\DomainAction;
use App\Filament\Support\TeamOptions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageQuotationTemplates extends ManageRecords
{
    protected static string $resource = QuotationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, CreateAction $action) => DomainAction::run(
                    fn () => app(SaveQuotationTemplate::class)->handle(TeamOptions::team(), $data),
                    fn () => $action->halt(),
                )),
        ];
    }
}
