<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{Country,Currency};

class CurrencyController extends Controller
{
    public function index()
    {
        $countries = Country::get(); // Only unassigned countries
    return view('admin.currency.index', compact('countries'));
    }
    public function create()
{
    $currencies = Currency::all(); // Get all available currencies
    $countries = Country::whereNull('currency_id')->get();
    return view('admin.currency.create', compact('countries','currencies'));
}

public function store(Request $request)
{
    $request->validate([
        'currency_id' => 'required|exists:currencies,id',
        'countries' => 'required|array',
        'countries.*' => 'exists:countries,id'
    ]);

    Country::whereIn('id', $request->countries)->update(['currency_id' => $request->currency_id]);

    return redirect()->route('currencies')->with('success', 'Countries have been linked to the currency.');
}
 public function edit($id)
    {
        // Get the country with the selected id
        $country = Country::findOrFail($id);
        
        // Get all available currencies to show in the dropdown
        $currencies = Currency::all();

        // Return the edit view with the country and currencies
        return view('admin.currency.edit', compact('country', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        // Validate incoming request
        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
        ]);

        // Find the country and update it
        $country = Country::findOrFail($id);
        $country->currency_id = $request->currency_id;
        $country->save();

        // Redirect with success message
        return redirect()->route('currencies')->with('success', 'Currency currency updated successfully.');
    }
}
