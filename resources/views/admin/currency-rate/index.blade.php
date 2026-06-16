@extends('admin.layout.app')
@section('title', 'Currency Rate Setup')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Currency Rate Setup</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Currency Conversion to AED</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-8">
            <div class="ibox">
                <div class="ibox-title">
                    <h5>Define AED equivalent for each currency</h5>
                </div>
                <div class="ibox-content">
                    <p class="text-muted">AED is the base currency (1 AED = 1). Enter how many AED each currency equals for cart settlement.</p>
                    <form action="{{ route('currency-rates.store') }}" method="POST">
                        @csrf
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Currency</th>
                                    <th>Code</th>
                                    <th style="width:220px;">Rate to AED</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($currencies as $currency)
                                    <tr>
                                        <td>{{ $currency->name }}</td>
                                        <td><strong>{{ $currency->code }}</strong></td>
                                        <td>
                                            <input type="number"
                                                step="0.0001"
                                                min="0"
                                                class="form-control"
                                                name="rates[{{ $currency->id }}]"
                                                value="{{ old('rates.'.$currency->id, $rates[$currency->id] ?? ($currency->code === 'AED' ? 1 : '')) }}"
                                                placeholder="e.g. 3.675">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Save Rates</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
