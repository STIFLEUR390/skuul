<?php

namespace App\Http\Middleware;

use App\Models\Installation;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetApplicationLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) config('app.locale');

        try {
            $userLocale = $request->user()?->locale;

            if (is_string($userLocale) && $userLocale !== '') {
                $locale = $userLocale;
            } else {
                $installedLocale = Installation::withoutGlobalScopes()->value('locale');

                if (is_string($installedLocale) && $installedLocale !== '') {
                    $locale = $installedLocale;
                }
            }
        } catch (Throwable) {
            // The installer must still render before the application schema exists.
        }

        if (!array_key_exists($locale, config('app.supported_locales', []))) {
            $locale = (string) config('app.fallback_locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        Number::useLocale($locale);

        return $next($request);
    }
}
