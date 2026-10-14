<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    /**
     * The locales the admin panel can be displayed in.
     *
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['zh_TW', 'en'];

    /**
     * The session key holding the admin panel locale chosen by the user.
     */
    public const SESSION_KEY = 'admin_locale';

    /**
     * Apply the admin panel locale chosen by the user, falling back to the application locale.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY);

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
