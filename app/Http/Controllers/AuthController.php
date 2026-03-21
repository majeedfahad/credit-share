<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = $data['phone'];
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'رقم الجوال غير مسجل']);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in cache for 30 minutes
        Cache::put("otp:{$phone}", $otp, now()->addMinutes(30));

        // Send via Telegram
        $telegram = new TelegramService();
        $message = "رقم الجوال {$phone} يحاول الدخول\nرمز التحقق: {$otp}";
        $sent = $telegram->sendMessage($message);

        if (!$sent) {
            Log::error('OTP: Failed to send Telegram message', ['phone' => $phone]);
            return back()->withErrors(['phone' => 'فشل في إرسال رمز التحقق']);
        }

        return redirect()->route('verify-otp')->with('phone', $phone);
    }

    public function showVerifyOtp(Request $request)
    {
        $phone = session('phone') ?? $request->old('phone');
        if (!$phone) {
            return redirect()->route('login');
        }
        return view('auth.verify-otp', ['phone' => $phone]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $phone = $data['phone'];
        $otp = $data['otp'];

        $cachedOtp = Cache::get("otp:{$phone}");

        if (!$cachedOtp || $cachedOtp !== $otp) {
            return back()
                ->withInput(['phone' => $phone])
                ->with('phone', $phone)
                ->withErrors(['otp' => 'رمز التحقق غير صحيح أو منتهي']);
        }

        // Delete OTP (single-use)
        Cache::forget("otp:{$phone}");

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return redirect()->route('login')->withErrors(['phone' => 'رقم الجوال غير مسجل']);
        }

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
