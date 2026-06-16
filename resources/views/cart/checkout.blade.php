@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Checkout</h1>
    <form method="POST" action="{{ route('checkout.installment-request') }}">
    @csrf
    <table class="w-full border text-left mb-6">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-4">Package</th>
                <th class="p-4">Description</th>
                <th class="p-4">Price</th>
                <th class="p-4">Installments</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cart as $item)
                <tr class="border-b">
                    <td class="p-4">{{ $item->package_name }}</td>
                    <td class="p-4">{{ $item->short_description }}</td>
                    <td class="p-4">{{ $item->price }}AED</td>
                    <td class="p-4">
                        {{-- Hidden input for user_id --}}
                        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                        <input type="hidden" name="package_ids[]" value="{{ $item->course_fee_package_id }}">
                        <div class="flex gap-2">
                            @if($item->installments != '1')
                                <select name="installments[]" class="w-full border border-gray-300 py-2 px-3 rounded focus:outline-none" required>
                                    <option value="">-Request Installments-</option>
                                    <option value="1">No Installments</option>
                                    <option value="2">2 Installments</option>
                                    <option value="3">3 Installments</option>
                                    <option value="4">4 Installments</option>
                                </select>
                            @else
                                <input name="installments[]" type="hidden" value="1">
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="grid grid-cols-2 md:grid-cols-12 gap-10">

    <button type="submit" class="btn btn-primary rounded" style="background: #e6a60b;color: white;border-radius: 4px;padding: 1px;">
            Quote
        </button>
        </form>
        <!-- Installments Quote Form -->
        <!-- Installments Quote Form -->


    </div>
</div>
@endsection
