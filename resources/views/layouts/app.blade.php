<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config("app.name", "Pay") }} - @yield("title", "لوحة التحكم")</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ["IBM Plex Sans Arabic", "sans-serif"],
                    },
                }
            }
        }
    </script>
    <style>
        * { font-family: "IBM Plex Sans Arabic", sans-serif; }
        .glass { background: rgba(255,255,255,0.8); backdrop-filter: blur(12px); }
        .card-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-gradient-2 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen">
    @yield("content")
    @stack("scripts")
</body>
</html>
