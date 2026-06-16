@extends('admin.layout.app')

@section('title', 'Assign Currency to Countries')

    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">

@section('content')
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox">
                <div class="ibox-title">
                    <h5>Assign Currency to Countries</h5>
                </div>
                <div class="ibox-content">
                    <form action="{{ route('currencies.store') }}" method="POST">
                        @csrf

                        <!-- Single Currency Dropdown -->
                        <div class="form-group">
                            <label>Select Currency</label>
                            <select name="currency_id" class="tom-single" required>
                                <option value="">-- Select Currency --</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Multi-select Countries -->
                        <div class="form-group">
                            <label>Select Countries</label>
                            <select id="country-multiselect" class="tom-multi" name="countries[]" multiple required>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->iso_code }})</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">You can select multiple countries to assign this currency.</small>
                        </div>

                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-primary">Assign Currency</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Tom Select Loaded');

            // Single select
            new TomSelect('.tom-single', {
                create: false,
                allowEmptyOption: true,
                placeholder: 'Select a currency',
            });

            // Multi select
            new TomSelect('.tom-multi', {
                plugins: ['remove_button'],
                placeholder: 'Select countries',
            });
        });
    </script>
