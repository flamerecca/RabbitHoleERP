<?php

use App\Enums\PermissionProfile;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

$registerTeamRoutes = require __DIR__.'/api/team.php';

foreach (['v1' => PermissionProfile::Legacy, 'v2' => PermissionProfile::Current] as $version => $profile) {
    Route::prefix("{$version}/{team}")
        ->middleware(['auth:sanctum', EnsureTeamMembership::class])
        ->scopeBindings()
        ->name("api.{$version}.")
        ->group(fn () => $registerTeamRoutes($profile));
}
