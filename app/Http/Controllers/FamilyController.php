<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
use App\Models\Payment;
use Carbon\Carbon;

class FamilyController extends Controller
{
    public function show(Request $request, Card $card)
    {
        $currentBalance = $card->current_balance;
        $payments = $card->payments()->orderByDesc('received_at')->get();
        $byMonth = $payments->groupBy(function ($p) {
            return Carbon::parse($p->received_at ?? $p->created_at)->format('Y-m');
        })->map(function ($group) {
            return ['total' => $group->sum('amount'), 'items' => $group];
        });
        return view('family.index', compact('card', 'currentBalance', 'byMonth'));
    }

    public function updateNote(Request $request, Payment $payment)
    {
        $data = $request->validate(['note' => 'nullable|string']);
        $payment->note = $data['note'] ?? null;
        $payment->save();
        return back()->with('ok', 'تم حفظ الملاحظة');
    }
}
