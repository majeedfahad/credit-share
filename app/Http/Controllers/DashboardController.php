<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Payment;
use App\Models\Category;
use App\Models\SalaryCycle;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $cycle = SalaryCycle::current() ?? SalaryCycle::findOrCreateForDate(now());
        
        // Get all cards
        $allCards = Card::all();
        
        // All salary cycles with payment totals, newest first
        $allCycles = SalaryCycle::withCount('payments')
            ->withSum('payments', 'amount')
            ->orderByDesc('start_date')
            ->get();
        
        // Payments grouped by cycle (eager load for current view)
        $cyclePayments = Payment::with(["card", "category"])
            ->orderByDesc("received_at")
            ->get()
            ->groupBy("salary_cycle_id");
        
        // Spending by category for current cycle
        $categorySpending = Payment::selectRaw("category_id, SUM(amount) as total")
            ->where("salary_cycle_id", $cycle->id)
            ->whereNotNull("category_id")
            ->groupBy("category_id")
            ->with("category")
            ->get()
            ->sortByDesc("total");
        
        // Uncategorized spending
        $uncategorized = Payment::where("salary_cycle_id", $cycle->id)
            ->whereNull("category_id")
            ->sum("amount");
        
        // Categories for classification modal
        $categories = Category::orderBy("sort_order")->get();
        
        // Recent unclassified payments
        $unclassified = Payment::with("card")
            ->whereNull("category_id")
            ->orderByDesc("received_at")
            ->limit(10)
            ->get();
        
        return view("dashboard", compact(
            "cycle", "allCards", "allCycles", "cyclePayments",
            "categorySpending", "uncategorized",
            "categories", "unclassified"
        ));
    }
    
    public function updateCategory(Request $request, Payment $payment)
    {
        $data = $request->validate(["category_id" => "required|exists:categories,id"]);
        $payment->category_id = $data["category_id"];
        $payment->save();
        
        // Also save merchant pattern for future auto-classification
        if ($payment->merchant) {
            \App\Models\MerchantCategory::updateOrCreate(
                ["merchant_pattern" => mb_strtolower($payment->merchant)],
                ["category_id" => $data["category_id"]]
            );
        }
        
        return back()->with("success", "تم تحديث التصنيف");
    }
    
    public function updateBudget(Request $request, SalaryCycle $cycle)
    {
        $data = $request->validate(["budget" => "required|numeric|min:0"]);
        $cycle->budget = $data["budget"];
        $cycle->save();
        
        return back()->with("success", "تم تحديث الميزانية");
    }
    
    public function deletePayment(Request $request, Payment $payment)
    {
        $payment->delete();
        return back()->with("success", "تم حذف العملية");
    }

    public function cardDetails(Request $request, Card $card)
    {
        $cycle = SalaryCycle::current();
        
        $payments = Payment::with("category")
            ->where("card_id", $card->id)
            ->when($cycle, fn($q) => $q->where("salary_cycle_id", $cycle->id))
            ->orderByDesc("received_at")
            ->paginate(50);
        
        $totalSpent = Payment::where("card_id", $card->id)
            ->when($cycle, fn($q) => $q->where("salary_cycle_id", $cycle->id))
            ->sum("amount");
        
        $categories = Category::orderBy("sort_order")->get();
        
        return view("card-details", compact("card", "payments", "totalSpent", "cycle", "categories"));
    }
}
