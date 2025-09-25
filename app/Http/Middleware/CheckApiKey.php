<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization');

        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            return response()->json(['message'=>'Unauthorized'], 401);
        }

        $token = substr($auth,7);
        $device = Device::where('api_token',$token)->where('is_active',true)->first();

        if (!$device) {
            return response()->json(['message'=>'Invalid token'], 403);
        }

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
