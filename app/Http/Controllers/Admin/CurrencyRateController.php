<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;

class CurrencyRateController extends Controller
{
    public function index()
    {
        $currencies = Currency::with('countries')->orderBy('code')->get();
        $rates = CurrencyRate::pluck('rate_to_aed', 'currency_id');

        return view('admin.currency-rate.index', compact('currencies', 'rates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['rates'] as $currencyId => $rate) {
            if ($rate === null || $rate === '') {
                continue;
            }

            CurrencyRate::updateOrCreate(
                ['currency_id' => $currencyId],
                ['rate_to_aed' => $rate]
            );
        }

        return redirect()->route('currency-rates.index')->with('success', 'Currency conversion rates saved.');
    }
}
