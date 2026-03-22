<div class="payment-row" onclick="togglePaymentDetail(this)">
    {{-- Summary Row --}}
    <div class="flex items-center justify-between px-4 py-2.5 hover:bg-white/50 transition-colors cursor-pointer">
        <div class="flex items-center gap-2.5 min-w-0 flex-1">
            <span class="text-base w-6 text-center shrink-0">{{ $payment->category?->icon ?? '💳' }}</span>
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-700 truncate">{{ $payment->merchant ?: ($payment->description ?: 'عملية دفع') }}</p>
                <p class="text-[10px] text-slate-400">{{ $payment->received_at?->format('d M H:i') }} · {{ $payment->card->last4 ?? '' }}</p>
            </div>
        </div>
        <span class="text-xs font-bold text-red-500 shrink-0 mr-2">-{{ number_format($payment->amount, 2) }}</span>
    </div>
    {{-- Detail (hidden by default) --}}
    <div class="payment-detail hidden bg-slate-50/80 px-4 py-3 mx-3 mb-2 rounded-xl">
        <div class="space-y-1.5 text-[11px]">
            @if($payment->merchant)
            <div class="flex justify-between">
                <span class="text-slate-400">التاجر</span>
                <span class="text-slate-600">{{ $payment->merchant }}</span>
            </div>
            @endif
            <div class="flex justify-between">
                <span class="text-slate-400">المبلغ</span>
                <span class="text-slate-600">{{ number_format($payment->amount, 2) }} ر.س</span>
            </div>
            @if($payment->balance_after)
            <div class="flex justify-between">
                <span class="text-slate-400">الرصيد بعدها</span>
                <span class="text-slate-600">{{ number_format($payment->balance_after, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between">
                <span class="text-slate-400">البطاقة</span>
                <span class="text-slate-600">{{ $payment->card->name ?? '' }} ({{ $payment->card->last4 ?? '' }})</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">التصنيف</span>
                <span class="text-slate-600">
                    @if($payment->category)
                        {{ $payment->category->icon }} {{ $payment->category->name_ar }}
                    @else
                        <button onclick="event.stopPropagation(); openCategoryModal({{ $payment->id }}, '{{ addslashes($payment->merchant) }}')"
                                class="text-indigo-500 hover:underline">صنّف</button>
                    @endif
                </span>
            </div>
            @if($payment->description)
            <div class="flex justify-between">
                <span class="text-slate-400">الوصف</span>
                <span class="text-slate-600">{{ $payment->description }}</span>
            </div>
            @endif
            @if($payment->note)
            <div class="flex justify-between">
                <span class="text-slate-400">ملاحظة</span>
                <span class="text-slate-600">{{ $payment->note }}</span>
            </div>
            @endif
            @if($payment->raw_text)
            <div class="mt-2 pt-2 border-t border-slate-200">
                <p class="text-slate-400 mb-1">الرسالة الأصلية</p>
                <p class="text-[10px] text-slate-500 bg-white rounded-lg p-2 font-mono leading-relaxed whitespace-pre-wrap" dir="rtl">{{ $payment->raw_text }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
