@extends("layouts.app")
@section("title", "تسجيل الدخول")

@section("content")
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="glass rounded-3xl p-8 w-full max-w-sm shadow-xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">💳</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Pay</h1>
            <p class="text-slate-500 text-sm mt-1">تتبع مصروفاتك بسهولة</p>
        </div>

        @if(isset($errors) && $errors->any())
        <div class="bg-red-50 text-red-600 text-sm p-3 rounded-xl mb-4">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-6">
                <label class="block text-sm text-slate-600 mb-2">رقم الجوال</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" 
                       class="w-full border border-slate-200 rounded-xl p-3 text-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="05xxxxxxxx" required autofocus>
            </div>
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl p-3 font-medium hover:opacity-90 transition-opacity">
                إرسال رمز التحقق
            </button>
            <p class="text-center text-slate-400 text-xs mt-4">سيتم إرسال رمز التحقق عبر تيليقرام</p>
        </form>
    </div>
</div>
@endsection
