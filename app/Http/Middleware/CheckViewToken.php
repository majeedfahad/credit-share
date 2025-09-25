<?php

namespace App\Http\Middleware;

use App\Models\ViewToken;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckViewToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieToken = $request->cookie('family_view_token');
        $queryToken = $request->query('token');
        $token = $cookieToken ?? $queryToken;

        if (!$token) {
            abort(403,'Unauthorized');
        }

        $view = ViewToken::where('token', $token)->where('is_active', true)->first();
        if (!$view) {
            abort(403,'Unauthorized');
        }

        // if route has card param ensure token is for that card
        $routeCard = $request->route('card');
        if ($routeCard && $view->card_id && $view->card_id != $routeCard->id) {
            abort(403,'This token is not valid for the requested card');
        }

        $finger = $this->computeFingerprint($request);

        if (!$view->bound_fingerprint) {
            // Bind on first use
            $view->bound_fingerprint = $finger;
            $view->bound_at = Carbon::now();
            $view->save();
            // set long-lived HttpOnly cookie (2 years)
            cookie()->queue(cookie('family_view_token', $token, 60*24*365*2, null, null, true, true, false, 'Lax'));
            return $next($request);
        }

        if (!hash_equals($view->bound_fingerprint, $finger)) {
            abort(403,'Token bound to different device');
        }

        return $next($request);
    }

    private function computeFingerprint(Request $request): string
    {
        $ua   = $request->header('User-Agent','');
        $lang = $request->header('Accept-Language','');
        $tz   = $request->header('Time-Zone','');
        // not including IP because mobile IP may change
        return hash('sha256', $ua.'|'.$lang.'|'.$tz);
    }
}
