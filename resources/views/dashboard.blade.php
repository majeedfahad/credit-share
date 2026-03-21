@extends("layouts.app")
@section("title", "الرئيسية")

@section("content")
<div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
    
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">مرحباً 👋</h1>
            <p class="text-slate-500 text-sm mt-1">
                الدورة: {{ $cycle->start_date->format("d M") }} - {{ $cycle->end_date->format("d M Y") }}
                <span class="text-slate-400 mx-2">•</span>
                متبقي {{ $cycle->days_remaining }} يوم
            </p>
        </div>
        <form action="{{ route("logout") }}" method="POST">
            @csrf
            <button type="submit" class="text-slate-400 hover:text-slate-600 text-sm">خروج</button>
        </form>
    </div>

    {{-- Budget Overview Card --}}
    <div class="glass rounded-3xl p-6 mb-6 shadow-lg shadow-slate-200/50">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-700">ملخص الميزانية</h2>
            <button onclick="document.getElementById('budgetModal').classList.remove('hidden')"
                    class="text-sm text-indigo-600 hover:text-indigo-800">
                {{ $cycle->budget ? "تعديل" : "تحديد ميزانية" }}
            </button>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-white/50 rounded-2xl">
                <p class="text-2xl font-bold text-slate-800">{{ number_format($cycle->total_spent, 0) }}</p>
                <p class="text-xs text-slate-500 mt-1">المصروف</p>
            </div>
            @if($cycle->budget)
            <div class="text-center p-4 bg-white/50 rounded-2xl">
                <p class="text-2xl font-bold {{ $cycle->remaining_budget < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                    {{ number_format($cycle->remaining_budget, 0) }}
                </p>
                <p class="text-xs text-slate-500 mt-1">المتبقي</p>
            </div>
            <div class="text-center p-4 bg-white/50 rounded-2xl">
                <p class="text-2xl font-bold text-slate-800">{{ number_format($cycle->budget, 0) }}</p>
                <p class="text-xs text-slate-500 mt-1">الميزانية</p>
            </div>
            <div class="text-center p-4 bg-white/50 rounded-2xl">
                <div class="relative w-16 h-16 mx-auto">
                    <svg class="w-16 h-16 transform -rotate-90">
                        <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="6"/>
                        <circle cx="32" cy="32" r="28" fill="none" 
                                stroke="{{ $cycle->budget_percentage > 90 ? '#ef4444' : ($cycle->budget_percentage > 75 ? '#f59e0b' : '#10b981') }}" 
                                stroke-width="6" stroke-linecap="round"
                                stroke-dasharray="{{ min($cycle->budget_percentage, 100) * 1.76 }} 176"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-slate-700">
                        {{ round($cycle->budget_percentage) }}%
                    </span>
                </div>
            </div>
            @else
            <div class="col-span-3 flex items-center justify-center text-slate-400 text-sm">
                حدد ميزانية لتتبع مصروفاتك
            </div>
            @endif
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        @foreach($allCards as $card)
        <a href="{{ route('card.details', $card) }}"
           class="block {{ $card->isSubCard() ? 'card-gradient-2' : 'card-gradient' }} rounded-3xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <span class="text-white/80 text-sm">{{ $card->name }}</span>
                <span class="text-white/60 text-xs font-mono">•••• {{ $card->last4 }}</span>
            </div>
            <p class="text-3xl font-bold">{{ number_format($card->current_balance, 2) }}</p>
            <p class="text-white/60 text-xs mt-1">{{ $card->currency }}</p>
            @if($card->isSubCard())
            <span class="inline-block mt-3 text-xs bg-white/20 px-2 py-1 rounded-full">بطاقة فرعية</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Category Breakdown --}}
    @if($categorySpending->count() > 0)
    <div class="glass rounded-3xl p-6 mb-6 shadow-lg shadow-slate-200/50">
        <h2 class="text-lg font-semibold text-slate-700 mb-4">المصروفات حسب التصنيف</h2>
        <div class="space-y-3">
            @foreach($categorySpending as $item)
            @php $percentage = $cycle->total_spent > 0 ? ($item->total / $cycle->total_spent) * 100 : 0; @endphp
            <div class="flex items-center gap-3">
                <span class="text-2xl w-10 text-center">{{ $item->category->icon }}</span>
                <div class="flex-1">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700">{{ $item->category->name_ar }}</span>
                        <span class="text-slate-500">{{ number_format($item->total, 0) }} ر.س</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $percentage }}%; background-color: {{ $item->category->color }}"></div>
                    </div>
                </div>
            </div>
            @endforeach
            @if($uncategorized > 0)
            <div class="flex items-center gap-3 opacity-60">
                <span class="text-2xl w-10 text-center">❓</span>
                <div class="flex-1">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700">غير مصنف</span>
                        <span class="text-slate-500">{{ number_format($uncategorized, 0) }} ر.س</span>
                    </div>
                    <div class="h-2 bg-slate-200 rounded-full"></div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Unclassified Payments --}}
    @if($unclassified->count() > 0)
    <div class="glass rounded-3xl p-6 mb-6 shadow-lg shadow-slate-200/50 border-2 border-amber-200">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xl">⚠️</span>
            <h2 class="text-lg font-semibold text-slate-700">تحتاج تصنيف</h2>
        </div>
        <div class="space-y-2">
            @foreach($unclassified->take(5) as $payment)
            <div class="flex items-center justify-between bg-white/50 rounded-xl p-3">
                <div>
                    <p class="text-sm font-medium text-slate-700">{{ $payment->merchant ?: "بدون اسم" }}</p>
                    <p class="text-xs text-slate-400">{{ $payment->received_at?->format("d M") }} • {{ $payment->card->name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-700">{{ number_format($payment->amount, 0) }}</span>
                    <button onclick="openCategoryModal({{ $payment->id }}, '{{ addslashes($payment->merchant) }}')"
                            class="bg-amber-100 text-amber-700 text-xs px-3 py-1 rounded-full hover:bg-amber-200">
                        صنّف
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent Transactions --}}
    <div class="glass rounded-3xl p-6 shadow-lg shadow-slate-200/50">
        <h2 class="text-lg font-semibold text-slate-700 mb-4">آخر العمليات</h2>
        <div class="space-y-2">
            @foreach($cyclePayments->take(15) as $payment)
            <div class="flex items-center justify-between bg-white/50 rounded-xl p-3 hover:bg-white/80 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="text-xl w-8 text-center">{{ $payment->category?->icon ?? "💳" }}</span>
                    <div>
                        <p class="text-sm font-medium text-slate-700">{{ $payment->merchant ?: ($payment->description ?: "عملية دفع") }}</p>
                        <p class="text-xs text-slate-400">{{ $payment->received_at?->format("d M H:i") }} • {{ $payment->card->name }}</p>
                    </div>
                </div>
                <div class="text-left">
                    <p class="text-sm font-bold text-red-500">-{{ number_format($payment->amount, 2) }}</p>
                    @if(!$payment->category)
                    <button onclick="openCategoryModal({{ $payment->id }}, '{{ addslashes($payment->merchant) }}')"
                            class="text-xs text-indigo-500 hover:underline">صنّف</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Budget Modal --}}
<div id="budgetModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl p-6 w-full max-w-sm">
        <h3 class="text-lg font-bold text-slate-800 mb-4">تحديد الميزانية</h3>
        <form action="{{ route('cycle.budget', $cycle) }}" method="POST">
            @csrf
            <input type="number" name="budget" value="{{ $cycle->budget ?? '' }}"
                   class="w-full border border-slate-200 rounded-xl p-3 text-lg mb-4"
                   placeholder="مثال: 25000">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white rounded-xl p-3 font-medium hover:bg-indigo-700">
                    حفظ
                </button>
                <button type="button" onclick="document.getElementById('budgetModal').classList.add('hidden')"
                        class="flex-1 bg-slate-100 text-slate-600 rounded-xl p-3 font-medium hover:bg-slate-200">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Category Modal --}}
<div id="categoryModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl p-6 w-full max-w-sm max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-slate-800 mb-2">اختر التصنيف</h3>
        <p id="categoryMerchant" class="text-sm text-slate-500 mb-4"></p>
        <form id="categoryForm" method="POST">
            @csrf
            <div class="grid grid-cols-3 gap-2 mb-4">
                @foreach($categories as $cat)
                <label class="cursor-pointer">
                    <input type="radio" name="category_id" value="{{ $cat->id }}" class="hidden peer">
                    <div class="peer-checked:ring-2 peer-checked:ring-indigo-500 bg-slate-50 hover:bg-slate-100 rounded-xl p-3 text-center transition-all">
                        <span class="text-2xl block mb-1">{{ $cat->icon }}</span>
                        <span class="text-xs text-slate-600">{{ $cat->name_ar }}</span>
                    </div>
                </label>
                @endforeach
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white rounded-xl p-3 font-medium hover:bg-indigo-700">
                    حفظ
                </button>
                <button type="button" onclick="document.getElementById('categoryModal').classList.add('hidden')"
                        class="flex-1 bg-slate-100 text-slate-600 rounded-xl p-3 font-medium hover:bg-slate-200">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push("scripts")
<script>
function openCategoryModal(paymentId, merchant) {
    document.getElementById("categoryForm").action = "/payment/" + paymentId + "/category";
    document.getElementById("categoryMerchant").textContent = merchant || "عملية دفع";
    document.getElementById("categoryModal").classList.remove("hidden");
}
</script>
@endpush
