<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\ViewToken; // if still used; otherwise check card ownership
use App\Models\Device;
use Carbon\Carbon;

class CheckPersonalToken
{
    private function computeFingerprint(Request $request): string
    {
        $ua   = $request->header('User-Agent','');
        $lang = $request->header('Accept-Language','');
        $tz   = $request->header('Time-Zone','');
        return hash('sha256', $ua.'|'.$lang.'|'.$tz);
    }

    public function handle(Request $request, Closure $next)
    {
        $tokenPlain = $request->bearerToken() ?? $request->cookie('personal_token');
        if (!$tokenPlain) abort(403,'Unauthorized');

        $tokenModel = PersonalAccessToken::findToken($tokenPlain);
        if (!$tokenModel) abort(403,'Invalid token');

        // get linked device record (if any)
        $device = Device::where('personal_access_token_id',$tokenModel->id)->first();

        $finger = $this->computeFingerprint($request);

        if (!$device) {
            // No device record (maybe user logged in on a different browser) -> create one and bind
            $device = Device::create([
                'name' => $request->header('X-Device-Name') ?? 'unknown',
                'device_type' => $request->header('X-Device-Type') ?? null,
                'is_active' => true,
                'user_id' => $tokenModel->tokenable->id ?? null,
                'personal_access_token_id' => $tokenModel->id,
                'bound_fingerprint' => $finger,
                'last_used_at' => Carbon::now(),
            ]);
            // allow first-time binding
            // set cookie again with same token if you'd like
            cookie()->queue(cookie('personal_token', $tokenPlain, 60*24*365*2, null, null, true, true, false, 'Lax'));
            return $next($request);
        }

        // if device exists but not active
        if (!$device->is_active) abort(403,'Device revoked');

        // fingerprint mismatch?
        if (!hash_equals($device->bound_fingerprint ?? '', $finger)) {
            // Option A: reject
            abort(403,'Token bound to different device');

            // Option B (alternative UX): ask for PIN or send confirmation to owner
            // implement fallback here if required
        }

        // update last used
        $device->last_used_at = Carbon::now();
        $device->save();

        // authenticate the token owner as current user
        if ($tokenModel->tokenable) {
            auth()->loginUsingId($tokenModel->tokenable->id);
        }

        return $next($request);
    }
}
