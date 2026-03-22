@extends("layouts.app")
@section("title", "الرئيسية")

@section("content")
<div class="max-w-lg mx-auto px-4 py-6">
    
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">مرحباً 👋</h1>
            <p class="text-slate-400 text-xs mt-0.5">
                {{ $cycle->start_date->format("d M") }} - {{ $cycle->end_date->format("d M") }}
                · متبقي {{ $cycle->days_remaining }} يوم
            </p>
        </div>
        <form action="{{ route("logout") }}" method="POST">
            @csrf
            <button type="submit" class="text-slate-300 hover:text-slate-500 text-xs">خروج</button>
        </form>
    </div>

    {{-- Cards — horizontal scroll --}}
    <div class="flex gap-3 overflow-x-auto pb-2 mb-5 snap-x snap-mandatory -mx-4 px-4 scrollbar-hide">
        @foreach($allCards as $card)
        <a href="{{ route('card.details', $card) }}"
           class="snap-start shrink-0 w-44 {{ $card->isSubCard() ? 'card-gradient-2' : 'card-gradient' }} rounded-2xl p-4 text-white shadow-md hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-white/70 text-xs truncate">{{ $card->name }}</span>
                <span class="text-white/50 text-[10px] font-mono">{{ $card->last4 }}</span>
            </div>
            <p class="text-xl font-bold">{{ number_format($card->current_balance, 0) }}</p>
            <p class="text-white/50 text-[10px] mt-0.5">{{ $card->currency ?? 'SAR' }}</p>
            @if($card->isSubCard())
            <span class="inline-block mt-2 text-[9px] bg-white/20 px-1.5 py-0.5 rounded-full">فرعية</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Budget Overview --}}
    <div class="glass rounded-2xl p-4 mb-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-700">الميزانية</h2>
            <button onclick="document.getElementById('budgetModal').classList.remove('hidden')"
                    class="text-xs text-indigo-500 hover:text-indigo-700">
                {{ $cycle->budget ? "تعديل" : "تحديد" }}
            </button>
        </div>
        
        @if($cycle->budget)
        <div class="flex items-end justify-between mb-2">
            <div>
                <span class="text-2xl font-bold text-slate-800">{{ number_format($cycle->total_spent, 0) }}</span>
                <span class="text-slate-400 text-xs mr-1">/ {{ number_format($cycle->budget, 0) }}</span>
            </div>
            <span class="text-sm font-bold {{ $cycle->budget_percentage > 90 ? 'text-red-500' : ($cycle->budget_percentage > 75 ? 'text-amber-500' : 'text-emerald-500') }}">
                {{ round($cycle->budget_percentage) }}%
            </span>
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                 style="width: {{ min($cycle->budget_percentage, 100) }}%; background: {{ $cycle->budget_percentage > 90 ? '#ef4444' : ($cycle->budget_percentage > 75 ? '#f59e0b' : '#10b981') }}">
            </div>
        </div>
        @if($cycle->remaining_budget !== null)
        <p class="text-xs text-slate-400 mt-2">
            متبقي <span class="{{ $cycle->remaining_budget < 0 ? 'text-red-500 font-semibold' : 'text-emerald-600' }}">{{ number_format(abs($cycle->remaining_budget), 0) }}</span>
            {{ $cycle->remaining_budget < 0 ? 'تجاوز' : 'ريال' }}
        </p>
        @endif
        @else
        <p class="text-xs text-slate-400">صرفت <span class="font-bold text-slate-700">{{ number_format($cycle->total_spent, 0) }}</span> ريال · حدد ميزانية لتتبع مصروفاتك</p>
        @endif
    </div>

    {{-- Salary Cycles — Collapsible with sort toggle --}}
    <div class="space-y-3 mb-5">
        <h2 class="text-sm font-semibold text-slate-700">العمليات</h2>
        
        @foreach($allCycles as $c)
        @php
            $isCurrentCycle = $c->id === $cycle->id;
            $payments = $cyclePayments->get($c->id, collect());
            $cycleTotal = $payments->sum('amount');
        @endphp
        <div class="glass rounded-2xl shadow-sm overflow-hidden" x-data="{ open: {{ $isCurrentCycle ? 'true' : 'false' }}, sortBy: 'time' }">
            {{-- Cycle Header --}}
            <button @click="open = !open" class="w-full flex items-center justify-between p-4 text-right hover:bg-white/50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full {{ $isCurrentCycle ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-400' }} flex items-center justify-center text-xs font-bold">
                        {{ $c->start_date->format('m') }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">
                            {{ $c->start_date->format('d M') }} → {{ $c->end_date->format('d M Y') }}
                            @if($isCurrentCycle)
                            <span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full mr-1">الحالية</span>
                            @endif
                        </p>
                        <p class="text-[10px] text-slate-400">{{ $payments->count() }} عملية</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-700">{{ number_format($cycleTotal, 0) }}</span>
                    <svg :class="{ 'rotate-180': open }" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>

            {{-- Payments List --}}
            <div x-show="open" x-collapse>
                {{-- Sort Toggle --}}
                <div class="flex items-center gap-1 px-4 py-2 border-t border-slate-100">
                    <span class="text-[10px] text-slate-400 ml-1">ترتيب:</span>
                    <button @click.stop="sortBy = 'time'" 
                            :class="sortBy === 'time' ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'"
                            class="text-[10px] px-2 py-1 rounded-full transition-colors">
                        🕐 الأحدث
                    </button>
                    <button @click.stop="sortBy = 'amount'" 
                            :class="sortBy === 'amount' ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-50 text-slate-400 hover:bg-slate-100'"
                            class="text-[10px] px-2 py-1 rounded-full transition-colors">
                        💰 الأعلى
                    </button>
                </div>

                <div class="divide-y divide-slate-50">
                    {{-- Time-sorted list --}}
                    <template x-if="sortBy === 'time'">
                        <div>
                            @forelse($payments->sortByDesc('received_at') as $payment)
                            @include('partials.payment-row', ['payment' => $payment])
                            @empty
                            <div class="px-4 py-6 text-center text-xs text-slate-400">لا توجد عمليات</div>
                            @endforelse
                        </div>
                    </template>
                    {{-- Amount-sorted list --}}
                    <template x-if="sortBy === 'amount'">
                        <div>
                            @forelse($payments->sortByDesc('amount') as $payment)
                            @include('partials.payment-row', ['payment' => $payment])
                            @empty
                            <div class="px-4 py-6 text-center text-xs text-slate-400">لا توجد عمليات</div>
                            @endforelse
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Category Breakdown --}}
    @if($categorySpending->count() > 0)
    <div class="glass rounded-2xl p-4 mb-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">التصنيفات</h2>
        <div class="space-y-2">
            @foreach($categorySpending->take(6) as $item)
            @php $percentage = $cycle->total_spent > 0 ? ($item->total / $cycle->total_spent) * 100 : 0; @endphp
            <div class="flex items-center gap-2">
                <span class="text-lg w-7 text-center">{{ $item->category->icon }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="text-slate-600 truncate">{{ $item->category->name_ar }}</span>
                        <span class="text-slate-400 shrink-0 mr-2">{{ number_format($item->total, 0) }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $percentage }}%; background-color: {{ $item->category->color }}"></div>
                    </div>
                </div>
            </div>
            @endforeach
            @if($uncategorized > 0)
            <div class="flex items-center gap-2 opacity-50">
                <span class="text-lg w-7 text-center">❓</span>
                <div class="flex-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-600">غير مصنف</span>
                        <span class="text-slate-400">{{ number_format($uncategorized, 0) }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Unclassified Quick Actions --}}
    @if($unclassified->count() > 0)
    <div class="glass rounded-2xl p-4 mb-5 shadow-sm border border-amber-200/60">
        <div class="flex items-center gap-1.5 mb-3">
            <span class="text-sm">⚠️</span>
            <h2 class="text-sm font-semibold text-slate-700">تحتاج تصنيف</h2>
            <span class="text-[10px] bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full mr-auto">{{ $unclassified->count() }}</span>
        </div>
        <div class="space-y-1.5">
            @foreach($unclassified->take(5) as $payment)
            <div class="flex items-center justify-between bg-white/50 rounded-xl px-3 py-2">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-slate-700 truncate">{{ $payment->merchant ?: "بدون اسم" }}</p>
                    <p class="text-[10px] text-slate-400">{{ $payment->received_at?->format("d M") }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-bold text-slate-700">{{ number_format($payment->amount, 0) }}</span>
                    <button onclick="openCategoryModal({{ $payment->id }}, '{{ addslashes($payment->merchant) }}')"
                            class="bg-amber-100 text-amber-700 text-[10px] px-2 py-0.5 rounded-full hover:bg-amber-200">صنّف</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Budget Modal --}}
<div id="budgetModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm">
        <h3 class="text-base font-bold text-slate-800 mb-3">تحديد الميزانية</h3>
        <form action="{{ route('cycle.budget', $cycle) }}" method="POST">
            @csrf
            <input type="number" name="budget" value="{{ $cycle->budget ?? '' }}"
                   class="w-full border border-slate-200 rounded-xl p-3 text-lg mb-3"
                   placeholder="مثال: 25000" inputmode="numeric">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white rounded-xl p-2.5 text-sm font-medium hover:bg-indigo-700">حفظ</button>
                <button type="button" onclick="document.getElementById('budgetModal').classList.add('hidden')"
                        class="flex-1 bg-slate-100 text-slate-500 rounded-xl p-2.5 text-sm font-medium hover:bg-slate-200">إلغاء</button>
            </div>
        </form>
    </div>
</div>

{{-- Category Modal --}}
<div id="categoryModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl p-5 w-full max-w-sm max-h-[80vh] overflow-y-auto">
        <h3 class="text-base font-bold text-slate-800 mb-1">اختر التصنيف</h3>
        <p id="categoryMerchant" class="text-xs text-slate-400 mb-3"></p>
        <form id="categoryForm" method="POST">
            @csrf
            <div class="grid grid-cols-3 gap-2 mb-3">
                @foreach($categories as $cat)
                <label class="cursor-pointer">
                    <input type="radio" name="category_id" value="{{ $cat->id }}" class="hidden peer">
                    <div class="peer-checked:ring-2 peer-checked:ring-indigo-500 bg-slate-50 hover:bg-slate-100 rounded-xl p-2.5 text-center transition-all">
                        <span class="text-xl block mb-0.5">{{ $cat->icon }}</span>
                        <span class="text-[10px] text-slate-600">{{ $cat->name_ar }}</span>
                    </div>
                </label>
                @endforeach
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white rounded-xl p-2.5 text-sm font-medium hover:bg-indigo-700">حفظ</button>
                <button type="button" onclick="document.getElementById('categoryModal').classList.add('hidden')"
                        class="flex-1 bg-slate-100 text-slate-500 rounded-xl p-2.5 text-sm font-medium hover:bg-slate-200">إلغاء</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push("scripts")
{{-- Alpine.js for collapsible --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
{{-- Alpine collapse plugin --}}
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

<script>
function togglePaymentDetail(row) {
    const detail = row.querySelector('.payment-detail');
    if (detail) detail.classList.toggle('hidden');
}

function openCategoryModal(paymentId, merchant) {
    document.getElementById("categoryForm").action = "/payment/" + paymentId + "/category";
    document.getElementById("categoryMerchant").textContent = merchant || "عملية دفع";
    document.getElementById("categoryModal").classList.remove("hidden");
}
</script>

<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush
