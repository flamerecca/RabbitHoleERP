<?php

namespace App\Support\Rbac;

use App\Enums\SiteAction;
use App\Enums\SiteResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SiteResourceAuthorizer
{
    /**
     * Decide the Filament resource abilities of a site resource by the site permissions of the user.
     *
     * Returns null for anything else so the policies and gates of the application keep deciding.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __invoke(User $user, string $ability, array $arguments): ?bool
    {
        $action = $this->action($ability);
        $model = $arguments[0] ?? null;
        $class = $model instanceof Model ? $model::class : (is_string($model) ? $model : null);

        if ($action === null || $class === null) {
            return null;
        }

        $resource = SiteResource::tryFromModel($class);

        if ($resource === null) {
            return null;
        }

        return $user->checkPermissionTo($resource->permission($action));
    }

    /**
     * Map a Filament resource ability to the site action it requires.
     */
    protected function action(string $ability): ?SiteAction
    {
        return match ($ability) {
            'viewAny', 'view' => SiteAction::View,
            'create' => SiteAction::Create,
            'update' => SiteAction::Update,
            'delete', 'deleteAny' => SiteAction::Delete,
            default => null,
        };
    }
}
