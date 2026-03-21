@extends("layouts.app")
@section("title", $card->name)

@section("content")
<div class="max-w-4xl mx-auto px-4 py-6">
    
    {{-- Back Button --}}
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 mb-6">
        <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        الرئيسية
    </a>

    {{-- Card Header --}}
    <div class="{{ $card->isSubCard() ? 'card-gradient-2' : 'card-gradient' }} rounded-3xl p-6 text-white shadow-lg mb-6">
        <div class="flex items-center justify-between mb-4">
            <span class="text-white/80">{{ $card->name }}</span>
            <span class="text-white/60 text-sm font-mono">•••• {{ $card->last4 }}</span>
        </div>
        <p class="text-4xl font-bold mb-2">{{ number_format($card->current_balance, 2) }}</p>
        <p class="text-white/60 text-sm">{{ $card->currency }}</p>
        
        @if($cycle)
        <div class="mt-4 pt-4 border-t border-white/20">
            <p class="text-white/80 text-sm">مصروف الدورة الحالية</p>
            <p class="text-xl font-bold">{{ number_format($totalSpent, 2) }} ر.س</p>
        </div>
        @endif
    </div>

    {{-- Transactions --}}
    <div class="glass rounded-3xl p-6 shadow-lg">
        <h2 class="text-lg font-semibold text-slate-700 mb-4">العمليات</h2>
        
        @if($payments->isEmpty())
        <p class="text-center text-slate-400 py-8">لا توجد عمليات</p>
        @else
        <div class="space-y-2">
            @foreach($payments as $payment)
            <div class="flex items-center justify-between bg-white/50 rounded-xl p-4 hover:bg-white/80 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="text-2xl w-10 text-center">{{ $payment->category?->icon ?? "💳" }}</span>
                    <div>
                        <p class="font-medium text-slate-700">{{ $payment->merchant ?: ($payment->description ?: "عملية دفع") }}</p>
                        <p class="text-xs text-slate-400">{{ $payment->received_at?->format("d M Y - H:i") }}</p>
                        @if($payment->note)
                        <p class="text-xs text-indigo-500 mt-1">📝 {{ $payment->note }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-left">
                    <p class="font-bold text-red-500">-{{ number_format($payment->amount, 2) }}</p>
                    <p class="text-xs text-slate-400">{{ number_format($payment->balance_after, 2) }}</p>
                    @if(!$payment->category)
                    <button onclick="openCategoryModal({{ $payment->id }}, '{{ addslashes($payment->merchant) }}')"
                            class="text-xs text-indigo-500 hover:underline mt-1">صنّف</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="mt-6">
            {{ $payments->links() }}
        </div>
        @endif
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
