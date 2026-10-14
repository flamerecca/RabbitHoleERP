<?php

namespace App\Filament\Pages;

use App\Actions\Hr\UpdateHrSettings;
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
class HrSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('Human resources');
    }

    public static function getNavigationLabel(): string
    {
        return __('HR settings');
    }

    public function getTitle(): string
    {
        return __('HR settings');
    }

    /**
     * Only team owners and admins may change the HR settings.
     */
    public static function canAccess(): bool
    {
        $team = Filament::getTenant();
        $role = $team instanceof Team ? auth()->user()?->teamRole($team) : null;

        return $role !== null && $role->isAtLeast(TeamRole::Admin);
    }

    public function mount(): void
    {
        $this->form->fill(['hr_enabled' => TeamOptions::team()->hr_enabled]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Toggle::make('hr_enabled')
                    ->label(__('Enable the HR module'))
                    ->helperText(__('Turning this off hides the HR pages. Existing HR data is kept.')),
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

        app(UpdateHrSettings::class)->handle(TeamOptions::team(), (bool) $data['hr_enabled']);

        Notification::make()->success()->title(__('Saved.'))->send();
    }
}
