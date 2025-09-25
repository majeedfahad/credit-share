<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عرض البطاقة - {{ $card->name ?? 'بطاقة' }}</title>
    <style>
        :root{
            --bg:#0b1320; --card:#111827; --muted:#1f2a44; --text:#eaf0ff;
        }
        *{box-sizing:border-box}
        body{font-family: system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; background:var(--bg); color:var(--text); margin:0}
        .container{max-width:900px; margin:20px auto; padding:0 12px;}
        .card{background:var(--card); border:1px solid var(--muted); border-radius:12px; padding:14px; margin-bottom:14px}
        .badge{background:var(--muted); padding:4px 10px; border-radius:999px; font-size:12px; display:inline-block}
        textarea,input,button{background:#0e1626; color:var(--text); border:1px solid var(--muted); border-radius:10px; padding:8px}
        button{cursor:pointer}
        a{color:#8ab4ff}

        /* --- Month header --- */
        .month-title{display:flex; justify-content:space-between; align-items:center; margin:0 0 6px 0}
        .month-sum{font-weight:700}

        /* --- TXN row / accordion --- */
        .txn{background:#0f1626; border:1px solid var(--muted); border-radius:12px; margin:10px 0;}
        .txn summary{list-style:none; cursor:pointer; display:flex; gap:12px; align-items:center; padding:12px 14px;}
        .txn summary::-webkit-details-marker{display:none}
        .txn .row{display:flex; gap:12px; align-items:center; width:100%;}
        .txn .amt{font-weight:700; white-space:nowrap;}
        .txn .merchant{flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; opacity:.9}
        .txn .dt{font-size:12px; opacity:.75; white-space:nowrap;}
        .txn .chev{margin-inline-start:auto; transition:transform .2s ease; opacity:.7}
        .txn[open] .chev{transform:rotate(90deg)}
        .txn .body{padding:0 14px 12px 14px; border-top:1px solid var(--muted)}
        .txn .kv{margin:8px 0}
        .txn .kv b{opacity:.85}

        /* top card layout */
        .top{display:flex; justify-content:space-between; align-items:center; gap:12px}
        .bal{font-size:28px; font-weight:800}
        .cardname{font-size:20px; font-weight:700}
        .sub{opacity:.7; font-size:13px}
    </style>
</head>
<body>
<div class="container">

    {{-- البطاقة + الرصيد --}}
    <div class="card">
        <div class="top">
            <div class="bal">
                {{ $currentBalance !== null ? number_format($currentBalance,2) . ' ' . ($card->currency ?? 'SAR') : '—' }}
                <div class="badge" style="margin-top:6px">الرصيد الحالي</div>
            </div>
            <div style="text-align:right">
                <div class="cardname">
                    {{ $card->name ?? 'بطاقة' }}
                    @if($card->last4) (•••• {{ $card->last4 }}) @endif
                </div>
                <div class="sub">العملة: {{ $card->currency }}</div>
            </div>
        </div>
    </div>

    {{-- الشهور والعمليات --}}
    @foreach($byMonth as $ym => $data)
        @php
            [$y,$m] = explode('-',$ym);
            $title = \Carbon\Carbon::createFromDate($y,$m,1)->translatedFormat('F Y');
        @endphp

        <div class="card">
            <div class="month-title">
                <h3 style="margin:0">{{ $title }}</h3>
                <div class="month-sum">مجموع المدفوعات: {{ number_format($data['total'],2) }} {{ $card->currency }}</div>
            </div>
            <hr style="border:none; border-top:1px solid var(--muted); margin:10px 0 0 0">

            @foreach($data['items'] as $p)
                @php
                    $dt = optional($p->received_at ?? $p->created_at);
                    $dtText = $dt ? $dt->format('Y-m-d H:i') : '—';
                @endphp

                <details class="txn">
                    <summary>
                        <div class="row">
                            <div class="amt">{{ number_format($p->amount,2) }} {{ $card->currency }}</div>
                            <div class="merchant">{{ $p->merchant ?? ($p->description ?? 'عملية') }}</div>
                            <div class="dt">{{ $dtText }}</div>
                            <div class="chev">›</div>
                        </div>
                    </summary>

                    <div class="body">
                        <div class="kv"><b>الوصف:</b> {{ $p->description ?? '—' }}</div>
                        <div class="kv"><b>التاجر:</b> {{ $p->merchant ?? '—' }}</div>
                        <div class="kv"><b>المبلغ:</b> {{ number_format($p->amount,2) }} {{ $card->currency }}</div>
                        <div class="kv"><b>الرصيد بعد العملية:</b>
                            {{ $p->balance_after !== null ? number_format($p->balance_after,2).' '.$card->currency : '—' }}
                        </div>
                        <div class="kv"><b>البطاقة:</b>
                            @if($p->card_last4) (•••• {{ $p->card_last4 }}) @else — @endif
                            @if(!empty($p->card_type)) <span class="badge" style="margin-inline-start:6px">{{ $p->card_type }}</span> @endif
                        </div>
                        <div class="kv"><b>التاريخ:</b> {{ $dtText }}</div>

                        {{-- ملاحظة --}}
                        <form method="post" action="{{ url('/family/payments/'.$p->id.'/note') }}" style="margin-top:10px">
                            @csrf
                            <textarea name="note" rows="2" placeholder="أضف ملاحظة..." style="width:100%">{{ old('note',$p->note) }}</textarea>
                            <div style="margin-top:6px; display:flex; gap:8px; align-items:center">
                                <button type="submit">حفظ الملاحظة</button>
                                @if(session('ok')) <small style="color:#7cf28a">{{ session('ok') }}</small> @endif
                            </div>
                        </form>

                        {{-- النص الخام (اختياري للرجوع) --}}
                        @if($p->raw_text)
                            <div class="kv" style="opacity:.7; margin-top:6px">
                                <b>النص الأصلي:</b> {{ $p->raw_text }}
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @endforeach

    <div style="opacity:.7; text-align:center; margin:16px 0 24px">
        ملاحظة: هذا الرابط مربوط بجهازك أول زيارة — إذا شارك الرابط فسيحتاج الشخص جهازك لتجاوز binding.
        اضغط صاحب الحساب لإبطال الروابط عند الحاجة.
    </div>
</div>
</body>
</html>
