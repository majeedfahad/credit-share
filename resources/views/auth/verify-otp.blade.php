@extends("layouts.app")
@section("title", "رمز التحقق")

@section("content")
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="glass rounded-3xl p-8 w-full max-w-sm shadow-xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">🔐</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">رمز التحقق</h1>
            <p class="text-slate-500 text-sm mt-1">أدخل الرمز المرسل عبر تيليقرام</p>
        </div>

        @if(isset($errors) && $errors->any())
        <div class="bg-red-50 text-red-600 text-sm p-3 rounded-xl mb-4">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('verify-otp.submit') }}">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            <div class="mb-6">
                <label class="block text-sm text-slate-600 mb-2">رمز التحقق</label>
                <input type="text" name="otp" 
                       class="w-full border border-slate-200 rounded-xl p-3 text-2xl text-center tracking-[0.5em] focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="000000" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            </div>
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl p-3 font-medium hover:opacity-90 transition-opacity">
                تحقق
            </button>
            <a href="{{ route('login') }}" class="block text-center text-indigo-500 text-sm mt-4 hover:underline">
                رجوع لتسجيل الدخول
            </a>
        </form>
    </div>
</div>
@endsection
