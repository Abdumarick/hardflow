<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ApplySelectedBusinessLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = Business::query()->whereKey($request->session()->get('tenant.business_id'))->where('is_active', true)->first();
        if (! $business || (! $request->user()->is_super_admin && ! $request->user()->hasActiveMembership($business))) {
            return $next($request);
        }

        $previousLocale = App::currentLocale();
        $previousTimezone = date_default_timezone_get();
        $preferredLocale = $request->session()->get('ui.locale');
        $locale = in_array($preferredLocale, ['en', 'sw'], true) ? $preferredLocale : (in_array($business->locale, ['en', 'sw'], true) ? $business->locale : config('app.fallback_locale'));
        $timezone = in_array($business->timezone, timezone_identifiers_list(), true) ? $business->timezone : config('app.timezone');
        App::setLocale($locale);
        Carbon::setLocale($locale);
        date_default_timezone_set($timezone);

        try {
            return $next($request);
        } finally {
            App::setLocale($previousLocale);
            Carbon::setLocale($previousLocale);
            date_default_timezone_set($previousTimezone);
        }
    }
}
