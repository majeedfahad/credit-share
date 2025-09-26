<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول برقم الجوال</title>
    <style>
        body{font-family:system-ui, -apple-system, "Segoe UI", Tahoma; background:#0b1320; color:#eaf0ff; display:flex; align-items:center; justify-content:center; height:100vh; margin:0}
        .card{background:#111827; padding:20px; border-radius:12px; width:360px; border:1px solid #1f2a44}
        input{width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #1f2a44; background:#0e1626; color:#eaf0ff}
        button{width:100%; padding:10px; border-radius:8px; border:none; background:#274c8a; color:white; cursor:pointer}
        .note{opacity:.8; font-size:13px}
        .error{color:#ff7b7b; font-size:13px}
    </style>
</head>
<body>
<div class="card">
    <h3 style="margin:0 0 8px 0">تسجيل الدخول</h3>

    @if($errors->any())
    <div class="error">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ url('/login') }}">
        @csrf
        <label>رقم الجوال</label>
        <input name="phone" type="text" placeholder="مثال: 9665xxxxxxxx" required>

        <label>كلمة المرور</label>
        <input name="password" type="password" placeholder="كلمة المرور" required>

        <label>اسم جهازك (اختياري)</label>
        <input name="device_name" type="text" placeholder="iPhone Abd">

        <div style="margin-top:8px">
            <button type="submit">تسجيل الدخول وتذكر هذا الجهاز</button>
        </div>
    </form>

    <div class="note" style="margin-top:10px">
        إذا لم تستلم بيانات الدخول، اطلب من صاحب الحساب إعادة إصدار بيانات جديدة من لوحة الإدارة.
    </div>
</div>
</body>
</html>
