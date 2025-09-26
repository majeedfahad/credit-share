<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Device;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.phone-login');
    }
    // POST /login
    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        // find user by phone
        $user = User::where('phone', $data['phone'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['phone' => 'بيانات تسجيل الدخول غير صحيحة']);
        }

        // login the user
        Auth::login($user);

        // create a personal access token (Sanctum)
        // Requires laravel/sanctum installed and migrated
        $deviceName = $data['device_name'] ?? 'browser';
        $tokenResult = $user->createToken($deviceName);
        $plainTextToken = $tokenResult->plainTextToken;

        // set HttpOnly cookie (long lived): 2 years in minutes
        $cookie = cookie('personal_token', $plainTextToken, 60*24*365*2, null, null, true, true, false, 'Lax');

        // redirect to home or intended page
        $cardId = $user->default_card_id ?? \App\Models\Card::where('is_active', true)->value('id');

        if (!$cardId) {
            // لو مافي بطاقة، ودّه لصفحة بسيطة تشرح أن البطاقة غير موجودة
            return redirect('/no-card')->withCookie($cookie);
        }

        return redirect("/family/view/{$cardId}")->withCookie($cookie);
    }

    // POST /logout
    public function logout(Request $request)
    {
        $token = $request->bearerToken() ?? $request->cookie('personal_token');
        if ($token) {
            $tokenModel = PersonalAccessToken::findToken($token);
            if ($tokenModel) {
                $tokenModel->delete();
            }
        }
        Cookie::queue(Cookie::forget('personal_token'));
        Auth::logout();
        return redirect('/login');
    }

    private function computeFingerprint(Request $request): string
    {
        $ua   = $request->header('User-Agent','');
        $lang = $request->header('Accept-Language','');
        $tz   = $request->header('Time-Zone','');
        return hash('sha256', $ua.'|'.$lang.'|'.$tz);
    }
}
