<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetAdminLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchAdminLocaleController extends Controller
{
    /**
     * Remember the admin panel locale for the session and go back to the previous page.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put(SetAdminLocale::SESSION_KEY, $locale);

        return redirect()->back(fallback: '/admin');
    }
}
