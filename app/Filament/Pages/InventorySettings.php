<?php

namespace App\Filament\Pages;

use App\Actions\Products\UpdateInventorySettings;
use App\Enums\TeamRole;
use App\Filament\Support\TeamOptions;
use App\Models\Team;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class InventorySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 9;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('Inventory settings');
    }

    public function getTitle(): string
    {
        return __('Inventory settings');
    }

    /**
     * Only team owners and admins may change the inventory settings.
     */
    public static function canAccess(): bool
    {
        $team = Filament::getTenant();
        $role = $team instanceof Team ? auth()->user()?->teamRole($team) : null;

        return $role !== null && $role->isAtLeast(TeamRole::Admin);
    }

    public function mount(): void
    {
        $this->form->fill(['require_lot_tracking' => TeamOptions::team()->require_lot_tracking]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Toggle::make('require_lot_tracking')
                    ->label('Require lot tracking for all products')
                    ->helperText(__('Turning this on switches every product without stock movements to lot tracking, unless its category says otherwise. Turning it off does not switch products back.')),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('Save'))
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(UpdateInventorySettings::class)->handle(TeamOptions::team(), (bool) $data['require_lot_tracking']);

        Notification::make()->success()->title(__('Saved.'))->send();
    }
}
